from fastapi import APIRouter, HTTPException, Query

from app.schemas.phenology import PhenologyResponse
from app.services.phenology_service import OlivePhenologyService

router = APIRouter()

_phenology_service = OlivePhenologyService()


@router.get("/current", response_model=PhenologyResponse)
async def get_current_phenology(
    lat: float = Query(..., description="Latitudine dell'azienda"),
    lon: float = Query(..., description="Longitudine dell'azienda"),
    variety: str = Query("Nocellara del Belice", description="Varietà di olivo"),
) -> dict:
    try:
        return await _phenology_service.get_current_stage(lat=lat, lon=lon, variety=variety)
    except Exception as exc:
        raise HTTPException(status_code=500, detail=str(exc)) from exc