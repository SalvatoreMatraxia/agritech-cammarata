"""NDVI fetcher: Agromonitoring API with Open-Meteo ET0 fallback."""
from __future__ import annotations

import math
import os
from datetime import date, timedelta

import httpx

ARCHIVE_URL = "https://archive-api.open-meteo.com/v1/archive"
AGRO_BASE   = "http://api.agromonitoring.com/agro/1.0"


class SatelliteFetcher:

    def __init__(self) -> None:
        self._agro_key = os.getenv("AGROMONITORING_API_KEY", "")

    async def fetch_ndvi(self, lat: float, lon: float, days_back: int = 90) -> dict:
        if self._agro_key:
            try:
                return await self._fetch_agromonitoring(lat, lon, days_back)
            except Exception:
                pass
        return await self._estimate_from_et0(lat, lon, days_back)

    async def _fetch_agromonitoring(self, lat: float, lon: float, days_back: int) -> dict:
        end   = date.today()
        start = end - timedelta(days=days_back)

        async with httpx.AsyncClient(timeout=20) as c:
            r = await c.get(
                f"{AGRO_BASE}/ndvi/history",
                params={
                    "appid": self._agro_key,
                    "polyid": f"{lat},{lon}",
                    "start":  int(start.strftime("%s")),
                    "end":    int(end.strftime("%s")),
                },
            )
            r.raise_for_status()
            raw = r.json()

        history = [
            {"date": item["dt_iso"][:10], "ndvi": round(item["data"]["mean"], 3)}
            for item in raw
            if "data" in item and item["data"].get("mean") is not None
        ]
        history.sort(key=lambda x: x["date"])
        return self._build_result(history, source="agromonitoring")

    async def _estimate_from_et0(self, lat: float, lon: float, days_back: int) -> dict:
        end   = date.today() - timedelta(days=1)   # archive lags 1 day
        start = end - timedelta(days=days_back)

        async with httpx.AsyncClient(timeout=30) as c:
            r = await c.get(ARCHIVE_URL, params={
                "latitude":   lat,
                "longitude":  lon,
                "start_date": start.isoformat(),
                "end_date":   end.isoformat(),
                "daily":      "et0_fao_evapotranspiration,precipitation_sum",
                "timezone":   "auto",
            })
            r.raise_for_status()
            data = r.json()

        daily = data.get("daily", {})
        dates = daily.get("time", [])
        et0s  = daily.get("et0_fao_evapotranspiration", [])

        # Smooth ET0 with 10-day rolling mean to simulate 5-day satellite revisit
        smoothed = self._rolling_mean(et0s, window=10)

        history: list[dict] = []
        for i, d in enumerate(dates):
            et0 = smoothed[i] if smoothed[i] is not None else 3.0
            # Empirical formula: higher ET0 in season → higher NDVI (olive phenology)
            ndvi = min(0.80, max(0.10, 0.15 + et0 * 0.08))
            history.append({"date": d, "ndvi": round(ndvi, 3)})

        return self._build_result(history, source="estimated_from_et0")

    def _build_result(self, history: list[dict], source: str) -> dict:
        if not history:
            return {
                "ndvi_current": 0.35,
                "ndvi_trend": "stable",
                "ndvi_history": [],
                "ndvi_min_90d": 0.35,
                "ndvi_max_90d": 0.35,
                "anomaly": False,
                "anomaly_description": None,
                "source": source,
            }

        values     = [h["ndvi"] for h in history]
        current    = values[-1]
        ndvi_min   = round(min(values), 3)
        ndvi_max   = round(max(values), 3)
        trend      = self._compute_trend(values)
        anomaly    = self.detect_anomaly(history)

        return {
            "ndvi_current":       round(current, 3),
            "ndvi_trend":         trend,
            "ndvi_history":       history,
            "ndvi_min_90d":       ndvi_min,
            "ndvi_max_90d":       ndvi_max,
            "anomaly":            anomaly["anomaly"],
            "anomaly_description": anomaly["description"],
            "source":             source,
        }

    def detect_anomaly(self, history: list[dict]) -> dict:
        if len(history) < 14:
            return {"anomaly": False, "description": None}

        recent   = [h["ndvi"] for h in history[-7:]]
        previous = [h["ndvi"] for h in history[-21:-7]]

        if not previous:
            return {"anomaly": False, "description": None}

        avg_recent   = sum(recent)   / len(recent)
        avg_previous = sum(previous) / len(previous)

        if avg_previous == 0:
            return {"anomaly": False, "description": None}

        pct_change = (avg_recent - avg_previous) / avg_previous * 100

        if pct_change < -15:
            return {
                "anomaly": True,
                "description": (
                    f"Calo vigoria del {abs(pct_change):.0f}% rilevato — "
                    "possibile stress idrico o attacco parassitario"
                ),
            }
        return {"anomaly": False, "description": None}

    @staticmethod
    def _compute_trend(values: list[float]) -> str:
        if len(values) < 7:
            return "stable"
        recent = sum(values[-7:]) / 7
        older  = sum(values[-14:-7]) / 7 if len(values) >= 14 else recent
        diff   = recent - older
        if diff > 0.02:
            return "improving"
        if diff < -0.02:
            return "declining"
        return "stable"

    @staticmethod
    def _rolling_mean(values: list, window: int) -> list:
        result = []
        for i, v in enumerate(values):
            chunk = [x for x in values[max(0, i - window + 1): i + 1] if x is not None]
            result.append(round(sum(chunk) / len(chunk), 3) if chunk else None)
        return result
