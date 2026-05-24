from __future__ import annotations

from datetime import date
from typing import Any, Dict, List, Optional

from pydantic import BaseModel, Field


# ── YIELD ─────────────────────────────────────────────────────────────────────

class YieldRequest(BaseModel):
    latitude: float = Field(..., ge=-90, le=90)
    longitude: float = Field(..., ge=-180, le=180)
    surface_ha: float = Field(..., gt=0)
    tree_count: int = Field(..., gt=0)
    variety: str
    year: int = Field(default_factory=lambda: date.today().year)
    planting_year: Optional[int] = None          # anno impianto → fattore età
    irrigation_type: str = Field(default="asciutto")  # asciutto | irriguo | agrivoltaico
    prev_year_actual_kg: Optional[float] = None  # resa effettiva anno precedente (kg totali azienda)
    farm_avg_kg: Optional[float] = None          # media resa anni storici (kg totali azienda)


class YieldScenario(BaseModel):
    yield_kg_ha: float
    total_kg: float
    oil_liters: float
    probability: str


class YieldResponse(BaseModel):
    yield_kg_ha: float
    total_kg: float
    oil_liters: float
    confidence: float
    method: str
    age_factor: float
    alternance_factor: float
    scenarios: Dict[str, YieldScenario]
    explanation_text: str
    features_used: Dict[str, Any]
    xgboost_prediction: Optional[float] = None
    random_forest_prediction: Optional[float] = None
    divergence_pct: Optional[float] = None


# ── PEST ──────────────────────────────────────────────────────────────────────

class PestRequest(BaseModel):
    latitude: float = Field(..., ge=-90, le=90)
    longitude: float = Field(..., ge=-180, le=180)
    farm_surface_ha: float = Field(default=1.0, gt=0)


class TreatmentWindow(BaseModel):
    inizio: str
    fine: str


class PestEconomicImpact(BaseModel):
    costo_trattamento_eur_ha: float
    perdita_stimata_eur_ha: float
    rapporto_costi_benefici: float


class PestResponse(BaseModel):
    risk_score: float
    risk_level: str
    fase_corrente: str
    generazione_n: int
    popolazione_adulti_stimata: int
    finestra_trattamento: TreatmentWindow
    mortalita_da_caldo: bool
    recommendations: List[str]
    economic_impact: PestEconomicImpact
    explanation_text: str
    confidence: float


# ── WATER STRESS ──────────────────────────────────────────────────────────────

class WaterRequest(BaseModel):
    latitude: float = Field(..., ge=-90, le=90)
    longitude: float = Field(..., ge=-180, le=180)
    soil_type: str = Field(default="clay_loam")
    surface_ha: float = Field(default=1.0, gt=0)


class DailyBalance(BaseModel):
    date: str
    rain_mm: float
    et0_mm: float
    et_c_mm: float
    sw_mm: float
    stress_ks: float


class WaterEconomicImpact(BaseModel):
    costo_acqua_eur: float
    perdita_stimata_eur: float
    rapporto_costi_benefici: float


class WaterResponse(BaseModel):
    stress_index: float
    water_balance_mm: float
    reserve_pct: float
    irrigation_needed: bool
    volume_mc_per_ha: float
    economic_impact: WaterEconomicImpact
    explanation_text: str
    daily_balance: List[DailyBalance]


# ── HEALTH ────────────────────────────────────────────────────────────────────

class HealthResponse(BaseModel):
    status: str
    service: str
    models_loaded: bool
    version: str = "1.0.0"