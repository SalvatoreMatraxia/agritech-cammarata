"""
Modello a stadi biologici — Bactrocera oleae (Mosca dell'olivo).

Parametri biologici validati:
  T base sviluppo : 9°C
  Degree-days per stadio:
    uovo   65 DD
    larva 170 DD  (L1+L2+L3)
    pupa  185 DD
    totale generazione: 420 DD
  Mortalità da calore: T_max > 35°C riduce accumulo DD del 50%
  3-5 generazioni/anno in Sicilia
"""
from __future__ import annotations

from datetime import datetime, timedelta

from .weather_fetcher import fetch_historical, fetch_forecast

# ── Biological constants ───────────────────────────────────────────────────────
BASE_T      = 9.0    # °C
DD_EGG      = 65
DD_LARVA    = 170
DD_PUPA     = 185
DD_GEN      = DD_EGG + DD_LARVA + DD_PUPA   # 420 DD

LETHAL_T    = 35.0   # °C — above this, development slows
OPT_T_MIN   = 20.0
OPT_T_MAX   = 30.0
FAV_HUMIDITY = 60.0


class PestPredictor:

    async def predict(self, lat: float, lon: float, farm_surface_ha: float = 1.0) -> dict:
        today = datetime.today()
        hist_start = (today - timedelta(days=60)).strftime("%Y-%m-%d")
        hist_end   = today.strftime("%Y-%m-%d")

        try:
            hist      = await fetch_historical(lat, lon, hist_start, hist_end)
            hist_d    = hist.get("daily", {})
        except Exception:
            hist_d = {}

        try:
            fc        = await fetch_forecast(lat, lon, days=7)
            fc_d      = fc.get("daily", {})
        except Exception:
            fc_d = {}

        all_dates = hist_d.get("time", []) + fc_d.get("time", [])
        all_tmax  = hist_d.get("temperature_2m_max",  []) + fc_d.get("temperature_2m_max",  [])
        all_tmin  = hist_d.get("temperature_2m_min",  []) + fc_d.get("temperature_2m_min",  [])
        all_hum   = hist_d.get("relative_humidity_2m_mean", []) + fc_d.get("relative_humidity_2m_mean", [])

        hist_len  = len(hist_d.get("time", []))

        # Season start: first day of sustained T_mean > 15°C (3 consecutive days)
        season_idx = self._season_start(all_tmax, all_tmin)

        # Cumulative DD from season start to today
        dd_today    = self._acc_dd(all_tmax, all_tmin, season_idx, hist_len)
        dd_forecast = self._acc_dd(all_tmax, all_tmin, hist_len, len(all_dates))

        gen_full, dd_in_gen = divmod(dd_today, DD_GEN)
        generation_n = max(1, int(gen_full) + 1)
        stage = self._stage(dd_in_gen)

        # Current conditions (last available day)
        idx = hist_len - 1 if hist_len > 0 else 0
        cur_tmax = self._safe(all_tmax, idx, 25.0)
        cur_tmin = self._safe(all_tmin, idx, 15.0)
        cur_hum  = self._safe(all_hum,  idx, 60.0)
        cur_tmean = (cur_tmax + cur_tmin) / 2

        # Heat mortality in 7-day forecast
        lethal_days = sum(
            1 for t in fc_d.get("temperature_2m_max", [])
            if t is not None and t > LETHAL_T
        )
        mortalita_da_caldo = lethal_days >= 2

        risk = self._risk_score(stage, cur_tmean, cur_hum, generation_n, today, mortalita_da_caldo)

        # Treatment window: time until next adult stage
        dd_remaining = DD_GEN - dd_in_gen
        days_to_adult = max(1, int(dd_remaining / max(1, dd_forecast / 7))) if dd_forecast > 0 else int(dd_remaining / 8)
        tw_start = (today + timedelta(days=days_to_adult)).strftime("%Y-%m-%d")
        tw_end   = (today + timedelta(days=days_to_adult + 10)).strftime("%Y-%m-%d")

        # Estimated adult population (adults/ha)
        stage_factor = {"adulto": 1.0, "larva": 0.4, "uovo": 0.2, "pupa": 0.05}
        gen_peak = {1: 0.3, 2: 0.6, 3: 1.0, 4: 0.8, 5: 0.45}
        pop = int(1000 * stage_factor.get(stage, 0.3) * gen_peak.get(min(generation_n, 5), 0.3) * farm_surface_ha)

        risk_level = ("critico" if risk >= 0.75 else
                      "alto"    if risk >= 0.50 else
                      "medio"   if risk >= 0.25 else "basso")

        return {
            "risk_score":                round(risk, 3),
            "risk_level":                risk_level,
            "fase_corrente":             stage,
            "generazione_n":             generation_n,
            "popolazione_adulti_stimata": pop,
            "finestra_trattamento":      {"inizio": tw_start, "fine": tw_end},
            "mortalita_da_caldo":        mortalita_da_caldo,
            "recommendations":           self._recommendations(risk_level, stage, mortalita_da_caldo, generation_n),
            "economic_impact":           self._economic(risk, farm_surface_ha),
            "explanation_text":          self._explain(risk, cur_tmean, cur_hum, stage, generation_n, mortalita_da_caldo, dd_today),
            "confidence":                0.82 if (hist_d and fc_d) else 0.50,
        }

    # ── helpers ───────────────────────────────────────────────────────────────

    @staticmethod
    def _safe(lst: list, idx: int, default: float) -> float:
        v = lst[idx] if 0 <= idx < len(lst) else None
        return v if v is not None else default

    @staticmethod
    def _season_start(tmax: list, tmin: list, streak: int = 3) -> int:
        for i in range(len(tmax) - streak):
            if all(
                ((tmax[i + j] or 0) + (tmin[i + j] or 0)) / 2 >= 15
                for j in range(streak)
            ):
                return i
        return 0

    @staticmethod
    def _acc_dd(tmax: list, tmin: list, start: int, end: int) -> float:
        total = 0.0
        for i in range(start, min(end, len(tmax))):
            tx = tmax[i] if tmax[i] is not None else 25
            tn = tmin[i] if tmin[i] is not None else 15
            tmean = (tx + tn) / 2
            dd = max(0.0, tmean - BASE_T)
            if tx > LETHAL_T:
                dd *= 0.5   # thermal mortality: slower development
            total += dd
        return total

    @staticmethod
    def _stage(dd_in_gen: float) -> str:
        if dd_in_gen < DD_EGG:
            return "uovo"
        if dd_in_gen < DD_EGG + DD_LARVA:
            return "larva"
        if dd_in_gen < DD_GEN:
            return "pupa"
        return "adulto"

    @staticmethod
    def _risk_score(
        stage: str, tmean: float, hum: float,
        gen_n: int, today: datetime, mortalita: bool
    ) -> float:
        if mortalita:
            return max(0.0, 0.35 - 0.05 * (gen_n - 1))

        # Temperature suitability [0,1]
        if tmean < 12 or tmean > LETHAL_T:
            t_fac = 0.0
        elif OPT_T_MIN <= tmean <= OPT_T_MAX:
            t_fac = 1.0
        elif tmean < OPT_T_MIN:
            t_fac = (tmean - 12) / (OPT_T_MIN - 12)
        else:
            t_fac = 1.0 - (tmean - OPT_T_MAX) / (LETHAL_T - OPT_T_MAX)

        hum_fac   = min(1.0, max(0.3, hum / FAV_HUMIDITY))
        stage_fac = {"adulto": 1.0, "uovo": 0.70, "larva": 0.80, "pupa": 0.25}.get(stage, 0.5)
        gen_fac   = {1: 0.4, 2: 0.7, 3: 1.0, 4: 0.8, 5: 0.45}.get(min(gen_n, 5), 0.5)

        # Phenological window: fruits at risk May-October
        m = today.month
        phenol = 0.05 if m in (1, 2, 3, 12) else (0.5 if m == 4 else 1.0)

        return min(1.0, max(0.0, t_fac * hum_fac * stage_fac * gen_fac * phenol))

    @staticmethod
    def _recommendations(risk_level: str, stage: str, mortalita: bool, gen_n: int) -> list[str]:
        recs: list[str] = []
        if mortalita:
            recs.append("Ondata di calore prevista: mortalità naturale attesa — rimandare trattamenti di 7-10 giorni")
        if risk_level in ("alto", "critico"):
            recs.append("Monitorare trappole cromotropiche gialle ogni 2-3 giorni")
            recs.append("Considerare trattamento attract-and-kill (proteina idrolizzata + spinosad — ammesso in bio)")
            if gen_n >= 3:
                recs.append("Generazione 3 in corso: massimo rischio danno drupe — intervenire entro 5 giorni")
        elif risk_level == "medio":
            recs.append("Monitorare trappole settimanalmente")
            recs.append("Soglia intervento: > 3 adulti/trappola/giorno per 3 giorni consecutivi")
        else:
            recs.append("Rischio basso: monitoraggio ordinario (1 ispezione/settimana)")
        if stage == "adulto":
            recs.append("Fase adulto rilevata: finestra ottimale per trattamento attract-and-kill aperta")
        return recs

    @staticmethod
    def _economic(risk: float, surface_ha: float) -> dict:
        cost_ha = 70.0  # €/ha — proteina idrolizzata + spinosad
        loss_pct = risk * 0.40
        loss_kg  = 3000 * loss_pct
        # drupe €0.25/kg + olio (18%) €6/L ÷ 0.917 kg/L
        loss_eur_ha = loss_kg * 0.25 + (loss_kg * 0.18 / 0.917) * 6.0
        ratio = round(loss_eur_ha / max(1.0, cost_ha), 2)
        return {
            "costo_trattamento_eur_ha": cost_ha,
            "perdita_stimata_eur_ha":   round(loss_eur_ha, 2),
            "rapporto_costi_benefici":  ratio,
        }

    @staticmethod
    def _explain(
        risk: float, tmean: float, hum: float,
        stage: str, gen_n: int, mortalita: bool, dd_total: float
    ) -> str:
        pct = int(risk * 100)
        cond = [
            f"T {tmean:.0f}°C ({'favorevole' if OPT_T_MIN <= tmean <= OPT_T_MAX else 'non ottimale'})",
            f"umidità {hum:.0f}% ({'favorevole' if hum > FAV_HUMIDITY else 'limitante'})",
            f"gen.{gen_n} in fase {stage}",
        ]
        if mortalita:
            cond.append("mortalità da caldo prevista → rischio ridotto")
        conf = 70 if mortalita else 82
        return (
            f"Rischio mosca {pct}% — {', '.join(cond)}. "
            f"DD accumulati dalla ripresa vegetativa: {dd_total:.0f}. "
            f"Confidenza {conf}%."
        )