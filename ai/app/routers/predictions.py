from fastapi import APIRouter, HTTPException

from app.schemas.prediction import (
    PestRequest,
    PestResponse,
    WaterRequest,
    WaterResponse,
    YieldRequest,
    YieldResponse,
)
from app.services.pest_service import PestPredictor
from app.services.water_service import WaterStressCalculator
from app.services.yield_service import YieldPredictor

router = APIRouter()

_yield_predictor = YieldPredictor()
_pest_predictor  = PestPredictor()
_water_calc      = WaterStressCalculator()


@router.post("/yield", response_model=YieldResponse)
async def predict_yield(req: YieldRequest) -> dict:
    try:
        return await _yield_predictor.predict(
            lat=req.latitude,
            lon=req.longitude,
            surface_ha=req.surface_ha,
            tree_count=req.tree_count,
            variety=req.variety,
            year=req.year,
            planting_year=req.planting_year,
            irrigation_type=req.irrigation_type,
            prev_year_actual_kg=req.prev_year_actual_kg,
            farm_avg_kg=req.farm_avg_kg,
        )
    except Exception as exc:
        raise HTTPException(status_code=500, detail=str(exc)) from exc


@router.post("/pest", response_model=PestResponse)
async def predict_pest(req: PestRequest) -> dict:
    try:
        return await _pest_predictor.predict(
            lat=req.latitude,
            lon=req.longitude,
            farm_surface_ha=req.farm_surface_ha,
        )
    except Exception as exc:
        raise HTTPException(status_code=500, detail=str(exc)) from exc


@router.post("/water-stress", response_model=WaterResponse)
async def predict_water_stress(req: WaterRequest) -> dict:
    try:
        return await _water_calc.calculate(
            lat=req.latitude,
            lon=req.longitude,
            soil_type=req.soil_type,
            surface_ha=req.surface_ha,
        )
    except Exception as exc:
        raise HTTPException(status_code=500, detail=str(exc)) from exc