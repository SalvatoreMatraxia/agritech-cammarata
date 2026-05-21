// phenology-timeline.js
// Scrolls the active stage dot into view on mobile (center of overflow container).
// CSS handles all visual states (completed/current/upcoming) and the pulse animation.
// Turbo-safe: re-runs on every page load.

function initPhenologyTimelines() {
    document.querySelectorAll('.pheno-timeline').forEach(timeline => {
        const current = timeline.querySelector('.pheno-stage.is-current');
        if (!current) return;

        // Scroll so the current stage dot is horizontally centred in the timeline
        const containerW = timeline.offsetWidth;
        const stageLeft  = current.offsetLeft;
        const stageW     = current.offsetWidth;
        const scrollTo   = stageLeft - containerW / 2 + stageW / 2;

        timeline.scrollTo({ left: Math.max(0, scrollTo), behavior: 'smooth' });
    });
}

document.addEventListener('DOMContentLoaded', initPhenologyTimelines);
document.addEventListener('turbo:load',       initPhenologyTimelines);