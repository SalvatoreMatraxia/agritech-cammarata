import { Chart, registerables } from 'chart.js';

Chart.register(...registerables);

function buildWeatherChart(canvas) {
    const labels = JSON.parse(canvas.dataset.labels || '[]');
    const tMax   = JSON.parse(canvas.dataset.tmax   || '[]');
    const tMin   = JSON.parse(canvas.dataset.tmin   || '[]');
    const rain   = JSON.parse(canvas.dataset.rain   || '[]');

    if (!labels.length) return null;

    return new Chart(canvas, {
        data: {
            labels,
            datasets: [
                {
                    type: 'bar',
                    label: 'Pioggia (mm)',
                    data: rain,
                    backgroundColor: 'rgba(41, 128, 185, 0.35)',
                    borderColor:     'rgba(41, 128, 185, 0.7)',
                    borderWidth: 1,
                    yAxisID: 'yRain',
                    order: 2,
                },
                {
                    type: 'line',
                    label: 'Temp Max (°C)',
                    data: tMax,
                    borderColor:     '#c0392b',
                    backgroundColor: 'transparent',
                    pointRadius: 3,
                    tension: 0.3,
                    yAxisID: 'yTemp',
                    order: 1,
                },
                {
                    type: 'line',
                    label: 'Temp Min (°C)',
                    data: tMin,
                    borderColor:     '#2980b9',
                    backgroundColor: 'transparent',
                    pointRadius: 3,
                    tension: 0.3,
                    yAxisID: 'yTemp',
                    order: 1,
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { boxWidth: 12, font: { size: 11 } },
                },
            },
            scales: {
                yTemp: {
                    type: 'linear',
                    position: 'left',
                    title: { display: true, text: '°C' },
                },
                yRain: {
                    type: 'linear',
                    position: 'right',
                    title: { display: true, text: 'mm' },
                    grid: { drawOnChartArea: false },
                },
            },
        },
    });
}

function initAll() {
    document.querySelectorAll('canvas[data-chart="weather"]').forEach(canvas => {
        if (canvas._agritechChart) return;
        canvas._agritechChart = buildWeatherChart(canvas);
    });
}

document.addEventListener('DOMContentLoaded', initAll);
document.addEventListener('turbo:load', initAll);