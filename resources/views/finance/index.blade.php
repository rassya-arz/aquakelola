@extends('layouts.app')
@section('title', 'Laba Rugi')
@section('crumb', 'Laba Rugi')

@section('content')
@php $canWrite = auth()->user()->isPengelola(); @endphp

<div class="flex flex-wrap items-start justify-between gap-4">
    <div>
        <h1 class="text-3xl font-normal tracking-tight">Laba rugi</h1>
        <p class="mt-2 text-sm text-slate-500">Ringkasan pendapatan panen dan biaya pembelian pakan pada periode terpilih.</p>
    </div>
    <div class="flex items-center gap-2">
        <form method="GET" class="flex items-center">
            <input type="hidden" name="type" value="{{ $filter }}">
            <label class="sr-only" for="month">Bulan</label>
            <input id="month" type="month" name="month" value="{{ $month->format('Y-m') }}" onchange="this.form.submit()" class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium">
        </form>
        <a href="{{ route('finance.export', ['month' => $month->format('Y-m')]) }}" class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-medium hover:bg-slate-50">
            @include('partials.icon', ['name' => 'download', 'class' => 'h-4 w-4']) Ekspor laporan
        </a>
    </div>
</div>

<div class="mt-6 grid gap-4 md:grid-cols-3">
    <div class="rounded-xl border border-slate-200 bg-white p-5">
        <p class="text-sm text-slate-500">Pendapatan panen</p>
        <p class="mt-3 text-3xl font-medium">@rp($revenue)</p>
        <p class="mt-3 text-xs text-slate-500">@num($soldKg) kg ikan • {{ $salesCount }} transaksi penjualan</p>
    </div>
    <div class="rounded-xl border border-slate-200 bg-white p-5">
        <p class="text-sm text-slate-500">Biaya pembelian pakan</p>
        <p class="mt-3 text-3xl font-medium">@rp($purchase)</p>
        <p class="mt-3 text-xs text-slate-500">@num($purchaseKg) kg pakan • {{ $purchaseCount }} transaksi pembelian</p>
    </div>
    <div class="rounded-xl border border-slate-200 bg-white p-5">
        <p class="text-sm text-slate-500">Hasil bersih terbatas</p>
        <p class="mt-3 text-3xl font-medium {{ $profit >= 0 ? 'text-brand-600' : 'text-red-600' }}">{{ $profit < 0 ? '-' : '' }}@rp(abs($profit))</p>
        <p class="mt-3 text-xs text-slate-500">@rp($revenue) − @rp($purchase)</p>
    </div>
</div>

<div class="mt-4 flex gap-3 rounded-xl bg-brand-50/60 p-4 text-sm">
    @include('partials.icon', ['name' => 'info', 'class' => 'mt-0.5 h-5 w-5 shrink-0 text-slate-500'])
    <div>
        <p class="font-medium">Bukan laba bersih seluruh usaha</p>
        <p class="mt-1 text-xs text-slate-600">Perhitungan hanya pendapatan penjualan panen dikurangi biaya pembelian pakan. Gaji, listrik, perawatan, dan biaya lain tidak dihitung; pemakaian stok tidak menambah biaya pembelian.</p>
    </div>
</div>

<div class="mt-6 grid gap-4 lg:grid-cols-3">
    <section class="rounded-xl border border-slate-200 bg-white p-6 lg:col-span-2">
        <div class="flex items-start justify-between">
            <div>
                <h2 class="font-medium">Pendapatan vs pembelian pakan</h2>
                <p class="mt-1 text-xs text-slate-500">{{ $month->translatedFormat('F Y') }} • dalam juta rupiah</p>
            </div>
            <span class="rounded-md bg-slate-100 px-2.5 py-1 text-xs text-slate-500">Mingguan</span>
        </div>
        <div class="relative mt-4 h-64"><canvas id="financeChart"></canvas></div>
        <div class="mt-4 flex justify-between border-t border-slate-100 pt-4 text-sm">
            <span class="text-slate-500">Hasil periode ini</span>
            <span class="font-medium {{ $profit >= 0 ? 'text-brand-600' : 'text-red-600' }}">{{ $profit >= 0 ? '+ ' : '− ' }}@rp(abs($profit))</span>
        </div>
    </section>

    <section class="rounded-xl border border-slate-200 bg-white p-6">
        <h2 class="font-medium">Catat penjualan panen</h2>
        @if ($canWrite)
            <p class="mt-1 text-xs text-slate-500">Input manual</p>
            <form method="POST" action="{{ route('finance.store') }}" class="mt-4 space-y-4"
                  x-data="{ w: '{{ old('weight_kg') }}', p: '{{ old('price_per_kg') }}', get total() { return (parseFloat(this.w) || 0) * (parseFloat(this.p) || 0); } }">
                @csrf
                <div>
                    <label class="text-sm font-medium" for="pond_id">Kolam panen</label>
                    <select id="pond_id" name="pond_id" required class="mt-2 w-full rounded-lg border border-slate-200 px-3 py-3 text-sm">
                        <option value="">Pilih kolam</option>
                        @foreach ($ponds as $p)<option value="{{ $p->id }}" @selected(old('pond_id') == $p->id)>{{ $p->name }} • {{ $p->fish_type }}</option>@endforeach
                    </select>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="text-sm font-medium" for="weight_kg">Berat panen (kg)</label>
                        <input id="weight_kg" name="weight_kg" type="number" min="0.01" step="any" required x-model="w" placeholder="0" class="mt-2 w-full rounded-lg border border-slate-200 px-3 py-3 text-sm">
                    </div>
                    <div>
                        <label class="text-sm font-medium" for="price_per_kg">Harga / kg (Rp)</label>
                        <input id="price_per_kg" name="price_per_kg" type="number" min="1" step="any" required x-model="p" placeholder="0" class="mt-2 w-full rounded-lg border border-slate-200 px-3 py-3 text-sm">
                    </div>
                </div>
                <div>
                    <label class="text-sm font-medium" for="transaction_date">Tanggal penjualan</label>
                    <input id="transaction_date" name="transaction_date" type="date" max="{{ now()->toDateString() }}" value="{{ old('transaction_date', now()->toDateString()) }}" required class="mt-2 w-full rounded-lg border border-slate-200 px-3 py-3 text-sm">
                </div>
                <label class="flex items-center gap-2 text-xs text-slate-500">
                    <input type="checkbox" name="mark_harvested" value="1" class="h-4 w-4 rounded border-slate-300 text-brand-600"> Tandai kolam sudah panen
                </label>
                <div class="flex justify-between text-sm">
                    <span class="text-slate-500">Total pendapatan</span>
                    <span class="font-medium" x-text="'Rp' + new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 }).format(total)"></span>
                </div>
                <button class="inline-flex items-center gap-2 rounded-lg bg-brand-600 px-5 py-3 text-sm font-medium text-white hover:bg-brand-700">
                    @include('partials.icon', ['name' => 'plus', 'class' => 'h-4 w-4']) Simpan penjualan
                </button>
            </form>
        @else
            <p class="mt-3 text-sm text-slate-500">Akun mitra / pemilik hanya dapat melihat laporan. Penjualan panen dicatat oleh pengelola.</p>
        @endif
    </section>
</div>

{{-- Rekap bulanan --}}
<section class="mt-6 rounded-xl border border-slate-200 bg-white p-6">
    <h2 class="font-medium">Rekapitulasi bulanan</h2>
    <p class="mt-1 text-xs text-slate-500">Enam bulan sampai {{ $month->translatedFormat('F Y') }}</p>
    <div class="mt-4 overflow-x-auto">
        <table class="w-full min-w-[560px] text-left text-sm">
            <thead class="bg-slate-50 text-xs text-slate-500">
                <tr><th class="px-4 py-3 font-medium">Bulan</th><th class="px-4 py-3 font-medium">Pendapatan panen</th><th class="px-4 py-3 font-medium">Pembelian pakan</th><th class="px-4 py-3 font-medium">Hasil</th></tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach ($recap as $row)
                    <tr class="{{ $row['key'] === $month->format('Y-m') ? 'bg-brand-50/40' : '' }}">
                        <td class="px-4 py-3.5 font-medium"><a href="{{ route('finance.index', ['month' => $row['key']]) }}" class="hover:underline">{{ $row['label'] }}</a></td>
                        <td class="px-4 py-3.5">@rp($row['revenue'])</td>
                        <td class="px-4 py-3.5">@rp($row['purchase'])</td>
                        <td class="px-4 py-3.5 font-medium {{ $row['profit'] >= 0 ? 'text-brand-600' : 'text-red-600' }}">{{ $row['profit'] < 0 ? '−' : '' }}@rp(abs($row['profit']))</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</section>

{{-- Riwayat keuangan --}}
<section class="mt-6 rounded-xl border border-slate-200 bg-white p-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="font-medium">Riwayat keuangan</h2>
            <p class="mt-1 text-xs text-slate-500">Seluruh transaksi keuangan pada {{ $month->translatedFormat('F Y') }}</p>
        </div>
        <div class="flex gap-1 rounded-xl bg-slate-100 p-1 text-sm">
            @foreach (['semua' => 'Semua transaksi', 'pendapatan_panen' => 'Pendapatan', 'pembelian_pakan' => 'Pembelian'] as $key => $label)
                <a href="{{ route('finance.index', ['month' => $month->format('Y-m'), 'type' => $key]) }}"
                   class="rounded-lg px-3 py-1.5 {{ $filter === $key ? 'bg-white font-medium text-brand-700 shadow-sm' : 'text-slate-500' }}">{{ $label }}</a>
            @endforeach
        </div>
    </div>
    <div class="mt-4 overflow-x-auto">
        <table class="w-full min-w-[720px] text-left text-sm">
            <thead class="bg-slate-50 text-xs text-slate-500">
                <tr><th class="px-4 py-3 font-medium">Tanggal</th><th class="px-4 py-3 font-medium">Transaksi</th><th class="px-4 py-3 font-medium">Rincian</th><th class="px-4 py-3 font-medium">Jumlah</th><th class="px-4 py-3 font-medium">Harga / kg</th><th class="px-4 py-3 font-medium">Nilai</th>@if ($canWrite)<th class="px-4 py-3 font-medium">Aksi</th>@endif</tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($history as $t)
                    <tr>
                        <td class="px-4 py-3.5 font-medium">{{ $t->transaction_date->translatedFormat('d M Y') }}</td>
                        <td class="px-4 py-3.5 text-slate-600">{{ $t->isRevenue() ? 'Pendapatan panen' : 'Pembelian pakan' }}</td>
                        <td class="px-4 py-3.5 text-slate-500">{{ $t->isRevenue() ? ($t->pond?->fish_type.' • '.$t->pond?->name) : ($t->feed?->feed_type ?? '-') }}</td>
                        <td class="px-4 py-3.5 text-slate-500">@num($t->weight_kg) kg</td>
                        <td class="px-4 py-3.5 text-slate-500">@rp($t->price_per_kg)</td>
                        <td class="px-4 py-3.5 font-medium {{ $t->isRevenue() ? 'text-brand-600' : 'text-slate-700' }}">{{ $t->isRevenue() ? '+' : '−' }} @rp($t->total_amount)</td>
                        @if ($canWrite)
                            <td class="px-4 py-3.5">
                                @if ($t->isRevenue())
                                    @include('partials.row-actions', ['edit' => route('finance.edit', $t), 'destroy' => route('finance.destroy', $t), 'confirm' => 'Hapus penjualan panen ini?'])
                                @else
                                    <a href="{{ route('feed.index') }}" class="text-xs text-slate-500 underline">Kelola di Stok Pakan</a>
                                @endif
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr><td colspan="{{ $canWrite ? 7 : 6 }}" class="px-4 py-6 text-slate-500">Belum ada transaksi pada periode ini.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $history->links() }}</div>
</section>
@endsection

@push('scripts')
<script>
(function () {
    const w = @json($weekly);
    new Chart(document.getElementById('financeChart'), {
        type: 'bar',
        data: {
            labels: w.labels,
            datasets: [
                { label: 'Pendapatan panen', data: w.revenue, backgroundColor: '#23805a', borderRadius: 4, maxBarThickness: 36 },
                { label: 'Pembelian pakan', data: w.purchase, backgroundColor: '#a9c6cf', borderRadius: 4, maxBarThickness: 36 }
            ]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            scales: { y: { beginAtZero: true, ticks: { callback: v => v + ' jt' } }, x: { grid: { display: false } } },
            plugins: {
                legend: { position: 'bottom', labels: { boxWidth: 10, usePointStyle: true } },
                tooltip: { callbacks: { label: c => c.dataset.label + ': Rp' + new Intl.NumberFormat('id-ID').format(c.parsed.y * 1000000) } }
            }
        }
    });
})();
</script>
@endpush
