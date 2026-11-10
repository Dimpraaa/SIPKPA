import Chart from 'chart.js/auto';
import { categoryAxis, chartColors, chartDefaults, chartNumber, directLabels, donutTotal, valueAxis } from './chart-theme.js';

export function dashboardChartConfigs(data) {
    const options = chartDefaults();
    const actualTotal = data.aktual.values.reduce((sum, value) => sum + Number(value), 0);

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
                    tooltip: {
                        ...options.plugins.tooltip,
                        callbacks: {
                            label(context) {
                                const total = context.dataset.data.reduce((sum, value) => sum + value, 0);
                                const percentage = Number(total > 0 ? context.raw / total * 100 : 0).toLocaleString('id-ID', { maximumFractionDigits: 2 });
                                return `${context.label}: ${context.raw} dari ${total} peserta (${percentage}%)`;
                            },
                        },
                    },
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
                    tooltip: { ...options.plugins.tooltip, callbacks: {
                        label(context) {
                            const total = context.dataset.data.reduce((sum, value) => sum + Number(value), 0);
                            return `${context.label}: ${context.raw} dari ${total} terprediksi (${chartNumber(total > 0 ? context.raw / total * 100 : 0, 1)}%)`;
                        },
                    } },
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
