# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Commands

### Symfony (PHP backend)
```bash
# Dev server
symfony server:start --no-tls

# Clear cache (required after config changes)
php -d memory_limit=512M bin/console cache:clear

# Run fixtures (wipes DB and reloads all demo data)
php bin/console doctrine:fixtures:load --no-interaction

# Migrations
php bin/console doctrine:migrations:migrate --no-interaction
php bin/console make:migration   # after entity changes

# Console shortcuts
php bin/console debug:router     # list routes
php bin/console debug:asset-map  # list AssetMapper paths

# Add a JS/CSS package via AssetMapper (NOT npm)
php bin/console importmap:require <package>
```

### AI Microservice (FastAPI / Python)
```bash
cd ai
uvicorn app.main:app --reload --port 8001
# Health check: curl http://127.0.0.1:8001/health
# OpenAPI docs: http://127.0.0.1:8001/docs
```

### Database (Docker)
```bash
docker compose up -d   # starts PostgreSQL on port 5432
docker compose down
```

### Tests
```bash
php bin/phpunit
php bin/phpunit tests/path/to/SpecificTest.php
```

## Architecture

### Two-service system
The app is split into:
1. **Symfony 7.4** (PHP 8.3) — the web application at `localhost:8000`
2. **FastAPI** (Python) — ML microservice at `localhost:8001`; configured via `AI_SERVICE_URL` env var

Symfony calls the AI service via `AIService` (HTTP POST), which persists results as `Prediction` entities in PostgreSQL.

### Domain model
The core aggregate is `Farm → Parcel → [Treatment, CropOperation]`.
- `Farm` belongs to a `User` (one user → one farm in practice)
- `Parcel` represents a named field (e.g. "Parcella Nord"), carrying variety, tree count, surface
- `Treatment` — phytosanitary treatments (quaderno di campagna, D.Lgs 150/2012)
- `CropOperation` — typed agronomic operations (potatura, irrigazione, raccolta, …)
- `FarmSeasonRecord` — annual summary record with actual yield, weather, and phytosanitary data
- `Prediction` — ML model output stored as JSON in `result` column; `type` is `yield`, `pest_risk`, or `water_stress`

### Frontend: AssetMapper (no Webpack/Node)
JavaScript and CSS are served via Symfony AssetMapper. **Do not use npm or Webpack.**

- Entrypoints: `assets/app.js` (main app) and `assets/admin.js` (EasyAdmin)
- Add packages: `php bin/console importmap:require <package>`
- All imports are declared in `importmap.php`

JS modules live in `assets/js/`:
- `charts/chart-setup.js` — single Chart.js registration point (`Chart.register(...registerables)`); all chart files import from here
- `charts/weather-chart.js`, `charts/phenology-timeline.js`, `charts/memory-chart.js`, `charts/memory-charts.js` — feature-specific Chart.js charts
- `js/map/ndvi-map.js` — Leaflet NDVI map
- `js/navbar-mobile.js` — hamburger menu
- `js/treatment-form.js` — treatment form dynamic behavior

All JS is Turbo-safe: always register both `DOMContentLoaded` **and** `turbo:load` events.

### CSS design system
All CSS lives in `assets/styles/`. `app.css` imports all components via `@import`.

**Design tokens** are CSS custom properties in `:root` inside `app.css`. All colors must use tokens — no hardcoded hex values in component files. New colors go into `:root` first, then reference them.

Dynamic values (e.g. bar widths) are passed via CSS custom properties only: `style="--bar-w: {{ value }}%"`. **No other `style=""` attributes, no `<style>` blocks, no `<script>` blocks in Twig templates** (sole exception: `treatment/pdf.html.twig` for Dompdf PDF export).

### Twig conventions
- `{{ icon('icon-name', 'css-classes') }}` — renders an inline SVG via `IconExtension`. Icons are hand-coded Lucide paths in `src/Twig/IconExtension.php`; add new ones there.
- Dates display in `Europe/Rome` timezone via `twig.date.timezone` (configured in `config/packages/twig.yaml`)
- Clickable table rows: add `data-href="{{ path('route', {id: x}) }}"` to `<tr>`; handled globally by `initClickableRows()` in `memory-charts.js`

### Admin panel
EasyAdmin 5.x at `/admin`. CRUD controllers in `src/Controller/Admin/`. Access requires `ROLE_ADMIN`.

### DQL limitations (PostgreSQL + Doctrine)
- `YEAR()` is **not** a valid DQL function — use date range: `>= new \DateTimeImmutable("$year-01-01")` and `< new \DateTimeImmutable(($year+1)."-01-01")`
- `Prediction.result` is a JSON column; the dashboard template expects keys `yield_kg_ha`, `oil_liters`, and `scenarios.pessimistico/medio/ottimistico`

### AI microservice endpoints
- `POST /predict/yield` → yield prediction (XGBoost)
- `POST /predict/pest` → pest risk (biological degree-day model)
- `POST /predict/water-stress` → FAO-56 water balance
- `GET /satellite/ndvi` → mock NDVI data
- `GET /phenology/{variety}` → phenological stage for a variety