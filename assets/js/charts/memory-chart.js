import { Chart } from './chart-setup.js';

function initMemoryChart() {
    const canvas = document.getElementById('memory-chart');
    if (!canvas || canvas._agritechChart) return;

    const style     = getComputedStyle(document.documentElement);
    const colorDark  = style.getPropertyValue('--color-primary').trim()      || '#2D5016';
    const colorLight = style.getPropertyValue('--color-secondary').trim()    || '#4A7C2F';

    const labels    = JSON.parse(canvas.dataset.labels    || '[]');
    const predicted = JSON.parse(canvas.dataset.predicted || '[]');
    const actual    = JSON.parse(canvas.dataset.actual    || '[]');

    if (!labels.length) return;

    canvas._agritechChart = new Chart(canvas, {
        type: 'line',
        data: {
            labels,
            datasets: [
                {
                    label: 'Resa prevista (kg)',
                    data: predicted,
                    borderColor: colorLight,
                    backgroundColor: 'rgba(74,124,47,0.08)',
                    borderDash: [6, 3],
                    borderWidth: 2,
                    pointRadius: 4,
                    tension: 0.3,
                    fill: false,
                },
                {
                    label: 'Resa reale (kg)',
                    data: actual,
                    borderColor: colorDark,
                    backgroundColor: 'rgba(45,80,22,0.07)',
                    borderWidth: 2.5,
                    pointRadius: 5,
                    tension: 0.3,
                    fill: true,
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'top' },
                tooltip: { mode: 'index', intersect: false },
            },
            scales: {
                y: {
                    beginAtZero: false,
                    ticks: { callback: v => v.toLocaleString('it') + ' kg' },
                },
            },
        },
    });
}

document.addEventListener('DOMContentLoaded', initMemoryChart);
document.addEventListener('turbo:load',       initMemoryChart);