import assert from 'node:assert/strict';
import { test } from 'node:test';
import Chart from 'chart.js/auto';
import { renderDashboardCharts } from '../resources/js/dashboard-charts.js';
import { renderParticipantRadar } from '../resources/js/participant-radar.js';

function canvasContext() {
    let draws = 0;
    const canvas = { width: 800, height: 400 };
    const context = new Proxy({
        canvas,
        measureText: (text) => ({ width: String(text).length * 7 }),
        getLineDash: () => [],
    }, {
        get(target, key) {
            if (key in target) return target[key];
            return () => { draws += 1; };
        },
    });
    canvas.getContext = () => context;
    return { canvas, draws: () => draws };
}

const data = {
    proporsi: { labels: ['Lulus', 'Berisiko Tidak Lulus'], values: [2, 1] },
    tren: {
        labels: ['Angkatan X', 'Angkatan XI', 'Angkatan XII'],
        komponen: ['Kehadiran', 'Kode Etik', 'Hukum Acara', 'Tugas Praktik', 'Keaktifan', 'Simulasi UPA'].map((label) => ({ label, values: [80, 0, 75] })),
        kelulusan: [50, 0, null],
    },
    featureImportance: [30, 5, 25, 20, 10, 10].map((persentase, index) => ({ label: `X${index + 1}`, persentase })),
};

test('Chart.js renders all dashboard types with real controllers and preserves null gaps and zeros', () => {
    const targets = Object.fromEntries(['proporsi', 'tren', 'importance'].map((name) => [`#${name}-chart`, canvasContext()]));
    const filter = { form: { requestSubmit: () => { submitted += 1; } } };
    let submitted = 0;
    let onChange;
    const root = {
        querySelector(selector) {
            if (selector === '#dashboard-chart-data') return { textContent: JSON.stringify(data) };
            if (selector === '[data-dashboard-filter]') return { addEventListener: (name, callback) => { onChange = callback; } };
            return targets[selector]?.canvas ?? null;
        },
    };
    const charts = renderDashboardCharts(root);
    try {
        assert.equal(charts.length, 3);
        const [donut, trend, importance] = charts;
        assert.ok(targets['#proporsi-chart'].draws() > 0);
        assert.ok(targets['#tren-chart'].draws() > 0);
        assert.ok(targets['#importance-chart'].draws() > 0);
        const arc = donut.getDatasetMeta(0).data[0];
        assert.ok(Math.abs(arc.circumference - (Math.PI * 4 / 3)) < 0.00001);
        assert.equal(trend.getDatasetMeta(0).type, 'bar');
        assert.equal(trend.getDatasetMeta(6).type, 'line');
        assert.equal(trend.getDatasetMeta(6).data[1].skip, false);
        assert.equal(trend.getDatasetMeta(6).data[2].skip, true);
        assert.equal(trend.options.scales.nilai.max, 100);
        assert.equal(trend.options.scales.kelulusan.max, 100);
        assert.equal(importance.getDatasetMeta(0).data[0].horizontal, true);
        assert.deepEqual(importance.data.datasets[0].data, [30, 5, 25, 20, 10, 10]);
        onChange({ currentTarget: filter });
        assert.equal(submitted, 1);

        const refreshed = renderDashboardCharts(root);
        assert.equal(refreshed.length, 3);
        refreshed.forEach((chart) => chart.destroy());
    } finally {
        charts.forEach((chart) => chart.destroy());
    }
});

test('dashboard rendering skips absent canvases and non-dashboard pages safely', () => {
    assert.deepEqual(renderDashboardCharts({ querySelector: () => null }), []);
    assert.deepEqual(renderDashboardCharts({
        querySelector: (selector) => selector === '#dashboard-chart-data'
            ? { textContent: JSON.stringify({ ...data, proporsi: { ...data.proporsi, values: [0, 0] }, featureImportance: [] }) }
            : null,
    }), []);
    assert.equal(Object.keys(Chart.instances).length, 0);
});

test('participant radar renders six scores including zero and correct attendance threshold', () => {
    const target = canvasContext();
    const values = [0, 70, 80, 90, 100, 50];
    const standards = [80, 70, 70, 70, 70, 70];
    const root = {
        querySelector(selector) {
            if (selector === '#participant-radar') return target.canvas;
            if (selector === '#participant-radar-data') return { textContent: JSON.stringify({ labels: data.tren.komponen.map((item) => item.label), values, standards }) };
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
