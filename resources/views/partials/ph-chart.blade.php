@php
    $cid = $id ?? 'phChart';
    $minPh = \App\Models\WaterQuality::MIN_PH;
    $maxPh = \App\Models\WaterQuality::MAX_PH;
    $hasData = ! empty($trend['datasets']);
@endphp

@if (! $hasData)
    <p class="flex h-64 items-center justify-center rounded-lg bg-slate-50 text-sm text-slate-500">Belum ada pengukuran pH dalam periode ini.</p>
@else
    <div class="relative h-64"><canvas id="{{ $cid }}"></canvas></div>
    @push('scripts')
    <script>
    (function () {
        const trend = @json($trend);
        const lo = @json($minPh), hi = @json($maxPh);
        const colors = ['#23805a', '#2f7fb8', '#d98a1f', '#8a5cc2', '#c2503f', '#5f6f68'];

        // Sumbu Y melebar otomatis agar nilai di luar 6–9 tetap terlihat.
        const values = trend.datasets.flatMap(d => d.data).filter(v => v !== null);
        const yMin = Math.min(6, Math.floor(Math.min(...values, lo)));
        const yMax = Math.max(9, Math.ceil(Math.max(...values, hi)));
        const isOut = v => v !== null && (v < lo || v > hi);

        const band = {
            id: 'phBand',
            beforeDatasetsDraw(chart) {
                const { ctx, chartArea: a, scales: { y } } = chart;
                const top = y.getPixelForValue(hi), bottom = y.getPixelForValue(lo);
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
                datasets: trend.datasets.map((d, i) => {
                    const c = colors[i % colors.length];
                    return {
                        label: d.label, data: d.data,
                        borderColor: c, backgroundColor: c,
                        pointBackgroundColor: d.data.map(v => isOut(v) ? '#dc2626' : c),
                        pointRadius: d.data.map(v => isOut(v) ? 4 : 2.5),
                        borderWidth: 2, tension: .3, spanGaps: true,
                        borderDash: i >= colors.length ? [5, 4] : []
                    };
                })
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                scales: { y: { min: yMin, max: yMax, ticks: { stepSize: 1 } }, x: { grid: { display: false } } },
                plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, usePointStyle: true } } }
            },
            plugins: [band]
        });
    })();
    </script>
    @endpush
@endif
