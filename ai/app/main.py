from fastapi import FastAPI
from fastapi.middleware.cors import CORSMiddleware

from app.routers import health, predictions

app = FastAPI(
    title="AgriTech Cammarata DSS — AI Service",
    description=(
        "Microservizio ML per DSS olivicoltura biologica mediterranea. "
        "Previsione resa (XGBoost), rischio mosca olearia (modello stadi biologici), "
        "bilancio idrico FAO-56."
    ),
    version="1.0.0",
    docs_url="/docs",
    redoc_url="/redoc",
)

app.add_middleware(
    CORSMiddleware,
    allow_origins=[
        "http://localhost:8000",
        "https://localhost:8000",
        "https://127.0.0.1:8000",
        "http://127.0.0.1:8000",
    ],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

app.include_router(health.router, tags=["health"])
app.include_router(predictions.router, prefix="/predict", tags=["predictions"])