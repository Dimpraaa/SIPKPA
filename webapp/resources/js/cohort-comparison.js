import Chart from 'chart.js/auto';
import { categoryAxis, chartColors, chartDefaults, chartNumber, directLabels, valueAxis } from './chart-theme.js';

export function renderCohortComparison(root = document, chartLibrary = Chart) {
    const canvas = root.querySelector('#cohort-comparison-chart');
    const payload = root.querySelector('#cohort-comparison-data');
    if (!canvas || !payload) return null;

    try {
        const data = JSON.parse(payload.textContent);
        const options = chartDefaults();
        chartLibrary.getChart(canvas)?.destroy();
        const chart = new chartLibrary(canvas, {
            type: 'bar',
            plugins: [directLabels],
            data: {
                labels: data.labels.map((label, index) => data.ids[index] === data.selected ? `${label} (pilihan)` : label),
                datasets: [{
                    label: 'Nilai akhir rata-rata (0–100)',
                    data: data.values,
                    backgroundColor: data.ids.map((id) => id === data.selected ? chartColors.brand : chartColors.info),
                    borderRadius: 2,
                    maxBarThickness: 24,
                }],
            },
            options: {
                ...options,
                indexAxis: 'y',
                layout: { padding: { right: 48 } },
                scales: { x: valueAxis('Nilai akhir rata-rata · skala 0–100', 100), y: categoryAxis(20) },
                plugins: { ...options.plugins, tooltip: { ...options.plugins.tooltip, callbacks: {
                    label: (context) => `Nilai akhir: ${chartNumber(context.raw)} / 100`,
                    afterLabel: (context) => `${chartNumber(data.counts[context.dataIndex], 0)} penilaian tersedia${data.ids[context.dataIndex] === data.selected ? ' · Angkatan pilihan tabel' : ''}`,
                } } },
            },
        });
        const loading = root.querySelector('[data-chart-loading="comparison"]');
        if (loading) loading.hidden = true;
        return chart;
    } catch {
        chartLibrary.getChart(canvas)?.destroy();
        canvas.hidden = true;
        const loading = root.querySelector('[data-chart-loading="comparison"]');
        if (loading) loading.hidden = true;
        const notice = root.querySelector('[data-comparison-error]');
        if (notice) notice.hidden = false;
        const fallback = root.querySelector('[data-comparison-fallback]');
        if (fallback) fallback.open = true;
        return null;
    }
}
