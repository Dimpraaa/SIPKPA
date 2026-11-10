export const chartColors = {
    brand: '#176b56',
    ink: '#182e24',
    muted: '#4c6055',
    grid: '#e7ece5',
    danger: '#a34545',
    warning: '#a67529',
    info: '#466d91',
    neutral: '#82948a',
    light: '#dce3dc',
};

export const chartNumber = (value, digits = 2) => value === null || value === undefined
    ? 'Belum tersedia'
    : Number(value).toLocaleString('id-ID', { minimumFractionDigits: digits, maximumFractionDigits: digits });

export function chartDefaults() {
    return {
        responsive: true,
        maintainAspectRatio: false,
        animation: false,
        locale: 'id-ID',
        color: chartColors.muted,
        font: { family: 'Instrument Sans, Segoe UI, system-ui, sans-serif', size: 11 },
        plugins: {
            legend: { display: false },
            tooltip: {
                backgroundColor: '#102f28',
                titleColor: '#ffffff',
                bodyColor: '#eaf4ef',
                padding: 12,
                cornerRadius: 4,
                displayColors: false,
                titleFont: { size: 12, weight: '600' },
                bodyFont: { size: 12 },
            },
        },
    };
}

export function categoryAxis(maxCharacters = 26) {
    return {
        border: { display: false },
        grid: { display: false },
        ticks: {
            autoSkip: false,
            color: chartColors.muted,
            font: { size: 11 },
            callback(value) {
                const words = String(this.getLabelForValue(value)).split(' ');
                const lines = [''];
                for (const word of words) {
                    if (lines.at(-1).length + word.length > maxCharacters && lines.at(-1)) lines.push('');
                    lines[lines.length - 1] += `${lines.at(-1) ? ' ' : ''}${word}`;
                }
                return lines;
            },
        },
    };
}

export function valueAxis(title, maximum) {
    return {
        min: 0,
        ...(maximum === undefined ? {} : { max: maximum }),
        border: { display: false },
        title: { display: true, text: title, color: chartColors.muted, font: { size: 10 } },
        grid: { color: chartColors.grid, drawTicks: false },
        ticks: { color: chartColors.muted, padding: 8, precision: 0, font: { size: 10 } },
    };
}

// Labels are drawn from the dataset itself, including zero; missing data stays missing.
export const directLabels = {
    id: 'directLabels',
    descriptors: { _scriptable: false, _indexable: false },
    afterDatasetsDraw(chart, _args, options) {
        const { ctx } = chart;
        ctx.save();
        ctx.font = '600 11px "Instrument Sans", "Segoe UI", sans-serif';
        ctx.fillStyle = chartColors.ink;
        ctx.textBaseline = 'middle';
        chart.data.datasets.forEach((dataset, datasetIndex) => {
            if (!chart.isDatasetVisible(datasetIndex)) return;
            chart.getDatasetMeta(datasetIndex).data.forEach((element, index) => {
                const value = dataset.data[index];
                if (value === null || value === undefined || !Number.isFinite(Number(value))) return;
                const text = options.format ? options.format(value, index, datasetIndex) : chartNumber(value);
                const { x, y } = element.tooltipPosition();
                ctx.textAlign = 'left';
                ctx.fillText(text, Math.min(x + 8, chart.width - ctx.measureText(text).width - 4), y);
            });
        });
        ctx.restore();
    },
};

export const donutTotal = {
    id: 'donutTotal',
    afterDraw(chart) {
        if (!chart.getDatasetMeta(0).data.length) return;
        const { ctx } = chart;
        const { x, y } = chart.getDatasetMeta(0).data[0];
        const total = chart.data.datasets[0].data.reduce((sum, value) => sum + Number(value), 0);
        ctx.save();
        ctx.textAlign = 'center';
        ctx.fillStyle = chartColors.ink;
        ctx.font = '600 28px "Instrument Sans", "Segoe UI", sans-serif';
        ctx.fillText(chartNumber(total, 0), x, y - 1);
        ctx.fillStyle = chartColors.muted;
        ctx.font = '11px "Instrument Sans", "Segoe UI", sans-serif';
        ctx.fillText('terprediksi', x, y + 21);
        ctx.restore();
    },
};
