"""
Olive Phenological Model — GDD (Growing Degree Days) based.

Base temperature: 7 °C (validated for Mediterranean olive; Orlandi et al. 2004,
Bonofiglio et al. 2008).  DD accumulation starts on 1 January of the current year.
Daily temperatures are fetched from Open-Meteo archive API.
"""

from __future__ import annotations

import httpx
from datetime import date, timedelta
from typing import Optional

# ---------------------------------------------------------------------------
# Variety parameters (10 main Sicilian cultivars)
# ---------------------------------------------------------------------------
VARIETY_PARAMS: dict[str, dict[str, float]] = {
    "Nocellara del Belice": {"dd_flowering": 380, "dd_veraison": 1500, "dd_maturation": 1900},
    "Biancolilla":          {"dd_flowering": 350, "dd_veraison": 1400, "dd_maturation": 1750},
    "Cerasuola":            {"dd_flowering": 370, "dd_veraison": 1450, "dd_maturation": 1850},
    "Moresca":              {"dd_flowering": 360, "dd_veraison": 1420, "dd_maturation": 1800},
    "Tonda Iblea":          {"dd_flowering": 380, "dd_veraison": 1500, "dd_maturation": 1900},
    "Ogliarola Messinese":  {"dd_flowering": 370, "dd_veraison": 1480, "dd_maturation": 1880},
    "Carolea":              {"dd_flowering": 400, "dd_veraison": 1550, "dd_maturation": 1950},
    "Nocellara Etnea":      {"dd_flowering": 390, "dd_veraison": 1520, "dd_maturation": 1920},
    "Santagatese":          {"dd_flowering": 360, "dd_veraison": 1400, "dd_maturation": 1780},
    "Verdello":             {"dd_flowering": 350, "dd_veraison": 1380, "dd_maturation": 1750},
}

DEFAULT_VARIETY = "Nocellara del Belice"
BASE_TEMP = 7.0  # °C

# Stage-level context (not variety-specific)
_PHENOLOGY_CONTEXT: dict[str, str] = {
    "DORMANCY":     "L'olivo è in dormienza invernale. Fase ideale per la potatura e i lavori di sistemazione.",
    "BUD_BREAK":    "Risveglio vegetativo in corso — compaiono le prime gemme fogliari. Condizioni favorevoli alla ripresa vegetativa.",
    "INFLORESCENCE":"Mignolatura attiva: i grappoli fiorali sono visibili. Monitorare le condizioni meteo per la fioritura.",
    "FLOWERING":    "FASE CRITICA — Fioritura attiva. Temperatura ottimale 18–22 °C. Evitare trattamenti rameici o antiparassitari volatili.",
    "FRUIT_SET":    "Allegagione in corso: i piccoli frutti si stanno formando. Fase sensibile allo stress idrico e nutrizionale.",
    "FRUIT_GROWTH": "Accrescimento della drupa in corso. Le olive aumentano rapidamente di dimensione. Fase critica per l'approvvigionamento idrico.",
    "VERAISON":     "Invaiatura — le olive stanno cambiando colore. Inizia la fase critica per la mosca dell'olivo. Aumentare la frequenza di monitoraggio.",
    "MATURATION":   "Maturazione in corso. Valutare indice di maturazione per definire la finestra ottimale di raccolta.",
    "HARVEST":      "Finestra di raccolta ottimale. Raccogliere tempestivamente per massimizzare qualità e resa in olio.",
    "POST_HARVEST": "Post-raccolta. Periodo ideale per concimazione organica e potatura invernale.",
}

_PEST_CONTEXT: dict[str, str] = {
    "DORMANCY":     "Nessuna attività della mosca olearia. Tignola in dormienza.",
    "BUD_BREAK":    "Nessuna minaccia da mosca. Tignola: prima generazione fillofaga in preparazione.",
    "INFLORESCENCE":"Tignola antofaga (attacca i fiori) — installare trappole di monitoraggio.",
    "FLOWERING":    "Tignola antofaga attiva sui fiori — controllare soglia d'intervento. Mosca olearia assente.",
    "FRUIT_SET":    "Tignola carpofaga (prima generazione) può attaccare i frutti appena formati.",
    "FRUIT_GROWTH": "Tignola carpofaga attiva. Mosca olearia non ancora pericolosa — olive non idonee all'ovideposizione.",
    "VERAISON":     "ALLERTA MOSCA OLEARIA — Bactrocera oleae attiva, olive idonee all'ovideposizione. Intervenire se soglia >5% frutti.",
    "MATURATION":   "Rischio mosca massimo. Monitorare trappole ogni 3 giorni. Soglia d'intervento: 10% frutti con punture.",
    "HARVEST":      "Raccogliere rapidamente se pressione mosca elevata. Trattamento con caolino se necessario.",
    "POST_HARVEST": "Nessuna attività fitosanitaria richiesta. Rimuovere olive a terra (serbatoio moscas).",
}

_IRRIGATION_CRITICAL_STAGES = {"FRUIT_SET", "FRUIT_GROWTH", "VERAISON"}


# ---------------------------------------------------------------------------
# Helpers
# ---------------------------------------------------------------------------

def _build_stages(params: dict[str, float]) -> list[dict]:
    dd_f = params["dd_flowering"]
    dd_v = params["dd_veraison"]
    dd_m = params["dd_maturation"]
    return [
        {"stage": "DORMANCY",      "label_it": "Dormienza",            "dd_start": 0},
        {"stage": "BUD_BREAK",     "label_it": "Risveglio",            "dd_start": 50},
        {"stage": "INFLORESCENCE", "label_it": "Mignolatura",          "dd_start": 200},
        {"stage": "FLOWERING",     "label_it": "Fioritura",            "dd_start": int(dd_f)},
        {"stage": "FRUIT_SET",     "label_it": "Allegagione",          "dd_start": int(dd_f + 200)},
        {"stage": "FRUIT_GROWTH",  "label_it": "Accrescimento drupa",  "dd_start": int(dd_f + 400)},
        {"stage": "VERAISON",      "label_it": "Invaiatura",           "dd_start": int(dd_v)},
        {"stage": "MATURATION",    "label_it": "Maturazione",          "dd_start": int(dd_m)},
        {"stage": "HARVEST",       "label_it": "Raccolta",             "dd_start": int(dd_m + 100)},
        {"stage": "POST_HARVEST",  "label_it": "Post-raccolta",        "dd_start": int(dd_m + 500)},
    ]


def _find_current_stage_idx(dd_total: float, stages: list[dict]) -> int:
    idx = 0
    for i, s in enumerate(stages):
        if dd_total >= s["dd_start"]:
            idx = i
    return idx


def _find_crossing_date(
    dates: list[date],
    cumulative: list[float],
    threshold: float,
) -> Optional[str]:
    for d, c in zip(dates, cumulative):
        if c >= threshold:
            return d.isoformat()
    return None


# ---------------------------------------------------------------------------
# Main service
# ---------------------------------------------------------------------------

class OlivePhenologyService:

    async def get_current_stage(
        self,
        lat: float,
        lon: float,
        variety: str,
        *,
        start_date: Optional[str] = None,
    ) -> dict:
        variety_warning: Optional[str] = None
        params = VARIETY_PARAMS.get(variety)
        if params is None:
            variety_warning = (
                f"Varietà '{variety}' non in database — usando parametri di {DEFAULT_VARIETY}."
            )
            params = VARIETY_PARAMS[DEFAULT_VARIETY]
            variety = DEFAULT_VARIETY

        stages = _build_stages(params)

        # Fetch temperatures
        year = date.today().year
        t_start = date.fromisoformat(start_date) if start_date else date(year, 1, 1)
        t_end   = date.today() - timedelta(days=1)  # archive is safe to yesterday

        dates, daily_dd, cumulative_dd = await self._fetch_degree_days(lat, lon, t_start, t_end)

        dd_total = cumulative_dd[-1] if cumulative_dd else 0.0

        # Recent DD accumulation rate (last 14 days)
        recent = daily_dd[-14:] if len(daily_dd) >= 14 else daily_dd
        rate   = sum(recent) / len(recent) if recent else 0.5
        rate   = max(rate, 0.1)  # floor to avoid division by zero in winter

        # Current stage
        current_idx   = _find_current_stage_idx(dd_total, stages)
        current_stage = stages[current_idx]

        # Next stage
        next_stage_obj: Optional[dict] = stages[current_idx + 1] if current_idx + 1 < len(stages) else None
        dd_to_next:     Optional[float] = None
        days_to_next:   Optional[int]   = None
        next_date_str:  Optional[str]   = None

        if next_stage_obj:
            dd_to_next = next_stage_obj["dd_start"] - dd_total
            days_raw   = dd_to_next / rate
            days_to_next  = max(1, int(days_raw))
            next_date_str = (date.today() + timedelta(days=days_to_next)).isoformat()

        # Harvest window
        dd_m       = params["dd_maturation"]
        hw         = self._harvest_window(dd_total, dd_m, rate)

        # Build all_stages
        all_stages = []
        for i, s in enumerate(stages):
            threshold = s["dd_start"]
            if i < current_idx:
                status       = "completed"
                actual_date  = _find_crossing_date(dates, cumulative_dd, threshold)
                est_date     = None
            elif i == current_idx:
                status       = "current"
                actual_date  = _find_crossing_date(dates, cumulative_dd, threshold)
                est_date     = None
            else:
                status       = "upcoming"
                actual_date  = None
                dd_needed    = threshold - dd_total
                days_needed  = max(1, int(dd_needed / rate))
                est_date     = (date.today() + timedelta(days=days_needed)).isoformat()

            all_stages.append({
                "stage":          s["stage"],
                "label_it":       s["label_it"],
                "dd_start":       threshold,
                "status":         status,
                "actual_date":    actual_date,
                "estimated_date": est_date,
            })

        current_name = current_stage["stage"]
        return {
            "variety":                  variety,
            "current_stage":            current_name,
            "stage_label_it":           current_stage["label_it"],
            "degree_days_cumulated":    round(dd_total, 1),
            "dd_to_next_stage":         round(dd_to_next, 1) if dd_to_next is not None else None,
            "next_stage":               next_stage_obj["stage"]    if next_stage_obj else None,
            "next_stage_label_it":      next_stage_obj["label_it"] if next_stage_obj else None,
            "estimated_next_stage_date": next_date_str,
            "days_to_next_stage":       days_to_next,
            "all_stages":               all_stages,
            "phenology_context":        _PHENOLOGY_CONTEXT.get(current_name, ""),
            "agronomic_implications": {
                "irrigation_critical": current_name in _IRRIGATION_CRITICAL_STAGES,
                "pest_risk_context":   _PEST_CONTEXT.get(current_name, ""),
                "harvest_window":      hw,
            },
            "variety_warning": variety_warning,
        }

    # -----------------------------------------------------------------------

    async def _fetch_degree_days(
        self,
        lat: float,
        lon: float,
        start: date,
        end: date,
    ) -> tuple[list[date], list[float], list[float]]:
        async with httpx.AsyncClient(timeout=20) as client:
            resp = await client.get(
                "https://archive-api.open-meteo.com/v1/archive",
                params={
                    "latitude":  lat,
                    "longitude": lon,
                    "start_date": start.isoformat(),
                    "end_date":   end.isoformat(),
                    "daily":     "temperature_2m_mean",
                    "timezone":  "auto",
                },
            )
            resp.raise_for_status()
            data = resp.json()

        daily      = data.get("daily", {})
        dates_str  = daily.get("time", [])
        temps_raw  = daily.get("temperature_2m_mean", [])

        dates = [date.fromisoformat(d) for d in dates_str]
        temps = [float(t) if t is not None else BASE_TEMP for t in temps_raw]

        daily_dd   = [max(0.0, t - BASE_TEMP) for t in temps]
        cumulative = []
        total = 0.0
        for dd in daily_dd:
            total += dd
            cumulative.append(round(total, 2))

        return dates, daily_dd, cumulative

    @staticmethod
    def _harvest_window(dd_total: float, dd_maturation: float, rate: float) -> dict:
        if dd_total >= dd_maturation:
            base = date.today()
        else:
            days = max(1, int((dd_maturation - dd_total) / rate))
            base = date.today() + timedelta(days=days)

        return {
            "earliest": base.isoformat(),
            "optimal":  (base + timedelta(days=14)).isoformat(),
            "latest":   (base + timedelta(days=60)).isoformat(),
        }