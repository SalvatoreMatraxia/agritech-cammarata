from pathlib import Path

from fastapi import APIRouter

from app.schemas.prediction import HealthResponse

router = APIRouter()

MODEL_PATH = Path(__file__).parent.parent.parent / "trained_models" / "yield_xgboost.pkl"


@router.get("/health", response_model=HealthResponse)
async def health() -> HealthResponse:
    return HealthResponse(
        status="ok",
        service="agritech-ai",
        models_loaded=MODEL_PATH.exists(),
    )