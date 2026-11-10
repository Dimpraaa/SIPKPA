import { initTablePagination } from './table-pagination.js';
import { initActionMenus } from './action-menu.js';
import { initPredictionDetails } from './prediction-details.js';

initTablePagination();
if (document.querySelector('[data-action-menu]')) initActionMenus();
if (document.querySelector('.prediksi-page, .peringatan-page')) initPredictionDetails();
document.querySelectorAll('[data-chart-loading]').forEach((message) => { message.hidden = false; });

if (document.querySelector('#dashboard-chart-data')) {
    document.querySelector('[data-dashboard-filter]')?.addEventListener('change', (event) => event.currentTarget.form.requestSubmit());
    import('./dashboard-charts').then(({ renderDashboardCharts }) => renderDashboardCharts()).catch(() => {
        document.querySelectorAll('[data-dashboard-chart]').forEach((section) => {
            const canvas = section.querySelector('canvas');
            if (!canvas) return;
            canvas.hidden = true;
            section.querySelector('[data-chart-loading]')?.setAttribute('hidden', '');
            section.querySelector('[data-chart-error]').hidden = false;
            const fallback = section.querySelector('[data-chart-fallback]');
            if (fallback) fallback.open = true;
        });
    });
}

if (document.querySelector('#participant-radar-data')) {
    import('./participant-radar').then(({ renderParticipantRadar }) => renderParticipantRadar()).catch(() => {
        document.querySelector('#participant-radar').hidden = true;
        document.querySelector('[data-chart-loading="radar"]').hidden = true;
        document.querySelector('[data-radar-error]').hidden = false;
    });
}

if (document.querySelector('#cohort-comparison-data')) {
    import('./cohort-comparison').then(({ renderCohortComparison }) => renderCohortComparison()).catch(() => {
        const canvas = document.querySelector('#cohort-comparison-chart');
        if (canvas) canvas.hidden = true;
        const loading = document.querySelector('[data-chart-loading="comparison"]');
        if (loading) loading.hidden = true;
        document.querySelector('[data-comparison-error]').hidden = false;
        document.querySelector('[data-comparison-fallback]').open = true;
    });
}

const menuToggle = document.querySelector('[data-menu-toggle]');
const navigation = document.querySelector('#sidebar');
const menuBackdrop = document.querySelector('[data-menu-backdrop]');
const workspace = document.querySelector('[data-workspace-content]');
function closeNavigation(restoreFocus = true) {
    if (!navigation?.classList.contains('is-open')) return;
    navigation.classList.remove('is-open');
    navigation.removeAttribute('role');
    navigation.removeAttribute('aria-modal');
    menuToggle.setAttribute('aria-expanded', 'false');
    menuBackdrop.hidden = true;
    workspace.inert = false;
    if (restoreFocus) menuToggle.focus();
}
menuToggle?.addEventListener('click', () => {
    if (navigation.classList.contains('is-open')) return closeNavigation();
    navigation.classList.add('is-open');
    navigation.setAttribute('role', 'dialog');
    navigation.setAttribute('aria-modal', 'true');
    menuToggle.setAttribute('aria-expanded', 'true');
    menuBackdrop.hidden = false;
    workspace.inert = true;
    navigation.querySelector('[data-menu-close]').focus();
});
document.querySelector('[data-menu-close]')?.addEventListener('click', () => closeNavigation());
menuBackdrop?.addEventListener('click', () => closeNavigation());
navigation?.addEventListener('keydown', (event) => {
    if (!navigation.classList.contains('is-open')) return;
    if (event.key === 'Escape') closeNavigation();
    if (event.key !== 'Tab') return;
    const elements = [...navigation.querySelectorAll('a[href], button, [tabindex="0"]')].filter((element) => element.getClientRects().length);
    const first = elements[0];
    const last = elements.at(-1);
    if (event.shiftKey && document.activeElement === first) {
        event.preventDefault();
        last.focus();
    } else if (!event.shiftKey && document.activeElement === last) {
        event.preventDefault();
        first.focus();
    }
});
if (navigation) window.matchMedia('(min-width: 1024px)').addEventListener('change', (event) => {
    if (event.matches) closeNavigation(false);
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
