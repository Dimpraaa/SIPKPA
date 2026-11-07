import Chart from 'chart.js/auto';

const featureColors = ['#0d9488', '#0284c7', '#6366f1', '#d97706', '#db2777', '#64748b'];
const percent = (value) => `${Number(value).toLocaleString('id-ID', { maximumFractionDigits: 2 })}%`;
const numeric = (value) => Number(value).toLocaleString('id-ID', { maximumFractionDigits: 2 });

export function dashboardChartConfigs(data) {
    const options = {
        responsive: true,
        maintainAspectRatio: false,
        locale: 'id-ID',
        plugins: {
            legend: { labels: { usePointStyle: true, boxWidth: 8, padding: 16, font: { size: 11 } } },
        },
    };

    return {
        proporsi: {
            type: 'doughnut',
            data: {
                labels: data.proporsi.labels,
                datasets: [{ data: data.proporsi.values, backgroundColor: ['#0d9488', '#f43f5e'], borderWidth: 3, borderColor: '#ffffff', hoverOffset: 5 }],
            },
            options: {
                ...options,
                cutout: '74%',
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label(context) {
                                const total = context.dataset.data.reduce((sum, value) => sum + value, 0);
                                return `${context.label}: ${context.raw} peserta (${percent(total > 0 ? context.raw / total * 100 : 0)})`;
                            },
                        },
                    },
                },
            },
        },
        tren: {
            type: 'bar',
            data: {
                labels: data.tren.labels,
                datasets: [
                    ...data.tren.komponen.map((feature, index) => ({
                        type: 'bar', label: `X${index + 1} · ${feature.label}`, data: feature.values,
                        backgroundColor: featureColors[index], borderRadius: 4, maxBarThickness: 24,
                        yAxisID: 'nilai', order: 2,
                    })),
                    {
                        type: 'line', label: 'Kelulusan aktual (%)', data: data.tren.kelulusan,
                        borderColor: '#0f172a', backgroundColor: '#0f172a', borderWidth: 2,
                        pointRadius: 4, pointHoverRadius: 6, borderDash: [5, 4],
                        tension: 0.2, spanGaps: false, yAxisID: 'kelulusan', order: 1,
                    },
                ],
            },
            options: {
                ...options,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    ...options.plugins,
                    legend: { ...options.plugins.legend, position: 'bottom' },
                    tooltip: {
                        callbacks: {
                            label: (context) => `${context.dataset.label}: ${context.dataset.yAxisID === 'kelulusan' ? percent(context.raw) : numeric(context.raw)}`,
                        },
                    },
                },
                scales: {
                    x: { grid: { display: false }, ticks: { font: { size: 11 } } },
                    nilai: { type: 'linear', position: 'left', min: 0, max: 100, title: { display: true, text: 'Rata-rata X1–X6 (0–100)' }, grid: { color: '#f1f5f9' } },
                    kelulusan: { type: 'linear', position: 'right', min: 0, max: 100, title: { display: true, text: 'Kelulusan aktual (%)' }, ticks: { callback: (value) => `${value}%` }, grid: { drawOnChartArea: false } },
                },
            },
        },
        importance: {
            type: 'bar',
            data: {
                labels: data.featureImportance.map((feature) => feature.label),
                datasets: [{
                    label: 'Kontribusi fitur (%)', data: data.featureImportance.map((feature) => feature.persentase),
                    backgroundColor: featureColors, borderRadius: 5, maxBarThickness: 26,
                }],
            },
            options: {
                ...options,
                indexAxis: 'y',
                plugins: { legend: { display: false }, tooltip: { callbacks: { label: (context) => `Kontribusi: ${percent(context.raw)}` } } },
                scales: {
                    x: { min: 0, max: 100, ticks: { callback: (value) => `${value}%` }, grid: { color: '#f1f5f9' } },
                    y: { grid: { display: false }, ticks: { font: { size: 11 } } },
                },
            },
        },
    };
}

export function renderDashboardCharts(root = document) {
    const dataElement = root.querySelector('#dashboard-chart-data');
    if (!dataElement) return [];

    const configs = dashboardChartConfigs(JSON.parse(dataElement.textContent));
    const charts = [];
    for (const [name, config] of Object.entries(configs)) {
        const canvas = root.querySelector(`#${name}-chart`);
        if (!canvas) continue;
        Chart.getChart(canvas)?.destroy();
        charts.push(new Chart(canvas, config));
    }

    root.querySelector('[data-dashboard-filter]')?.addEventListener('change', (event) => {
        event.currentTarget.form.requestSubmit();
    });
    return charts;
}
