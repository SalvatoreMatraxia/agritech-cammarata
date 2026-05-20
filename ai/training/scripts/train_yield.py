"""
Training XGBoost — Previsione Resa Oliveto (kg/ha)
Dataset: ISTAT Agrigento 2005-2024 + features meteo stagionali

Uso:
    cd ai
    python training/scripts/train_yield.py
"""
from __future__ import annotations

import sys
from pathlib import Path

import joblib
import numpy as np
import pandas as pd
import xgboost as xgb
from sklearn.metrics import mean_absolute_error, r2_score
from sklearn.model_selection import KFold, cross_val_score

ROOT = Path(__file__).parent.parent.parent
DATA_PATH  = ROOT / "data" / "raw" / "istat_olive_agrigento.csv"
MODEL_PATH = ROOT / "trained_models" / "yield_xgboost.pkl"

FEATURE_COLS = [
    "pioggia_annua_mm",
    "pioggia_primavera_mm",
    "temp_media_estate",
    "temp_media_primavera",
    "et0_estate",
    "giorni_gelo",
    "giorni_caldo",
]
TARGET = "resa_kg_ha"


def main() -> None:
    print("=" * 60)
    print("  AgriTech Cammarata — Training Modello Resa Oliveto")
    print("=" * 60)

    if not DATA_PATH.exists():
        print(f"[ERROR] Dataset non trovato: {DATA_PATH}", file=sys.stderr)
        sys.exit(1)

    df = pd.read_csv(DATA_PATH)
    print(f"\nDataset caricato: {len(df)} record | {DATA_PATH.name}")
    print(f"Periodo: {df['anno'].min()}–{df['anno'].max()}")
    print(f"\nStatistiche resa (kg/ha):")
    print(df[TARGET].describe().round(0).to_string())
    print(f"\nFeature summary:")
    print(df[FEATURE_COLS].describe().round(1).to_string())

    X = df[FEATURE_COLS].values
    y = df[TARGET].values

    model = xgb.XGBRegressor(
        n_estimators=100,
        max_depth=4,
        learning_rate=0.1,
        subsample=0.8,
        colsample_bytree=0.8,
        random_state=42,
        verbosity=0,
    )

    print("\n── Cross-validation 5-fold ──────────────────────────────")
    cv = KFold(n_splits=5, shuffle=True, random_state=42)
    r2_cv  = cross_val_score(model, X, y, cv=cv, scoring="r2")
    mae_cv = -cross_val_score(model, X, y, cv=cv, scoring="neg_mean_absolute_error")

    print(f"R²  per fold: {r2_cv.round(3).tolist()}")
    print(f"MAE per fold: {mae_cv.round(0).tolist()} kg/ha")
    print(f"\nR²  medio:   {r2_cv.mean():.3f}  ± {r2_cv.std():.3f}")
    print(f"MAE medio:   {mae_cv.mean():.0f}  ± {mae_cv.std():.0f} kg/ha")

    print("\n── Training su dataset completo ─────────────────────────")
    model.fit(X, y)
    preds    = model.predict(X)
    train_r2  = r2_score(y, preds)
    train_mae = mean_absolute_error(y, preds)
    print(f"Train R²:  {train_r2:.3f}")
    print(f"Train MAE: {train_mae:.0f} kg/ha")

    print("\n── Feature Importance ───────────────────────────────────")
    importances = dict(zip(FEATURE_COLS, model.feature_importances_))
    for feat, imp in sorted(importances.items(), key=lambda x: x[1], reverse=True):
        bar = "█" * max(1, int(imp * 50))
        print(f"  {feat:<28} {imp:.3f}  {bar}")

    print("\n── Campione previsioni vs reali ─────────────────────────")
    for anno, real, pred in zip(df["anno"][:6], y[:6], preds[:6]):
        diff = pred - real
        print(f"  {anno}: reale={real:.0f}  pred={pred:.0f}  diff={diff:+.0f} kg/ha")

    MODEL_PATH.parent.mkdir(exist_ok=True)
    joblib.dump(model, MODEL_PATH)
    print(f"\n✓ Modello salvato → {MODEL_PATH}")
    print(f"  Dimensione: {MODEL_PATH.stat().st_size / 1024:.1f} KB")


if __name__ == "__main__":
    main()