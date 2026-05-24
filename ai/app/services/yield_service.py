"""Previsione resa oliveto — XGBoost con fallback agronomico FAO."""
from __future__ import annotations

from datetime import datetime
from pathlib import Path
from typing import Optional

import numpy as np

from .weather_fetcher import fetch_historical

MODEL_PATH = Path(__file__).parent.parent.parent / "trained_models" / "yield_xgboost.pkl"

FEATURE_COLS = [
    "pioggia_annua_mm",
    "pioggia_primavera_mm",
    "temp_media_estate",
    "temp_media_primavera",
    "et0_estate",
    "giorni_gelo",
    "giorni_caldo",
]

# ── RESA BASE PER ETTARO — zona montana interna Sicilia (600-700 m) ───────────
BASE_YIELD_KG_HA: dict[str, float] = {
    "asciutto":    4500.0,   # 45 q.li/ha — oliveto tradizionale non irriguo
    "irriguo":     6500.0,   # 65 q.li/ha — con irrigazione di soccorso
    "agrivoltaico": 5500.0,  # 55 q.li/ha — +20% asciutto grazie all'ombra pannelli
}
_REF_YIELD = 3000.0  # riferimento per normalizzare l'output del modello meteo

# ── MOLTIPLICATORE DI VARIETÀ (resa druppe relativa alla media) ───────────────
VARIETY_MULTIPLIER: dict[str, float] = {
    "nocellara del belice": 0.95,
    "biancolilla":          1.05,
    "cerasuola":            1.00,
    "ogliarola messinese":  1.02,
    "moresca":              0.98,
    "carolea":              1.03,
    "tonda iblea":          1.00,
    "nocellara etnea":      0.94,
    "santagatese":          1.01,
    "verdello":             0.97,
}

# ── TASSO DI ESTRAZIONE OLIO — resa reale al frantoio siciliano ───────────────
# Regola pratica: ~7 kg olive → 1 litro olio (0.916 kg/L × 1/7 ≈ 13.1%)
# Nocellara Belice/Etnea: oliva grande, 85% polpa, duplice attitudine → resa bassa
EXTRACTION_RATES: dict[str, float] = {
    "nocellara del belice": 0.13,   # 13% — drupa grande, molta polpa, resa più bassa
    "biancolilla":          0.16,   # 16% — resa più alta tra le siciliane
    "cerasuola":            0.15,   # 15%
    "moresca":              0.14,   # 14%
    "tonda iblea":          0.14,   # 14%
    "ogliarola messinese":  0.15,   # 15%
    "carolea":              0.17,   # 17%
    "nocellara etnea":      0.13,   # 13% — simile alla Belice
    "santagatese":          0.14,   # 14%
    "verdello":             0.14,   # 14%
}
_DEFAULT_EXTRACTION = 0.14  # 14% media siciliana reale
_OIL_DENSITY = 0.916        # kg/L densità olio di oliva a 20°C

# backward-compat alias
OIL_RATE = EXTRACTION_RATES

# ── FATTORE ETÀ PIANTA — anni dal trapianto ───────────────────────────────────
AGE_PRODUCTION_FACTOR: dict[int, float] = {
    1: 0.00,
    2: 0.00,
    3: 0.10,
    4: 0.20,
    5: 0.35,
    6: 0.50,
    7: 0.70,
    8: 0.85,
    9: 0.95,
    10: 1.00,
}
# Oltre 10 anni → fattore 1.0 (piena produzione)


class YieldPredictor:
    def __init__(self) -> None:
        self.model = None
        self.model_loaded = False
        self._try_load()

    def _try_load(self) -> None:
        if MODEL_PATH.exists():
            try:
                import joblib
                self.model = joblib.load(MODEL_PATH)
                self.model_loaded = True
            except Exception:
                pass

    # ── public ────────────────────────────────────────────────────────────────

    async def predict(
        self,
        lat: float,
        lon: float,
        surface_ha: float,
        tree_count: int,
        variety: str,
        year: int,
        planting_year: Optional[int] = None,
        irrigation_type: str = "asciutto",
        prev_year_actual_kg: Optional[float] = None,
        farm_avg_kg: Optional[float] = None,
    ) -> dict:
        # Agricultural year: Sep(year-1) → Aug(year)
        start = f"{year - 1}-09-01"
        end   = min(f"{year}-08-31", datetime.today().strftime("%Y-%m-%d"))

        features    = await self._compute_features(lat, lon, start, end)
        variety_key = variety.lower()
        v_mult      = VARIETY_MULTIPLIER.get(variety_key, 1.00)
        oil_rate    = EXTRACTION_RATES.get(variety_key, _DEFAULT_EXTRACTION)

        # ── fattore meteo (normalizzato rispetto alla resa di riferimento) ────
        if self.model_loaded and features:
            raw_yield, method = self._predict_ml(features)
        else:
            raw_yield, method = self._predict_agronomic(features)

        meteo_factor = raw_yield / _REF_YIELD

        # ── fattori agronomici ────────────────────────────────────────────────
        base_yield       = BASE_YIELD_KG_HA.get(irrigation_type, BASE_YIELD_KG_HA["asciutto"])
        age_f            = self._age_factor(planting_year, year)
        alt_f, alt_label = self._alternance_factor(prev_year_actual_kg, farm_avg_kg)

        # ── calcolo resa ──────────────────────────────────────────────────────
        yield_kg_ha = base_yield * meteo_factor * v_mult * age_f * alt_f
        total_kg    = yield_kg_ha * surface_ha
        oil_kg      = total_kg * oil_rate
        oil_liters  = oil_kg / _OIL_DENSITY

        confidence = 0.78 if self.model_loaded else 0.55

        # ── scenari ───────────────────────────────────────────────────────────
        scenarios = {
            "ottimistico":  self._scenario(yield_kg_ha, total_kg, oil_liters, 1.25, "25%"),
            "medio":        self._scenario(yield_kg_ha, total_kg, oil_liters, 1.00, "50%"),
            "pessimistico": self._scenario(yield_kg_ha, total_kg, oil_liters, 0.70, "25%"),
        }

        age = (year - planting_year) if planting_year else None

        return {
            "yield_kg_ha":     round(yield_kg_ha, 1),
            "total_kg":        round(total_kg, 1),
            "oil_liters":      round(oil_liters, 1),
            "confidence":      confidence,
            "method":          method,
            "age_factor":      round(age_f, 2),
            "alternance_factor": round(alt_f, 2),
            "scenarios":       scenarios,
            "explanation_text": self._explain(
                features, yield_kg_ha, oil_liters, variety,
                oil_rate, age_f, age, alt_f, alt_label, method,
            ),
            "features_used":   {k: round(v, 2) for k, v in features.items()} if features else {},
        }

    # ── private ───────────────────────────────────────────────────────────────

    @staticmethod
    def _age_factor(planting_year: Optional[int], current_year: int) -> float:
        if planting_year is None:
            return 1.0
        age = current_year - planting_year
        if age <= 0:
            return 0.0
        return AGE_PRODUCTION_FACTOR.get(age, 1.0)

    @staticmethod
    def _alternance_factor(
        prev_yield: Optional[float], avg_yield: Optional[float]
    ) -> tuple[float, str]:
        if prev_yield is None or avg_yield is None or avg_yield <= 0:
            return 1.0, "storico non disponibile"
        if prev_yield > avg_yield * 1.3:
            return 0.6, f"anno di SCARICA (prev. {prev_yield:.0f} kg > media {avg_yield:.0f} kg × 1.3)"
        if prev_yield < avg_yield * 0.7:
            return 1.3, f"anno di CARICA (prev. {prev_yield:.0f} kg < media {avg_yield:.0f} kg × 0.7)"
        return 1.0, "anno normale"

    async def _compute_features(
        self, lat: float, lon: float, start: str, end: str
    ) -> Optional[dict]:
        try:
            data  = await fetch_historical(lat, lon, start, end)
            daily = data.get("daily", {})
        except Exception:
            return None

        dates = daily.get("time", [])
        tmax  = daily.get("temperature_2m_max", [])
        tmin  = daily.get("temperature_2m_min", [])
        rain  = daily.get("precipitation_sum", [])
        et0   = daily.get("et0_fao_evapotranspiration", [])

        def m(d: str) -> int:
            return int(d[5:7])

        def safe(v, default=0.0):
            return v if v is not None else default

        def tmean(x, n):
            return (safe(x, 25) + safe(n, 15)) / 2

        rain_total  = sum(safe(r) for r in rain)
        rain_spring = sum(safe(r) for r, d in zip(rain, dates) if m(d) in (3, 4, 5))

        summer_days = [(tmean(x, n), d) for x, n, d in zip(tmax, tmin, dates) if m(d) in (6, 7, 8)]
        spring_days = [(tmean(x, n), d) for x, n, d in zip(tmax, tmin, dates) if m(d) in (3, 4, 5)]
        temp_summer = float(np.mean([t for t, _ in summer_days])) if summer_days else 27.0
        temp_spring = float(np.mean([t for t, _ in spring_days])) if spring_days else 15.5

        et0_summer  = sum(safe(e) for e, d in zip(et0, dates) if m(d) in (6, 7, 8))
        frost_days  = sum(1 for n, d in zip(tmin, dates) if safe(n, 5) < 0 and m(d) in (12, 1, 2))
        hot_days    = sum(1 for x, d in zip(tmax, dates) if safe(x, 25) > 35 and m(d) in (6, 7, 8))

        return {
            "pioggia_annua_mm":     rain_total,
            "pioggia_primavera_mm": rain_spring,
            "temp_media_estate":    temp_summer,
            "temp_media_primavera": temp_spring,
            "et0_estate":           et0_summer,
            "giorni_gelo":          float(frost_days),
            "giorni_caldo":         float(hot_days),
        }

    def _predict_ml(self, features: dict) -> tuple[float, str]:
        X = np.array([[features[f] for f in FEATURE_COLS]])
        pred = float(self.model.predict(X)[0])
        return max(800.0, min(6000.0, pred)), "xgboost"

    def _predict_agronomic(self, features: Optional[dict]) -> tuple[float, str]:
        """Restituisce un valore normalizzato intorno a _REF_YIELD (3000)."""
        base = _REF_YIELD
        if not features:
            return base, "agronomic_fallback_no_weather"

        rain = features["pioggia_annua_mm"]
        if rain < 400:
            rf = 0.65
        elif rain < 550:
            rf = 0.80 + (rain - 400) / 1000
        elif rain <= 750:
            rf = 1.00
        elif rain <= 900:
            rf = 0.95
        else:
            rf = 0.85

        spring = features["pioggia_primavera_mm"]
        sf = min(1.20, max(0.80, spring / 180))

        te = features["temp_media_estate"]
        if te > 30:
            hf = 0.75
        elif te > 28:
            hf = 0.88
        elif te >= 24:
            hf = 1.00
        else:
            hf = 0.92

        hot_pen   = max(0.70, 1 - features["giorni_caldo"] * 0.015)
        frost_pen = max(0.70, 1 - features["giorni_gelo"] * 0.05)

        return round(base * rf * sf * hf * hot_pen * frost_pen, 1), "agronomic_fallback"

    @staticmethod
    def _scenario(ykha: float, tkg: float, oil: float, mult: float, prob: str) -> dict:
        return {
            "yield_kg_ha": round(ykha * mult),
            "total_kg":    round(tkg * mult),
            "oil_liters":  round(oil * mult),
            "probability": prob,
        }

    def _explain(
        self,
        features: Optional[dict],
        yield_kg_ha: float,
        oil_liters: float,
        variety: str,
        oil_rate: float,
        age_factor: float,
        age: Optional[int],
        alt_factor: float,
        alt_label: str,
        method: str,
    ) -> str:
        age_str = (
            f"{age_factor * 100:.0f}% (anno {age} dal trapianto)"
            if age is not None
            else f"{age_factor * 100:.0f}% (età non specificata)"
        )

        parts: list[str] = []

        if features:
            rain = features["pioggia_annua_mm"]
            if rain < 450:
                parts.append(f"pioggia bassa ({rain:.0f} mm) → stress idrico −20%")
            elif rain > 800:
                parts.append(f"pioggia eccessiva ({rain:.0f} mm) → rischio malattie −10%")
            else:
                parts.append(f"pioggia adeguata ({rain:.0f} mm)")

            spring = features["pioggia_primavera_mm"]
            if spring > 220:
                parts.append(f"primavera abbondante ({spring:.0f} mm) → fioritura favorita +15%")
            elif spring < 140:
                parts.append(f"primavera secca ({spring:.0f} mm) → stress florale −15%")

            te = features["temp_media_estate"]
            if te > 29:
                parts.append(f"estate molto calda ({te:.1f}°C) → stress termico −20%")
            elif te > 27:
                parts.append(f"estate calda ({te:.1f}°C) → leggero calo resa")

            hot = features["giorni_caldo"]
            if hot > 12:
                parts.append(f"{hot} giorni con T>35°C → mortalità polline elevata")

            frost = features["giorni_gelo"]
            if frost > 2:
                parts.append(f"{frost} giorni di gelo → possibile danno fioritura")

        label = (
            "modello XGBoost (calibrato su dati ISTAT Agrigento 2005-2024)"
            if "xgboost" in method
            else "modello agronomico FAO"
        )
        meteo_summary = " | ".join(parts) + "." if parts else "Dati meteo non disponibili."

        return (
            f"Resa stimata {yield_kg_ha:.0f} kg/ha per {variety} ({label}). "
            f"Resa olio {oil_rate * 100:.0f}% ({variety}). "
            f"Fattore età pianta: {age_str}. "
            f"Fattore alternanza: {alt_label}. "
            f"Produzione olio stimata: {oil_liters:.0f} litri. "
            f"{meteo_summary}"
        )