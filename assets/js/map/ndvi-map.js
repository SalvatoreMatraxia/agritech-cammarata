import L from 'leaflet';
import 'leaflet/dist/leaflet.min.css';
import { Chart, registerables } from 'chart.js';

Chart.register(...registerables);

// ── NDVI colour mapping ──────────────────────────────────────────────────────
function ndviColor(ndvi) {
    if (ndvi === null || ndvi === undefined) return '#aaaaaa';
    if (ndvi > 0.55)  return '#2D5016';
    if (ndvi >= 0.45) return '#8DB96E';
    if (ndvi >= 0.35) return '#C8972A';
    if (ndvi >= 0.25) return '#FFA500';
    return '#DC3545';
}

function ndviLabel(ndvi) {
    if (ndvi === null || ndvi === undefined) return '—';
    if (ndvi >= 0.55) return 'Ottima vigoria';
    if (ndvi >= 0.45) return 'Buona';
    if (ndvi >= 0.35) return 'Discreta';
    if (ndvi >= 0.25) return 'Scarsa';
    return 'Critica';
}

function trendArrow(trend) {
    if (trend === 'improving') return '↑ In miglioramento';
    if (trend === 'declining')  return '↓ In calo';
    return '→ Stabile';
}

// ── Sparkline (per popup) ────────────────────────────────────────────────────
function buildSparkline(canvas, labels, values) {
    if (!canvas || !values || values.length === 0) return;
    new Chart(canvas, {
        type: 'line',
        data: {
            labels,
            datasets: [{
                data: values,
                borderColor: '#4A7C2F',
                backgroundColor: 'rgba(74,124,47,0.12)',
                borderWidth: 1.5,
                pointRadius: 0,
                tension: 0.3,
                fill: true,
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: false,
            plugins: { legend: { display: false }, tooltip: { enabled: false } },
            scales: {
                x: { display: false },
                y: {
                    display: true,
                    min: 0,
                    max: 0.9,
                    ticks: { maxTicksLimit: 3, font: { size: 9 } },
                    grid: { color: '#e0e0e0' },
                },
            },
        },
    });
}

// ── Sidebar sparklines ────────────────────────────────────────────────────────
function initSidebarSparklines() {
    document.querySelectorAll('canvas.ndvi-sparkline').forEach(canvas => {
        if (canvas._sparkinit) return;
        canvas._sparkinit = true;
        const values = JSON.parse(canvas.dataset.history || '[]');
        const labels = JSON.parse(canvas.dataset.labels  || '[]');
        buildSparkline(canvas, labels, values);
    });
}

// ── Map init ──────────────────────────────────────────────────────────────────
function initNDVIMap() {
    const container = document.getElementById('ndvi-map');
    if (!container || container._leafletMap) return;

    const lat      = parseFloat(container.dataset.lat  || '37.63');
    const lon      = parseFloat(container.dataset.lon  || '13.63');
    const farmName = container.dataset.farmName || '';
    const parcels  = JSON.parse(container.dataset.parcels || '[]');

    const map = L.map(container, { zoomControl: true }).setView([lat, lon], 14);
    container._leafletMap = map;

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors',
        maxZoom: 19,
    }).addTo(map);

    // Fix blank tiles: il container può avere dimensione 0 al momento dell'init
    setTimeout(() => map.invalidateSize(), 150);
    setTimeout(() => map.invalidateSize(), 600);
    window.addEventListener('resize', () => map.invalidateSize());

    // Farm marker
    const farmIcon = L.divIcon({
        className: '',
        html: '<div style="background:#2D5016;color:#fff;border-radius:50%;width:36px;height:36px;display:flex;align-items:center;justify-content:center;box-shadow:0 2px 6px rgba(0,0,0,0.4)"><svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="20" height="20"><path d="M11 20A7 7 0 0 1 9.8 6.1C15.5 5 17 4.48 19 2c1 2 2 4.18 2 8 0 5.5-4.78 10-10 10z"/><path d="M2 21c0-3 1.85-5.36 5.08-6C9.5 14.52 12 13 13 12"/></svg></div>',
        iconSize: [36, 36],
        iconAnchor: [18, 18],
    });
    L.marker([lat, lon], { icon: farmIcon })
        .addTo(map)
        .bindPopup(`<strong>${farmName}</strong>`);

    // Parcel markers
    parcels.forEach(parcel => {
        const color = ndviColor(parcel.ndvi);
        const pLat  = parseFloat(parcel.lat);
        const pLon  = parseFloat(parcel.lon);

        const circle = L.circleMarker([pLat, pLon], {
            radius:      22,
            fillColor:   color,
            color:       '#fff',
            weight:      2,
            opacity:     1,
            fillOpacity: 0.85,
        }).addTo(map);

        const ndviText    = parcel.ndvi !== null ? parcel.ndvi.toFixed(3) : '—';
        const trendText   = trendArrow(parcel.ndvi_trend);
        const surfaceText = parcel.surface ? `${parseFloat(parcel.surface).toFixed(1)} ha` : '—';
        const altText     = parcel.altitude ? `${parcel.altitude} m` : '';
        const aspectText  = parcel.aspect   ? `${parcel.aspect}${altText ? ' · ' + altText : ''}` : altText;

        const popupHTML = `
            <div class="parcel-popup">
                <h4>${parcel.name}</h4>
                <p class="popup-variety">${parcel.variety} · ${parcel.treeCount} piante</p>
                <div class="popup-ndvi">
                    <span class="popup-label">NDVI:</span>
                    <span class="popup-value" style="color:${color}">${ndviText}</span>
                    <span class="popup-trend">${trendText}</span>
                </div>
                <div class="popup-details">
                    <p>Superficie: ${surfaceText}</p>
                    ${aspectText ? `<p>Esposizione: ${aspectText}</p>` : ''}
                    ${parcel.anomaly ? '<p style="color:#DC3545;font-weight:600">⚠ Anomalia rilevata</p>' : ''}
                </div>
            </div>`;

        circle.bindPopup(popupHTML, { maxWidth: 280, minWidth: 200 });
    });

    // Sidebar parcel click → pan to marker
    document.querySelectorAll('.sidebar-parcel').forEach(el => {
        el.addEventListener('click', () => {
            const id = parseInt(el.dataset.parcelId, 10);
            const p  = parcels.find(x => x.id === id);
            if (p) map.setView([parseFloat(p.lat), parseFloat(p.lon)], 16);
        });
    });

    // Sidebar toggle
    const sidebar   = document.getElementById('map-sidebar');
    const toggleBtn = document.getElementById('sidebar-toggle');
    const fabBtn    = document.getElementById('sidebar-fab');

    // Mobile: FAB apre/chiude il bottom sheet
    if (fabBtn && sidebar) {
        fabBtn.addEventListener('click', () => {
            const opening = !sidebar.classList.contains('open');
            sidebar.classList.toggle('open');
            fabBtn.setAttribute('aria-expanded', String(opening));
            if (opening) {
                // Aspetta la fine dell'animazione prima di inizializzare sparkline
                setTimeout(() => { map.invalidateSize(); initSidebarSparklines(); }, 320);
            }
        });
    }

    // ✕ nella sidebar header — chiude il bottom sheet su mobile
    if (toggleBtn && sidebar) {
        toggleBtn.addEventListener('click', () => {
            sidebar.classList.remove('open');
            if (fabBtn) fabBtn.setAttribute('aria-expanded', 'false');
            setTimeout(() => map.invalidateSize(), 50);
        });
    }

    initSidebarSparklines();
}

// ── Boot ──────────────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => { initNDVIMap(); initSidebarSparklines(); });
document.addEventListener('turbo:load',       () => { initNDVIMap(); initSidebarSparklines(); });