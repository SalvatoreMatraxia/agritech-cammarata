"""
Bilancio idrico FAO-56 per oliveto biologico.

Parametri suolo: FC (field capacity), WP (wilting point) in m³/m³
Olivo: Kc = 0.65, profondità radici Zr = 1.2 m
TAW = (FC - WP) × Zr × 1000  [mm]
RAW = 0.5 × TAW               (soglia stress)
"""
from __future__ import annotations

from datetime import datetime, timedelta

from .weather_fetcher import fetch_forecast, fetch_historical

SOIL: dict[str, dict[str, float]] = {
    "clay_loam":  {"fc": 0.36, "wp": 0.18},
    "sandy_loam": {"fc": 0.27, "wp": 0.10},
    "loam":       {"fc": 0.31, "wp": 0.14},
    "clay":       {"fc": 0.42, "wp": 0.22},
    "sandy":      {"fc": 0.20, "wp": 0.08},
}

KC      = 0.65   # olive Kc mid-season
ZR      = 1.2    # root depth m
WATER_COST = 0.35  # €/m³ (acqua irrigua Sicilia)
OIL_PRICE  = 6.0   # €/L


class WaterStressCalculator:

    async def calculate(
        self,
        lat: float,
        lon: float,
        soil_type: str = "clay_loam",
        surface_ha: float = 1.0,
    ) -> dict:
        today = datetime.today()
        soil  = SOIL.get(soil_type, SOIL["clay_loam"])
        fc, wp = soil["fc"], soil["wp"]

        taw   = (fc - wp) * ZR * 1000   # mm
        raw   = 0.5 * taw               # mm
        fc_mm = fc * ZR * 1000          # mm
        wp_mm = wp * ZR * 1000          # mm

        hist_start = (today - timedelta(days=30)).strftime("%Y-%m-%d")
        hist_end   = today.strftime("%Y-%m-%d")

        try:
            h     = await fetch_historical(lat, lon, hist_start, hist_end)
            h_d   = h.get("daily", {})
        except Exception:
            h_d = {}

        try:
            f     = await fetch_forecast(lat, lon, days=7)
            f_d   = f.get("daily", {})
        except Exception:
            f_d = {}

        all_dates = h_d.get("time", []) + f_d.get("time", [])
        all_rain  = h_d.get("precipitation_sum", []) + f_d.get("precipitation_sum", [])
        all_et0   = h_d.get("et0_fao_evapotranspiration", []) + f_d.get("et0_fao_evapotranspiration", [])

        # ── daily balance ──────────────────────────────────────────────────────
        sw  = fc_mm   # start at field capacity
        log: list[dict] = []

        for d, rain, et0 in zip(all_dates, all_rain, all_et0):
            rain = rain if rain is not None else 0.0
            et0  = et0  if et0  is not None else 3.5
            etc  = et0 * KC

            sw = max(wp_mm, min(fc_mm, sw + rain - etc))
            dr = fc_mm - sw
            ks = 1.0 if dr <= raw else max(0.0, (taw - dr) / (taw - raw))

            log.append({
                "date":     d,
                "rain_mm":  round(rain, 1),
                "et0_mm":   round(et0, 1),
                "et_c_mm":  round(etc, 1),
                "sw_mm":    round(sw, 1),
                "stress_ks": round(ks, 3),
            })

        # ── current state ──────────────────────────────────────────────────────
        dr_now = fc_mm - sw
        stress = max(0.0, min(1.0, (dr_now - raw) / (taw - raw))) if dr_now > raw else 0.0
        reserve_pct = round((sw - wp_mm) / (fc_mm - wp_mm) * 100, 1)

        irrigation_needed = stress > 0.05
        # Volume needed to refill to field capacity (1 mm = 10 m³/ha)
        vol_mc_ha = max(0.0, round(dr_now / 10, 2)) if irrigation_needed else 0.0

        # Rain-free streak
        rain_free = 0
        for entry in reversed(log):
            if entry["rain_mm"] < 1.0:
                rain_free += 1
            else:
                break

        last_et0 = log[-1]["et0_mm"] if log else 3.5

        return {
            "stress_index":     round(stress, 3),
            "water_balance_mm": round(sw - fc_mm, 1),
            "reserve_pct":      reserve_pct,
            "irrigation_needed": irrigation_needed,
            "volume_mc_per_ha": vol_mc_ha,
            "economic_impact":  self._economic(stress, surface_ha, irrigation_needed, vol_mc_ha),
            "explanation_text": self._explain(stress, reserve_pct, rain_free, last_et0, soil_type, irrigation_needed),
            "daily_balance":    log[-7:],
        }

    # ── private ───────────────────────────────────────────────────────────────

    @staticmethod
    def _economic(stress: float, surface_ha: float, needed: bool, vol_ha: float) -> dict:
        cost = vol_ha * surface_ha * WATER_COST if needed and vol_ha > 0 else 0.0
        loss_kg  = 3000 * stress * 0.35
        loss_oil = (loss_kg * 0.175 / 0.917) * OIL_PRICE * surface_ha
        ratio = round(loss_oil / max(1.0, cost), 2) if cost > 0 else 0.0
        return {
            "costo_acqua_eur":      round(cost, 2),
            "perdita_stimata_eur":  round(loss_oil, 2),
            "rapporto_costi_benefici": ratio,
        }

    @staticmethod
    def _explain(
        stress: float, reserve_pct: float, rain_free: int,
        et0: float, soil_type: str, needed: bool
    ) -> str:
        pct = int(stress * 100)
        soil_label = soil_type.replace("_", " ")
        if pct == 0:
            return (
                f"Nessuno stress idrico — riserva al {reserve_pct:.0f}% (suolo {soil_label}). "
                f"ET₀ attuale {et0:.1f} mm/giorno (ET_c olivo: {et0 * KC:.1f} mm/giorno)."
            )
        action = "Irrigazione consigliata" if needed else "Monitorare riserva idrica"
        return (
            f"Stress idrico al {pct}% — riserva al {reserve_pct:.0f}% "
            f"dopo {rain_free} giorni senza pioggia significativa. "
            f"ET₀ {et0:.1f} mm/giorno → ET_c olivo {et0 * KC:.1f} mm/giorno. "
            f"{action}."
        )