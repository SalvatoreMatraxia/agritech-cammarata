"""Thin async wrapper around Open-Meteo APIs (forecast + archive)."""
from __future__ import annotations

import httpx

FORECAST_URL = "https://api.open-meteo.com/v1/forecast"
ARCHIVE_URL  = "https://archive-api.open-meteo.com/v1/archive"

_DAILY_VARS = (
    "temperature_2m_max,temperature_2m_min,"
    "precipitation_sum,et0_fao_evapotranspiration,"
    "relative_humidity_2m_mean"
)


async def fetch_current(lat: float, lon: float) -> dict:
    async with httpx.AsyncClient(timeout=30) as c:
        r = await c.get(FORECAST_URL, params={
            "latitude":  lat,
            "longitude": lon,
            "current":   "temperature_2m,relative_humidity_2m,precipitation,wind_speed_10m",
            "timezone":  "auto",
        })
        r.raise_for_status()
        return r.json()


async def fetch_forecast(lat: float, lon: float, days: int = 7) -> dict:
    async with httpx.AsyncClient(timeout=30) as c:
        r = await c.get(FORECAST_URL, params={
            "latitude":     lat,
            "longitude":    lon,
            "daily":        _DAILY_VARS,
            "forecast_days": days,
            "timezone":     "auto",
        })
        r.raise_for_status()
        return r.json()


async def fetch_historical(lat: float, lon: float, start_date: str, end_date: str) -> dict:
    async with httpx.AsyncClient(timeout=60) as c:
        r = await c.get(ARCHIVE_URL, params={
            "latitude":   lat,
            "longitude":  lon,
            "start_date": start_date,
            "end_date":   end_date,
            "daily":      _DAILY_VARS,
            "timezone":   "auto",
        })
        r.raise_for_status()
        return r.json()