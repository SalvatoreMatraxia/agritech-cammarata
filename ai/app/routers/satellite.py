from fastapi import APIRouter, HTTPException, Query

from app.services.satellite_fetcher import SatelliteFetcher

router = APIRouter()

_fetcher = SatelliteFetcher()


@router.get("/ndvi")
async def get_ndvi(
    lat:  float = Query(..., ge=-90,  le=90),
    lon:  float = Query(..., ge=-180, le=180),
    days: int   = Query(default=90,   ge=7, le=365),
) -> dict:
    try:
        return await _fetcher.fetch_ndvi(lat, lon, days)
    except Exception as exc:
        raise HTTPException(status_code=500, detail=str(exc)) from exc