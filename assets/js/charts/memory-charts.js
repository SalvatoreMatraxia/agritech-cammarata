import { Chart } from './chart-setup.js';

// ── BAR CHART: previsto vs reale ─────────────────────────────────────────────
function initBarCharts() {
    document.querySelectorAll('canvas[data-chart="memory-bar"]').forEach(canvas => {
        if (canvas._agritechChart) return;

        const style     = getComputedStyle(document.documentElement);
        const colorPrim = style.getPropertyValue('--color-primary').trim()   || '#2D5016';
        const colorSec  = style.getPropertyValue('--color-secondary').trim() || '#4A7C2F';

        const predicted = parseFloat(canvas.dataset.predicted || '0');
        const actual    = parseFloat(canvas.dataset.actual    || '0');
        const year      = canvas.dataset.year || '';

        canvas._agritechChart = new Chart(canvas, {
            type: 'bar',
            data: {
                labels: [year],
                datasets: [
                    {
                        label: 'Resa prevista (kg)',
                        data: [predicted],
                        backgroundColor: 'rgba(74,124,47,0.45)',
                        borderColor: colorSec,
                        borderWidth: 2,
                        borderRadius: 4,
                    },
                    {
                        label: 'Resa reale (kg)',
                        data: [actual],
                        backgroundColor: 'rgba(45,80,22,0.75)',
                        borderColor: colorPrim,
                        borderWidth: 2,
                        borderRadius: 4,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'top', labels: { boxWidth: 14, font: { size: 12 } } },
                    tooltip: {
                        callbacks: {
                            label: ctx => ' ' + ctx.dataset.label + ': ' + ctx.parsed.y.toLocaleString('it') + ' kg',
                        },
                    },
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { callback: v => v.toLocaleString('it') + ' kg' },
                    },
                },
            },
        });
    });
}

// ── RADAR CHART: parametri meteo ─────────────────────────────────────────────
// Each axis normalised to 0–10 so the shape is meaningful
const WEATHER_MAX = {
    temp_avg_spring: 30,  // 30°C → 10
    temp_avg_summer: 40,  // 40°C → 10
    rain_total:      800, // 800 mm → 10
    frost_days:      20,  // 20 giorni → 10
    heat_days:       50,  // 50 giorni → 10
};

function normalise(value, key) {
    const max = WEATHER_MAX[key] ?? 1;
    return +Math.min((value / max) * 10, 10).toFixed(1);
}

function initRadarCharts() {
    document.querySelectorAll('canvas[data-chart="memory-radar"]').forEach(canvas => {
        if (canvas._agritechChart) return;

        let weather = {};
        try { weather = JSON.parse(canvas.dataset.weather || '{}'); } catch { return; }

        const style     = getComputedStyle(document.documentElement);
        const colorPrim = style.getPropertyValue('--color-primary').trim() || '#2D5016';

        const keys   = ['temp_avg_spring', 'temp_avg_summer', 'rain_total', 'frost_days', 'heat_days'];
        const labels = ['Temp. primavera', 'Temp. estate', 'Pioggia', 'Giorni gelo', 'Caldo estremo'];
        const data   = keys.map(k => normalise(weather[k] ?? 0, k));

        canvas._agritechChart = new Chart(canvas, {
            type: 'radar',
            data: {
                labels,
                datasets: [{
                    label: 'Indici meteo (scala 0-10)',
                    data,
                    backgroundColor: 'rgba(45,80,22,0.15)',
                    borderColor: colorPrim,
                    borderWidth: 2,
                    pointBackgroundColor: colorPrim,
                    pointRadius: 4,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: ctx => ' ' + ctx.label + ': ' + ctx.parsed.r + ' / 10',
                        },
                    },
                },
                scales: {
                    r: {
                        beginAtZero: true,
                        max: 10,
                        ticks: { stepSize: 2, font: { size: 10 }, backdropColor: 'transparent' },
                        pointLabels: { font: { size: 11 } },
                    },
                },
            },
        });
    });
}

// ── Clickable table rows ──────────────────────────────────────────────────────
function initClickableRows() {
    document.querySelectorAll('tr[data-href]').forEach(tr => {
        if (tr._hrefBound) return;
        tr._hrefBound = true;
        tr.addEventListener('click', () => { location.href = tr.dataset.href; });
    });
}

function initAll() {
    initBarCharts();
    initRadarCharts();
    initClickableRows();
}

document.addEventListener('DOMContentLoaded', initAll);
document.addEventListener('turbo:load',       initAll);