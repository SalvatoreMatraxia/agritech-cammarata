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

VARIETY_MULTIPLIER: dict[str, float] = {
    "nocellara del belice": 0.95,
    "biancolilla":          1.05,
    "cerasuola":            1.00,
    "ogliarola messinese":  1.02,
    "moresca":              0.98,
    "carolea":              1.03,
}
OIL_RATE: dict[str, float] = {
    "nocellara del belice": 0.18,
    "biancolilla":          0.16,
    "cerasuola":            0.19,
    "ogliarola messinese":  0.17,
}
_OIL_DENSITY = 0.917  # kg/L


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
    ) -> dict:
        # Agricultural year: Sep(year-1) → Aug(year)
        start = f"{year - 1}-09-01"
        end   = min(f"{year}-08-31", datetime.today().strftime("%Y-%m-%d"))

        features = await self._compute_features(lat, lon, start, end)
        variety_key = variety.lower()
        v_mult   = VARIETY_MULTIPLIER.get(variety_key, 1.00)
        oil_rate = OIL_RATE.get(variety_key, 0.175)

        if self.model_loaded and features:
            base_yield, method = self._predict_ml(features)
        else:
            base_yield, method = self._predict_agronomic(features)

        yield_kg_ha = base_yield * v_mult
        total_kg    = yield_kg_ha * surface_ha
        oil_liters  = (total_kg * oil_rate) / _OIL_DENSITY

        confidence = 0.78 if self.model_loaded else 0.55

        scenarios = {
            "ottimistico":  self._scenario(yield_kg_ha, total_kg, oil_liters, 1.20, "25%"),
            "medio":        self._scenario(yield_kg_ha, total_kg, oil_liters, 1.00, "50%"),
            "pessimistico": self._scenario(yield_kg_ha, total_kg, oil_liters, 0.75, "25%"),
        }

        return {
            "yield_kg_ha":     round(yield_kg_ha, 1),
            "total_kg":        round(total_kg, 1),
            "oil_liters":      round(oil_liters, 1),
            "confidence":      confidence,
            "method":          method,
            "scenarios":       scenarios,
            "explanation_text": self._explain(features, yield_kg_ha, variety, method),
            "features_used":   {k: round(v, 2) for k, v in features.items()} if features else {},
        }

    # ── private ───────────────────────────────────────────────────────────────

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
            "pioggia_annua_mm":    rain_total,
            "pioggia_primavera_mm": rain_spring,
            "temp_media_estate":   temp_summer,
            "temp_media_primavera": temp_spring,
            "et0_estate":          et0_summer,
            "giorni_gelo":         float(frost_days),
            "giorni_caldo":        float(hot_days),
        }

    def _predict_ml(self, features: dict) -> tuple[float, str]:
        X = np.array([[features[f] for f in FEATURE_COLS]])
        pred = float(self.model.predict(X)[0])
        return max(800.0, min(6000.0, pred)), "xgboost"

    def _predict_agronomic(self, features: Optional[dict]) -> tuple[float, str]:
        base = 3000.0
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
        variety: str,
        method: str,
    ) -> str:
        if not features:
            return (
                f"Previsione agronomica base per {variety}: {yield_kg_ha:.0f} kg/ha. "
                "Dati meteo non disponibili per analisi dettagliata."
            )

        parts: list[str] = []

        rain = features["pioggia_annua_mm"]
        if rain < 450:
            parts.append(f"pioggia bassa ({rain:.0f} mm) → stress idrico −20%")
        elif rain > 800:
            parts.append(f"pioggia eccessiva ({rain:.0f} mm) → rischio malattie −10%")
        else:
            parts.append(f"pioggia adeguata ({rain:.0f} mm)")

        spring = features["pioggia_primavera_mm"]
        if spring > 220:
            parts.append(f"piogge primaverili abbondanti ({spring:.0f} mm) → fioritura favorita +15%")
        elif spring < 140:
            parts.append(f"primavera secca ({spring:.0f} mm) → stress florale −15%")

        te = features["temp_media_estate"]
        if te > 29:
            parts.append(f"estate molto calda ({te:.1f}°C) → stress termico −20%")
        elif te > 27:
            parts.append(f"estate calda ({te:.1f}°C) → leggero calo resa")
        else:
            parts.append(f"temperatura estiva nella norma ({te:.1f}°C)")

        hot = features["giorni_caldo"]
        if hot > 12:
            parts.append(f"{hot} giorni con T>35°C → mortalità polline elevata")

        frost = features["giorni_gelo"]
        if frost > 2:
            parts.append(f"{frost} giorni di gelo → possibile danno alla fioritura")

        label = (
            "modello XGBoost (calibrato su dati ISTAT Agrigento 2005-2024)"
            if "xgboost" in method
            else "modello agronomico FAO"
        )
        return (
            f"Previsione {yield_kg_ha:.0f} kg/ha per {variety} ({label}). "
            + " | ".join(parts) + "."
        )