import Chart from 'chart.js/auto';

export function renderParticipantRadar(root = document) {
    const payload = root.querySelector('#participant-radar-data');
    const canvas = root.querySelector('#participant-radar');
    if (!payload || !canvas) return null;

    const data = JSON.parse(payload.textContent);
    Chart.getChart(canvas)?.destroy();
    return new Chart(canvas, {
        type: 'radar',
        data: {
            labels: data.labels,
            datasets: [
                { label: 'Nilai peserta', data: data.values, borderColor: '#0d9488', backgroundColor: 'rgba(13,148,136,0.18)', pointBackgroundColor: '#0d9488', borderWidth: 2 },
                { label: 'Standar minimum', data: data.standards, borderColor: '#d97706', backgroundColor: 'transparent', borderDash: [5, 4], pointRadius: 2, borderWidth: 2 },
            ],
        },
        options: {
            responsive: true, maintainAspectRatio: false, locale: 'id-ID',
            scales: { r: { min: 0, max: 100, ticks: { stepSize: 20, backdropColor: 'transparent' }, pointLabels: { font: { size: 11 } }, grid: { color: '#e2e8f0' } } },
            plugins: { legend: { position: 'bottom', labels: { usePointStyle: true } } },
        },
    });
}
