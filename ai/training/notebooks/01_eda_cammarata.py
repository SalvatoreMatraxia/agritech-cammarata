"""
EDA — Analisi Climatica Cammarata (AG) 2010-presente
Fonte dati: Open-Meteo Archive API (ERA5-Land)
Coordinate: 37.63°N, 13.63°E — Alt. 650 m s.l.m.

Uso:
    cd ai
    python training/notebooks/01_eda_cammarata.py
"""
from __future__ import annotations

import csv
import sys
from datetime import datetime
from pathlib import Path

import httpx

LAT, LON   = 37.63, 13.63
ARCHIVE_URL = "https://archive-api.open-meteo.com/v1/archive"
VARIABLES   = (
    "temperature_2m_max,temperature_2m_min,"
    "precipitation_sum,et0_fao_evapotranspiration"
)

OUTPUT = Path(__file__).parent.parent.parent / "data" / "processed" / "training_yield.csv"


def fetch_year(year: int) -> dict:
    r = httpx.get(
        ARCHIVE_URL,
        params={
            "latitude":   LAT,
            "longitude":  LON,
            "start_date": f"{year}-01-01",
            "end_date":   f"{year}-12-31",
            "daily":      VARIABLES,
            "timezone":   "auto",
        },
        timeout=60,
    )
    r.raise_for_status()
    return r.json()


def analyze_year(data: dict, year: int) -> dict:
    d     = data.get("daily", {})
    dates = d.get("time", [])
    tmax  = d.get("temperature_2m_max", [])
    tmin  = d.get("temperature_2m_min", [])
    rain  = d.get("precipitation_sum", [])
    et0   = d.get("et0_fao_evapotranspiration", [])

    def m(s: str) -> int:
        return int(s[5:7])

    def s(v, default=0.0):
        return v if v is not None else default

    def tmean(x, n):
        return (s(x, 25) + s(n, 15)) / 2

    spring = [m(d) in (3, 4, 5) for d in dates]
    summer = [m(d) in (6, 7, 8) for d in dates]
    winter = [m(d) in (12, 1, 2) for d in dates]

    def avg_temp(mask):
        vals = [tmean(x, n) for x, n, ok in zip(tmax, tmin, mask) if ok]
        return sum(vals) / len(vals) if vals else 0.0

    n_days = max(1, len(dates))
    temp_annua = sum(tmean(x, n) for x, n in zip(tmax, tmin)) / n_days

    return {
        "anno":                 year,
        "pioggia_annua_mm":    round(sum(s(r) for r in rain), 1),
        "pioggia_primavera_mm": round(sum(s(r) for r, ok in zip(rain, spring) if ok), 1),
        "temp_media_annua":    round(temp_annua, 1),
        "temp_media_estate":   round(avg_temp(summer), 1),
        "temp_media_primavera": round(avg_temp(spring), 1),
        "et0_estate":          round(sum(s(e) for e, ok in zip(et0, summer) if ok), 1),
        "giorni_gelo":         sum(1 for n, ok in zip(tmin, winter) if ok and s(n, 5) < 0),
        "giorni_caldo":        sum(1 for x, ok in zip(tmax, summer) if ok and s(x, 25) > 35),
    }


def print_stats(rows: list[dict]) -> None:
    def col(k):
        return [r[k] for r in rows]

    print("\n" + "─" * 65)
    print(f"  STATISTICHE CLIMATICHE CAMMARATA {rows[0]['anno']}–{rows[-1]['anno']}")
    print("─" * 65)

    fields = [
        ("Temperatura media annua (°C)",     "temp_media_annua"),
        ("Temperatura media estate (°C)",     "temp_media_estate"),
        ("Temperatura media primavera (°C)",  "temp_media_primavera"),
        ("Pioggia annua (mm)",               "pioggia_annua_mm"),
        ("Pioggia primaverile (mm)",          "pioggia_primavera_mm"),
        ("ET₀ estate (mm stagione)",         "et0_estate"),
        ("Giorni gelo (dic-feb)",            "giorni_gelo"),
        ("Giorni caldo >35°C (giu-ago)",     "giorni_caldo"),
    ]
    for label, key in fields:
        vals = col(key)
        print(f"  {label:<40} media={sum(vals)/len(vals):6.1f}  "
              f"min={min(vals):5.1f}  max={max(vals):5.1f}")

    # Trend (5-yr averages)
    if len(rows) >= 10:
        recent = rows[-5:]
        older  = rows[:5]
        dr = sum(r["pioggia_annua_mm"] for r in recent) / 5 - sum(r["pioggia_annua_mm"] for r in older) / 5
        dt = sum(r["temp_media_annua"] for r in recent) / 5 - sum(r["temp_media_annua"] for r in older) / 5
        print(f"\n  Trend (ultimi 5 vs primi 5 anni):")
        print(f"    Pioggia:       {dr:+.0f} mm")
        print(f"    Temperatura:   {dt:+.2f}°C  {'↑ riscaldamento rilevato' if dt > 0.3 else ''}")

    # Anomalous years
    avg_rain = sum(col("pioggia_annua_mm")) / len(rows)
    print(f"\n  Anni anomali (pioggia < {avg_rain*0.70:.0f} mm oppure > {avg_rain*1.30:.0f} mm):")
    for r in rows:
        if r["pioggia_annua_mm"] < avg_rain * 0.70:
            print(f"    {r['anno']} — siccità grave: {r['pioggia_annua_mm']:.0f} mm")
        elif r["pioggia_annua_mm"] > avg_rain * 1.30:
            print(f"    {r['anno']} — piogge anomale: {r['pioggia_annua_mm']:.0f} mm")


def main() -> None:
    print("═" * 65)
    print("  EDA Meteo Cammarata — Open-Meteo Archive (ERA5-Land)")
    print("═" * 65)

    current_year = datetime.now().year
    years = list(range(2010, current_year))

    rows = []
    for year in years:
        print(f"  Fetching {year}...", end=" ", flush=True)
        try:
            data  = fetch_year(year)
            stats = analyze_year(data, year)
            rows.append(stats)
            print(
                f"rain={stats['pioggia_annua_mm']:.0f}mm  "
                f"T_est={stats['temp_media_estate']:.1f}°C  "
                f"gelo={stats['giorni_gelo']}d  "
                f"caldo={stats['giorni_caldo']}d"
            )
        except Exception as exc:
            print(f"ERRORE: {exc}")

    if not rows:
        print("Nessun dato recuperato. Verifica connessione.", file=sys.stderr)
        sys.exit(1)

    print_stats(rows)

    OUTPUT.parent.mkdir(parents=True, exist_ok=True)
    with open(OUTPUT, "w", newline="") as f:
        writer = csv.DictWriter(f, fieldnames=rows[0].keys())
        writer.writeheader()
        writer.writerows(rows)

    print(f"\n✓ Dataset processato salvato → {OUTPUT}")
    print(f"  {len(rows)} anni, {len(rows[0])} colonne")


if __name__ == "__main__":
    main()