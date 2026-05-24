"""
Training Ensemble — Previsione Resa Oliveto (kg/ha)
Modelli: XGBoost + Random Forest con cross-validation 5-fold

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
from sklearn.ensemble import RandomForestRegressor
from sklearn.metrics import mean_absolute_error, r2_score
from sklearn.model_selection import KFold, cross_val_score

ROOT = Path(__file__).parent.parent.parent
DATA_PATH  = ROOT / "data" / "raw" / "istat_olive_agrigento.csv"
XGB_PATH   = ROOT / "trained_models" / "yield_xgboost.pkl"
RF_PATH    = ROOT / "trained_models" / "yield_random_forest.pkl"

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


def cv_eval(model, X, y, label: str) -> tuple[float, float]:
    cv = KFold(n_splits=5, shuffle=True, random_state=42)
    r2_scores  = cross_val_score(model, X, y, cv=cv, scoring="r2")
    mae_scores = -cross_val_score(model, X, y, cv=cv, scoring="neg_mean_absolute_error")
    print(f"\n── {label} — Cross-validation 5-fold ──────────────────")
    print(f"  R²  per fold : {r2_scores.round(3).tolist()}")
    print(f"  MAE per fold : {mae_scores.round(0).tolist()} kg/ha")
    print(f"  R²  medio    : {r2_scores.mean():.3f} ± {r2_scores.std():.3f}")
    print(f"  MAE medio    : {mae_scores.mean():.0f} ± {mae_scores.std():.0f} kg/ha")
    return float(r2_scores.mean()), float(mae_scores.mean())


def main() -> None:
    print("=" * 60)
    print("  AgriTech Cammarata — Training Ensemble Resa Oliveto")
    print("=" * 60)

    if not DATA_PATH.exists():
        print(f"[ERROR] Dataset non trovato: {DATA_PATH}", file=sys.stderr)
        sys.exit(1)

    df = pd.read_csv(DATA_PATH)
    print(f"\nDataset: {len(df)} record | {DATA_PATH.name}")
    print(f"Periodo: {df['anno'].min()}–{df['anno'].max()}")
    print(f"\nStatistiche resa (kg/ha):")
    print(df[TARGET].describe().round(0).to_string())

    X = df[FEATURE_COLS].values
    y = df[TARGET].values

    xgb_model = xgb.XGBRegressor(
        n_estimators=100,
        max_depth=4,
        learning_rate=0.1,
        subsample=0.8,
        colsample_bytree=0.8,
        random_state=42,
        verbosity=0,
    )

    rf_model = RandomForestRegressor(
        n_estimators=200,
        max_depth=6,
        random_state=42,
        n_jobs=-1,
    )

    xgb_r2, xgb_mae = cv_eval(xgb_model, X, y, "XGBoost")
    rf_r2,  rf_mae  = cv_eval(rf_model,  X, y, "Random Forest")

    print("\n── Confronto performance ────────────────────────────────")
    print(f"  {'Modello':<20} {'R² medio':>10} {'MAE medio':>12}")
    print(f"  {'-'*44}")
    print(f"  {'XGBoost':<20} {xgb_r2:>10.3f} {xgb_mae:>10.0f} kg/ha")
    print(f"  {'Random Forest':<20} {rf_r2:>10.3f} {rf_mae:>10.0f} kg/ha")
    winner = "XGBoost" if xgb_r2 > rf_r2 else "Random Forest"
    print(f"\n  Modello migliore (R²): {winner}")

    print("\n── Training su dataset completo ─────────────────────────")
    xgb_model.fit(X, y)
    rf_model.fit(X, y)

    xgb_preds = xgb_model.predict(X)
    rf_preds  = rf_model.predict(X)
    ens_preds = xgb_preds * 0.6 + rf_preds * 0.4

    print(f"  XGBoost    — Train R²: {r2_score(y, xgb_preds):.3f} | MAE: {mean_absolute_error(y, xgb_preds):.0f} kg/ha")
    print(f"  RandomForest — Train R²: {r2_score(y, rf_preds):.3f} | MAE: {mean_absolute_error(y, rf_preds):.0f} kg/ha")
    print(f"  Ensemble   — Train R²: {r2_score(y, ens_preds):.3f} | MAE: {mean_absolute_error(y, ens_preds):.0f} kg/ha")

    print("\n── Feature Importance (XGBoost) ─────────────────────────")
    importances = dict(zip(FEATURE_COLS, xgb_model.feature_importances_))
    for feat, imp in sorted(importances.items(), key=lambda x: x[1], reverse=True):
        bar = "█" * max(1, int(imp * 50))
        print(f"  {feat:<28} {imp:.3f}  {bar}")

    print("\n── Campione previsioni vs reali ─────────────────────────")
    header = f"  {'Anno':>6} {'Reale':>8} {'XGB':>8} {'RF':>8} {'Ensemble':>10}"
    print(header)
    for anno, real, xp, rp, ep in zip(df["anno"][:6], y[:6], xgb_preds[:6], rf_preds[:6], ens_preds[:6]):
        print(f"  {anno:>6} {real:>8.0f} {xp:>8.0f} {rp:>8.0f} {ep:>10.0f}")

    XGB_PATH.parent.mkdir(exist_ok=True)
    joblib.dump(xgb_model, XGB_PATH)
    joblib.dump(rf_model,  RF_PATH)
    print(f"\n  XGBoost salvato    → {XGB_PATH}  ({XGB_PATH.stat().st_size / 1024:.1f} KB)")
    print(f"  RandomForest salvato → {RF_PATH}  ({RF_PATH.stat().st_size / 1024:.1f} KB)")
    print("\n✓ Training completato.")


if __name__ == "__main__":
    main()