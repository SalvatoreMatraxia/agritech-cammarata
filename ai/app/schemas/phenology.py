from __future__ import annotations

from typing import List, Optional

from pydantic import BaseModel


class StageInfo(BaseModel):
    stage: str
    label_it: str
    dd_start: int
    status: str  # "completed" | "current" | "upcoming"
    actual_date: Optional[str] = None
    estimated_date: Optional[str] = None


class HarvestWindow(BaseModel):
    earliest: Optional[str] = None
    optimal: Optional[str] = None
    latest: Optional[str] = None


class AgronomicImplications(BaseModel):
    irrigation_critical: bool
    pest_risk_context: str
    harvest_window: HarvestWindow


class PhenologyResponse(BaseModel):
    variety: str
    current_stage: str
    stage_label_it: str
    degree_days_cumulated: float
    dd_to_next_stage: Optional[float] = None
    next_stage: Optional[str] = None
    next_stage_label_it: Optional[str] = None
    estimated_next_stage_date: Optional[str] = None
    days_to_next_stage: Optional[int] = None
    all_stages: List[StageInfo]
    phenology_context: str
    agronomic_implications: AgronomicImplications
    variety_warning: Optional[str] = None