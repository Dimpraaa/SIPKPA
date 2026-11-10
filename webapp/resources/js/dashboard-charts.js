import Chart from 'chart.js/auto';
import { categoryAxis, chartColors, chartDefaults, chartNumber, directLabels, donutTotal, valueAxis } from './chart-theme.js';

export function dashboardChartConfigs(data) {
    const options = chartDefaults();
    const actualTotal = data.aktual.values.reduce((sum, value) => sum + Number(value), 0);
    const participantTooltip = {
        ...options.plugins.tooltip,
        padding: 8,
        caretSize: 4,
        titleMarginBottom: 4,
        titleFont: { size: 11, weight: '600' },
        bodyFont: { size: 11 },
        callbacks: {
            title: (items) => items[0]?.label ?? '',
            label(context) {
                const total = context.dataset.data.reduce((sum, value) => sum + Number(value), 0);
                const percentage = total > 0 ? Number(context.raw) / total * 100 : 0;
                return `${chartNumber(context.raw, 0)} peserta (${chartNumber(percentage, 1)}%)`;
            },
        },
    };

    const configs = {
        akademik: {
            type: 'bar',
            data: {
                labels: data.akademik.labels,
                datasets: [{ label: 'Nilai akhir rata-rata (0–100)', data: data.akademik.values, backgroundColor: chartColors.brand, borderRadius: 2, maxBarThickness: 24 }],
            },
            plugins: [directLabels],
            options: {
                ...options,
                indexAxis: 'y',
                layout: { padding: { right: 48 } },
                plugins: {
                    ...options.plugins,
                    tooltip: { ...options.plugins.tooltip, callbacks: {
                        label: (context) => `Nilai akhir: ${chartNumber(context.raw)} / 100`,
                        afterLabel: (context) => data.akademik.counts ? `${chartNumber(data.akademik.counts[context.dataIndex], 0)} penilaian tersedia` : '',
                    } },
                },
                scales: {
                    x: valueAxis('Nilai akhir rata-rata · skala 0–100', 100),
                    y: categoryAxis(),
                },
            },
        },
        aktual: {
            type: 'bar',
            data: {
                labels: data.aktual.labels,
                datasets: [{ label: 'Jumlah peserta', data: data.aktual.values, backgroundColor: [chartColors.brand, chartColors.danger, chartColors.neutral, chartColors.light], borderRadius: 2, maxBarThickness: 24 }],
            },
            plugins: [directLabels],
            options: {
                ...options,
                indexAxis: 'y',
                layout: { padding: { right: 92 } },
                scales: { x: valueAxis('Jumlah peserta'), y: categoryAxis(18) },
                plugins: {
                    ...options.plugins,
                    directLabels: { format: (value) => `${chartNumber(value, 0)} · ${chartNumber(actualTotal > 0 ? value / actualTotal * 100 : 0, 1)}%` },
                    tooltip: participantTooltip,
                },
            },
        },
    };
    if (data.prediksi) {
        configs.prediksi = {
            type: 'doughnut',
            plugins: [donutTotal],
            data: {
                labels: data.prediksi.labels,
                datasets: [{ data: data.prediksi.values, backgroundColor: [chartColors.brand, chartColors.warning], borderWidth: 3, borderColor: '#ffffff', hoverOffset: 0 }],
            },
            options: {
                ...options,
                cutout: '78%',
                plugins: {
                    ...options.plugins,
                    tooltip: participantTooltip,
                },
            },
        };
    }
    return configs;
}

function showChartError(root, name, canvas) {
    if (canvas) canvas.hidden = true;
    const loading = root.querySelector(`[data-chart-loading="${name}"]`);
    if (loading) loading.hidden = true;
    const notice = root.querySelector(`[data-chart-error="${name}"]`);
    if (notice) notice.hidden = false;
    const fallback = root.querySelector(`[data-chart-fallback="${name}"]`);
    if (fallback) fallback.open = true;
}

export function renderDashboardCharts(root = document, chartLibrary = Chart) {
    const dataElement = root.querySelector('#dashboard-chart-data');
    if (!dataElement) return [];

    let configs;
    try {
        configs = dashboardChartConfigs(JSON.parse(dataElement.textContent));
    } catch {
        for (const name of ['akademik', 'aktual', 'prediksi']) showChartError(root, name, root.querySelector(`#${name}-chart`));
        return [];
    }

    const charts = [];
    for (const [name, config] of Object.entries(configs)) {
        const canvas = root.querySelector(`#${name}-chart`);
        if (!canvas) continue;
        try {
            chartLibrary.getChart(canvas)?.destroy();
            canvas.hidden = false;
            const notice = root.querySelector(`[data-chart-error="${name}"]`);
            if (notice) notice.hidden = true;
            charts.push(new chartLibrary(canvas, config));
            const loading = root.querySelector(`[data-chart-loading="${name}"]`);
            if (loading) loading.hidden = true;
        } catch {
            chartLibrary.getChart(canvas)?.destroy();
            showChartError(root, name, canvas);
        }
    }
    return charts;
}
