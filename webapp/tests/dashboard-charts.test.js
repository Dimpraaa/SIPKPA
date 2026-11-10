import assert from 'node:assert/strict';
import { test } from 'node:test';
import Chart from 'chart.js/auto';
import { renderDashboardCharts } from '../resources/js/dashboard-charts.js';
import { renderParticipantRadar } from '../resources/js/participant-radar.js';
import { renderCohortComparison } from '../resources/js/cohort-comparison.js';

function canvasContext(width = 800, height = 400) {
    let draws = 0;
    const labels = [];
    const canvas = { width, height };
    const context = new Proxy({
        canvas,
        measureText: (text) => ({ width: String(text).length * 7 }),
        getLineDash: () => [],
        fillText: (text) => { labels.push(text); draws += 1; },
    }, {
        get(target, key) {
            if (key in target) return target[key];
            return () => { draws += 1; };
        },
    });
    canvas.getContext = () => context;
    return { canvas, draws: () => draws, labels };
}

const data = {
    akademik: { labels: ['Angkatan X', 'Angkatan XI', 'Angkatan XII'], values: [42, 0, null] },
    aktual: { labels: ['Lulus', 'Tidak Lulus', 'Belum Ditentukan', 'Belum dinilai'], values: [1, 2, 1, 1] },
};

test('Chart.js renders two single-purpose charts without mixing units and preserves missing scores and zeros', () => {
    const targets = Object.fromEntries(['akademik', 'aktual'].map((name) => [`#${name}-chart`, canvasContext()]));
    const root = {
        querySelector(selector) {
            if (selector === '#dashboard-chart-data') return { textContent: JSON.stringify(data) };
            return targets[selector]?.canvas ?? null;
        },
    };
    const charts = renderDashboardCharts(root);
    try {
        assert.equal(charts.length, 2);
        const [academic, actual] = charts;
        assert.ok(targets['#akademik-chart'].draws() > 0);
        assert.ok(targets['#aktual-chart'].draws() > 0);
        assert.equal(actual.getDatasetMeta(0).type, 'bar');
        assert.equal(actual.getDatasetMeta(0).data[0].horizontal, true);
        assert.equal(actual.options.scales.x.min, 0);
        assert.ok(targets['#aktual-chart'].labels.includes('1 · 20,0%'));
        assert.ok(targets['#akademik-chart'].labels.includes('0,00'));
        assert.equal(academic.data.datasets.length, 1);
        assert.equal(academic.getDatasetMeta(0).type, 'bar');
        assert.equal(academic.getDatasetMeta(0).data[0].horizontal, true);
        assert.equal(academic.getDatasetMeta(0).controller.getParsed(1).x, 0);
        assert.equal(academic.getDatasetMeta(0).controller.getParsed(2).x, null);
        assert.equal(academic.options.scales.x.max, 100);
        assert.deepEqual(academic.data.datasets[0].data, [42, 0, null]);
        assert.deepEqual(actual.data.datasets[0].data, [1, 2, 1, 1]);
        assert.equal(academic.options.animation, false);

        const refreshed = renderDashboardCharts(root);
        assert.equal(refreshed.length, 2);
        refreshed.forEach((chart) => chart.destroy());
    } finally {
        charts.forEach((chart) => chart.destroy());
    }
});

test('dashboard rendering skips absent canvases and non-dashboard pages safely', () => {
    assert.deepEqual(renderDashboardCharts({ querySelector: () => null }), []);
    assert.deepEqual(renderDashboardCharts({
        querySelector: (selector) => selector === '#dashboard-chart-data'
            ? { textContent: JSON.stringify(data) }
            : null,
    }), []);
    assert.equal(Object.keys(Chart.instances).length, 0);
});

test('prediction composition counts only predicted participants and preserves a zero-count class', () => {
    const target = canvasContext();
    const root = {
        querySelector(selector) {
            if (selector === '#dashboard-chart-data') return { textContent: JSON.stringify({ ...data, prediksi: { labels: ['Lulus', 'Berisiko Tidak Lulus'], values: [3, 0] } }) };
            return selector === '#prediksi-chart' ? target.canvas : null;
        },
    };

    const charts = renderDashboardCharts(root);
    try {
        assert.equal(charts.length, 1);
        const [prediction] = charts;
        assert.equal(prediction.getDatasetMeta(0).type, 'doughnut');
        assert.deepEqual(prediction.data.datasets[0].data, [3, 0]);
        assert.equal(prediction.getDatasetMeta(0).data[1].circumference, 0);
        assert.ok(target.labels.includes('3'));
        assert.ok(target.labels.includes('terprediksi'));
        assert.equal(prediction.options.animation, false);
    } finally {
        charts.forEach((chart) => chart.destroy());
    }
});

test('prediction tooltip shows category, count and share of predicted participants within a compact donut', () => {
    const target = canvasContext(208, 208);
    const root = {
        querySelector(selector) {
            if (selector === '#dashboard-chart-data') return { textContent: JSON.stringify({ ...data, prediksi: { labels: ['Lulus', 'Berisiko Tidak Lulus'], values: [2, 1] } }) };
            return selector === '#prediksi-chart' ? target.canvas : null;
        },
    };

    const [chart] = renderDashboardCharts(root);
    try {
        const position = chart.getDatasetMeta(0).data[1].tooltipPosition();
        chart.tooltip.setActiveElements([{ datasetIndex: 0, index: 1 }], position);
        chart.update();

        assert.deepEqual(chart.tooltip.title, ['Berisiko Tidak Lulus']);
        assert.deepEqual(chart.tooltip.body[0].lines, ['1 peserta (33,3%)']);
        assert.ok(target.labels.includes('3'));
        assert.ok(chart.tooltip.width <= target.canvas.width);
        assert.ok(chart.tooltip.x >= 0);
        assert.ok(chart.tooltip.x + chart.tooltip.width <= target.canvas.width);
        assert.ok(chart.tooltip.y >= 0);
        assert.ok(chart.tooltip.y + chart.tooltip.height <= target.canvas.height);
    } finally {
        chart.destroy();
    }
});

test('cohort comparison renders backend averages on a fixed academic scale including zero and missing assessment', () => {
    const target = canvasContext();
    const root = {
        querySelector(selector) {
            if (selector === '#cohort-comparison-data') return { textContent: JSON.stringify({ labels: ['A', 'B', 'C'], values: [50, 0, null], counts: [2, 1, 0], ids: [1, 2, 3], selected: 1 }) };
            return selector === '#cohort-comparison-chart' ? target.canvas : null;
        },
    };
    const chart = renderCohortComparison(root);
    try {
        assert.ok(target.draws() > 0);
        assert.equal(chart.options.scales.x.min, 0);
        assert.equal(chart.options.scales.x.max, 100);
        assert.deepEqual(chart.data.datasets[0].data, [50, 0, null]);
        assert.ok(target.labels.includes('0,00'));
        assert.notEqual(chart.data.datasets[0].backgroundColor[0], chart.data.datasets[0].backgroundColor[1]);
    } finally {
        chart.destroy();
    }
    assert.equal(renderCohortComparison({ querySelector: () => null }), null);
});

test('invalid chart data reveals notices and opens the numeric fallback without fabricating values', () => {
    const canvas = { hidden: false };
    const notice = { hidden: true };
    const fallback = { open: false };
    const targets = { '#dashboard-chart-data': { textContent: '{invalid json' }, '#akademik-chart': canvas, '[data-chart-error="akademik"]': notice, '[data-chart-fallback="akademik"]': fallback };

    const charts = renderDashboardCharts({ querySelector: (selector) => targets[selector] ?? null });

    assert.deepEqual(charts, []);
    assert.equal(canvas.hidden, true);
    assert.equal(notice.hidden, false);
    assert.equal(fallback.open, true);
});

test('a chart construction failure reveals only its fallback and leaves backend data untouched', () => {
    const canvas = { hidden: false };
    const notice = { hidden: true };
    const fallback = { open: false };
    const serialized = JSON.stringify(data);
    const targets = { '#dashboard-chart-data': { textContent: serialized }, '#akademik-chart': canvas, '[data-chart-error="akademik"]': notice, '[data-chart-fallback="akademik"]': fallback };
    class UnavailableChart {
        static getChart() { return null; }
        constructor() { throw new Error('Canvas unavailable'); }
    }

    const charts = renderDashboardCharts({ querySelector: (selector) => targets[selector] ?? null }, UnavailableChart);

    assert.deepEqual(charts, []);
    assert.equal(canvas.hidden, true);
    assert.equal(notice.hidden, false);
    assert.equal(fallback.open, true);
    assert.equal(targets['#dashboard-chart-data'].textContent, serialized);
});

test('participant radar renders six scores including zero and correct attendance threshold', () => {
    const target = canvasContext();
    const values = [0, 70, 80, 90, 100, 50];
    const standards = [80, 70, 70, 70, 70, 70];
    const root = {
        querySelector(selector) {
            if (selector === '#participant-radar') return target.canvas;
            if (selector === '#participant-radar-data') return { textContent: JSON.stringify({ labels: ['Kehadiran', 'Kode Etik', 'Hukum Acara', 'Tugas Praktik', 'Keaktifan', 'Simulasi UPA'], values, standards }) };
            return null;
        },
    };
    const chart = renderParticipantRadar(root);
    try {
        assert.ok(target.draws() > 0);
        assert.equal(chart.getDatasetMeta(0).type, 'radar');
        assert.equal(chart.getDatasetMeta(0).data[0].skip, false);
        assert.equal(chart.getDatasetMeta(0).data.length, 6);
        assert.equal(chart.options.scales.r.max, 100);
        assert.deepEqual(chart.data.datasets[0].data, values);
        assert.deepEqual(chart.data.datasets[1].data, standards);
    } finally {
        chart.destroy();
    }
    assert.equal(renderParticipantRadar({ querySelector: () => null }), null);
});
