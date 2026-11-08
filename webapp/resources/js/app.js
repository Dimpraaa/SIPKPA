import { initTablePagination } from './table-pagination.js';
import { initActionMenus } from './action-menu.js';

initTablePagination();
if (document.querySelector('[data-action-menu]')) initActionMenus();

if (document.querySelector('#dashboard-chart-data')) {
    import('./dashboard-charts').then(({ renderDashboardCharts }) => renderDashboardCharts());
}

if (document.querySelector('#participant-radar-data')) {
    import('./participant-radar').then(({ renderParticipantRadar }) => renderParticipantRadar());
}

document.querySelector('[data-menu-toggle]')?.addEventListener('click', (event) => {
    const navigation = document.querySelector('#sidebar');
    const expanded = event.currentTarget.getAttribute('aria-expanded') !== 'true';
    event.currentTarget.setAttribute('aria-expanded', String(expanded));
    navigation.classList.toggle('hidden', !expanded);
    navigation.classList.toggle('flex', expanded);
});

document.addEventListener('submit', (event) => {
    const form = event.target;
    if (event.defaultPrevented) return;
    if (form.matches('form[data-confirm]') && !window.confirm(form.dataset.confirm)) {
        event.preventDefault();
        return;
    }
    if (form.matches('form[data-busy]')) {
        form.querySelectorAll('button[type="submit"]').forEach((button) => {
            button.disabled = true;
            button.textContent = 'Sedang memproses…';
        });
    }
});

const gradeInputs = [...document.querySelectorAll('[data-grade]')];
const averageOutput = document.querySelector('[data-average]');
function updateAverage() {
    if (averageOutput) {
        averageOutput.textContent = (gradeInputs.reduce((sum, input) => sum + (Number(input.value) || 0), 0) / 5).toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }
}
gradeInputs.forEach((input) => input.addEventListener('input', updateAverage));
updateAverage();
