@php $cid = $id ?? 'phChart'; @endphp
<div class="relative h-64"><canvas id="{{ $cid }}"></canvas></div>
@push('scripts')
<script>
(function () {
    const trend = @json($trend);
    const colors = ['#23805a', '#5b9aa9', '#9aa5a0'];
    const band = {
        id: 'phBand',
        beforeDatasetsDraw(chart) {
            const { ctx, chartArea: a, scales: { y } } = chart;
            const top = y.getPixelForValue(8.5), bottom = y.getPixelForValue(6.5);
            ctx.save();
            ctx.fillStyle = 'rgba(35,128,90,.08)';
            ctx.fillRect(a.left, top, a.right - a.left, bottom - top);
            ctx.restore();
        }
    };
    new Chart(document.getElementById(@json($cid)), {
        type: 'line',
        data: {
            labels: trend.labels,
            datasets: trend.datasets.map((d, i) => ({
                label: d.label, data: d.data,
                borderColor: colors[i % 3], backgroundColor: colors[i % 3],
                borderWidth: 2, tension: .3, pointRadius: 2, spanGaps: true,
                borderDash: i % 3 === 2 ? [5, 4] : []
            }))
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            scales: { y: { min: 6, max: 9, ticks: { stepSize: 1 } }, x: { grid: { display: false } } },
            plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, usePointStyle: true } } }
        },
        plugins: [band]
    });
})();
</script>
@endpush
