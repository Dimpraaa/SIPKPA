import Chart from 'chart.js/auto';
import { chartColors, chartDefaults, chartNumber } from './chart-theme.js';

export function renderParticipantRadar(root = document) {
    const payload = root.querySelector('#participant-radar-data');
    const canvas = root.querySelector('#participant-radar');
    if (!payload || !canvas) return null;

    try {
        const data = JSON.parse(payload.textContent);
        const options = chartDefaults();
        Chart.getChart(canvas)?.destroy();
        const chart = new Chart(canvas, {
            type: 'radar',
            data: {
                labels: data.labels,
                datasets: [
                    { label: 'Nilai peserta', data: data.values, borderColor: chartColors.brand, backgroundColor: 'rgba(23,107,86,0.10)', pointBackgroundColor: chartColors.brand, pointRadius: 3, borderWidth: 2 },
                    { label: 'Ambang simulasi', data: data.standards, borderColor: chartColors.warning, backgroundColor: 'transparent', borderDash: [5, 4], pointStyle: 'rect', pointRadius: 2, borderWidth: 2 },
                ],
            },
            options: {
                ...options,
                scales: {
                    r: {
                        min: 0,
                        max: 100,
                        ticks: { stepSize: 20, backdropColor: 'transparent', color: chartColors.muted, font: { size: 10 } },
                        pointLabels: { color: chartColors.muted, font: { size: 11 } },
                        grid: { color: chartColors.grid },
                        angleLines: { color: chartColors.grid },
                    },
                },
                plugins: {
                    ...options.plugins,
                    legend: { display: true, position: 'bottom', labels: { boxWidth: 20, boxHeight: 2, padding: 20, color: chartColors.muted, font: { size: 11 } } },
                    tooltip: { ...options.plugins.tooltip, callbacks: { label: (context) => `${context.dataset.label}: ${chartNumber(context.raw)}${/kehadiran/i.test(context.label) ? '%' : ' / 100'}` } },
                },
            },
        });
        const loading = root.querySelector('[data-chart-loading="radar"]');
        if (loading) loading.hidden = true;
        return chart;
    } catch {
        Chart.getChart(canvas)?.destroy();
        canvas.hidden = true;
        const loading = root.querySelector('[data-chart-loading="radar"]');
        if (loading) loading.hidden = true;
        const notice = root.querySelector('[data-radar-error]');
        if (notice) notice.hidden = false;
        return null;
    }
}
