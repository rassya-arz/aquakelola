@extends('layouts.app')
@section('title', 'Home')
@section('crumb', 'Home')

@section('content')
<div class="flex flex-wrap items-start justify-between gap-4">
    <div>
        <h1 class="text-3xl font-normal tracking-tight">Gambaran budidaya Anda</h1>
        <p class="mt-2 text-sm text-slate-500">Pantau ketersediaan pakan, kondisi kolam, dan hasil pencatatan bulan ini.</p>
    </div>
    <form method="GET" class="flex items-center gap-2">
        <label class="sr-only" for="month">Bulan</label>
        <input id="month" type="month" name="month" value="{{ $month->format('Y-m') }}" onchange="this.form.submit()"
               class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium">
    </form>
</div>

{{-- Kartu ringkasan --}}
<div class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
    <div class="rounded-xl border border-slate-200 bg-white p-5">
        <p class="text-sm text-slate-500">Stok pakan tersedia</p>
        <p class="mt-3 text-3xl font-medium">@num($totalStock) kg</p>
        <p class="mt-3 text-xs {{ $lowStocks->count() ? 'text-amber-600' : 'text-slate-500' }}">
            {{ $stocks->count() }} jenis pakan • {{ $lowStocks->count() }} stok di bawah minimum
        </p>
    </div>
    <div class="rounded-xl border border-slate-200 bg-white p-5">
        <p class="text-sm text-slate-500">Pendapatan panen</p>
        <p class="mt-3 text-3xl font-medium">@rp($revenue)</p>
        <p class="mt-3 text-xs text-slate-500">{{ $salesCount }} penjualan panen • @num($soldKg) kg ikan</p>
    </div>
    <div class="rounded-xl border border-slate-200 bg-white p-5">
        <p class="text-sm text-slate-500">Biaya pembelian pakan</p>
        <p class="mt-3 text-3xl font-medium">@rp($purchase)</p>
        <p class="mt-3 text-xs text-slate-500">{{ $purchaseCount }} pembelian • @num($purchaseKg) kg pakan</p>
    </div>
    <div class="rounded-xl border border-slate-200 bg-white p-5">
        <p class="text-sm text-slate-500">Selisih panen – pakan</p>
        <p class="mt-3 text-3xl font-medium {{ $profit >= 0 ? 'text-brand-600' : 'text-red-600' }}">{{ $profit < 0 ? '-' : '' }}@rp(abs($profit))</p>
        <p class="mt-3 text-xs text-slate-500">Tidak mencakup biaya operasional lain</p>
    </div>
</div>

{{-- Tren pH + kondisi kolam --}}
<div class="mt-6 grid gap-4 lg:grid-cols-3">
    <section class="rounded-xl border border-slate-200 bg-white p-6 lg:col-span-2">
        <div class="flex items-start justify-between">
            <div>
                <h2 class="font-medium">Riwayat pH air</h2>
                <p class="mt-1 text-xs text-slate-500">Pencatatan manual • area hijau: batas 6,5–8,5</p>
            </div>
            <span class="rounded-md bg-slate-100 px-2.5 py-1 text-xs text-slate-500">7 hari terakhir</span>
        </div>
        <div class="mt-4">@include('partials.ph-chart', ['id' => 'dashPhChart', 'trend' => $trend])</div>
    </section>

    <section class="rounded-xl border border-slate-200 bg-white p-6">
        <h2 class="font-medium">Kondisi kolam</h2>
        <p class="mt-1 text-xs text-slate-500">Pembacaan terakhir</p>
        <ul class="mt-4 divide-y divide-slate-100">
            @forelse ($ponds as $pond)
                @php $w = $pond->latestWater; @endphp
                <li class="flex items-center justify-between py-4">
                    <div>
                        <p class="text-sm font-medium">{{ $pond->name }}</p>
                        <p class="text-xs text-slate-500">{{ $pond->fish_type }}</p>
                    </div>
                    @if ($w)
                        <div class="text-right text-sm">
                            <p>pH @num($w->ph_level)</p>
                            <p class="text-xs text-slate-500">@num($w->temperature) °C</p>
                        </div>
                        <span class="rounded-md px-2.5 py-1 text-xs font-medium {{ $w->isOutOfRange() ? 'bg-red-50 text-red-600' : 'bg-brand-50 text-brand-700' }}">
                            {{ $w->isOutOfRange() ? 'Di luar batas' : 'Dalam batas' }}
                        </span>
                    @else
                        <span class="text-xs text-slate-400">Belum ada data</span>
                    @endif
                </li>
            @empty
                <li class="py-6 text-sm text-slate-500">Belum ada kolam terdaftar.</li>
            @endforelse
        </ul>
    </section>
</div>

{{-- Perlu perhatian + aktivitas --}}
<div class="mt-6 grid gap-4 lg:grid-cols-3">
    <section class="rounded-xl border border-slate-200 bg-white p-6">
        <div class="flex items-center justify-between">
            <h2 class="font-medium">Perlu perhatian</h2>
            <span class="rounded-md px-2.5 py-1 text-xs font-medium {{ $lowStocks->count() ? 'bg-amber-50 text-amber-700' : 'bg-brand-50 text-brand-700' }}">
                {{ $lowStocks->count() ? $lowStocks->count().' peringatan' : 'Aman' }}
            </span>
        </div>
        @forelse ($lowStocks as $s)
            <div class="mt-5">
                <div class="flex justify-between text-sm font-medium"><span>{{ $s->feed_type }}</span><span class="text-amber-600">@num($s->total_stock) kg</span></div>
                <div class="mt-2 h-1.5 rounded-full bg-slate-100">
                    <div class="h-1.5 rounded-full bg-amber-400" style="width: {{ min(100, $s->minimum_stock > 0 ? round($s->total_stock / $s->minimum_stock * 100) : 0) }}%"></div>
                </div>
                <p class="mt-3 text-sm text-slate-500">Di bawah stok minimum @num($s->minimum_stock) kg. Catat pembelian baru untuk menambah persediaan.</p>
            </div>
        @empty
            <p class="mt-5 text-sm text-slate-500">Semua stok pakan masih di atas batas minimum.</p>
        @endforelse
        @if ($lowStocks->count() && auth()->user()->isPengelola())
            <a href="{{ route('feed.index') }}" class="mt-5 inline-flex items-center gap-2 rounded-lg border border-slate-200 px-4 py-2.5 text-sm font-medium hover:bg-slate-50">
                @include('partials.icon', ['name' => 'arrow', 'class' => 'h-4 w-4']) Lihat stok pakan
            </a>
        @endif
    </section>

    <section class="rounded-xl border border-slate-200 bg-white p-6 lg:col-span-2">
        <h2 class="font-medium">Aktivitas terakhir</h2>
        <ul class="mt-4 divide-y divide-slate-100">
            @forelse ($activities as $a)
                <li class="flex items-center gap-4 py-3.5">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-brand-50 text-brand-600">@include('partials.icon', ['name' => $a['icon']])</span>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-medium">{{ $a['title'] }}</p>
                        <p class="truncate text-xs text-slate-500">{{ $a['sub'] }}</p>
                    </div>
                    <span class="shrink-0 text-xs text-slate-400">{{ $a['at']->translatedFormat('d M • H.i') }}</span>
                </li>
            @empty
                <li class="py-6 text-sm text-slate-500">Belum ada aktivitas tercatat.</li>
            @endforelse
        </ul>
    </section>
</div>
@endsection
