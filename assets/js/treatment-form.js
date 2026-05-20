function initTreatmentForm() {
    const select = document.getElementById('product_id');
    if (!select) return;

    select.addEventListener('change', function () {
        const opt = this.options[this.selectedIndex];
        const substanceField = document.getElementById('active_substance');
        const doseField      = document.getElementById('dose_per_ha');
        const badge          = document.getElementById('organic-badge');

        if (!opt.value) {
            if (badge) badge.style.display = 'none';
            return;
        }

        if (substanceField) substanceField.value = opt.dataset.substance || '';
        if (doseField)      doseField.value      = opt.dataset.dose || '';

        if (badge) {
            const isOrganic = opt.dataset.organic === '1';
            badge.style.display = 'block';
            badge.innerHTML = isOrganic
                ? '<span class="badge badge-organic">✓ Conforme Biologico — ammesso D.Lgs 150/2012</span>'
                : '<span class="badge badge-nonorganic">✗ Non Conforme — vietato in regime biologico</span>';
        }
    });
}

document.addEventListener('DOMContentLoaded', initTreatmentForm);
document.addEventListener('turbo:load', initTreatmentForm);
