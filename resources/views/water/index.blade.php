@extends('layouts.app')
@section('title', 'Pengecekan pH Air')
@section('crumb', 'Pengecekan pH Air')

@php
    $canWrite = auth()->user()->isPengelola();
    $range = \App\Support\Fmt::num($minPh).'–'.\App\Support\Fmt::num($maxPh);
@endphp

@section('content')
<h1 class="text-3xl font-normal tracking-tight">Pengecekan pH air</h1>
<p class="mt-2 text-sm text-slate-500">Catat hasil pengukuran manual dan kenali perubahan kualitas air lebih awal.</p>

{{-- Peringatan aktif: hilang otomatis setelah ada pengukuran ulang dalam batas --}}
@foreach ($alerts as $pond)
    @php $w = $pond->latestWater; @endphp
    <section class="mt-6 flex items-start gap-3 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">
        @include('partials.icon', ['name' => 'alert', 'class' => 'mt-0.5 h-5 w-5 shrink-0'])
        <div>
            <p class="font-medium">Peringatan aktif: {{ $pond->name }} pH @num($w->ph_level) {{ $w->ph_level > $maxPh ? '> batas atas' : '< batas bawah' }} {{ \App\Support\Fmt::num($w->ph_level > $maxPh ? $maxPh : $minPh) }}</p>
            <p class="mt-1 text-xs">Pengukuran {{ $w->measured_at->translatedFormat('d M Y, H.i') }}. Peringatan hilang setelah pengukuran ulang berada dalam batas {{ $range }}.</p>
        </div>
    </section>
@endforeach

{{-- Kartu per kolam --}}
<div class="mt-6 grid gap-4 md:grid-cols-3">
    @foreach ($ponds as $pond)
        @php $w = $pond->isAktif() ? $pond->latestWater : null; @endphp
        <div class="rounded-xl border bg-white p-5 {{ $w && $w->isOutOfRange() ? 'border-red-200' : 'border-slate-200' }} {{ $pond->isPanen() ? 'opacity-70' : '' }}">
            <div class="flex items-start justify-between">
                <div>
                    <p class="font-medium">{{ $pond->name }}</p>
                    <p class="mt-1 text-xs text-slate-500">{{ $pond->fish_type }}{{ $w ? ' • '.$w->measured_at->translatedFormat('d M Y, H.i') : '' }}</p>
                </div>
                @if ($w)
                    <span class="rounded-md px-2.5 py-1 text-xs font-medium {{ $w->isOutOfRange() ? 'bg-red-50 text-red-600' : 'bg-brand-50 text-brand-700' }}">{{ $w->isOutOfRange() ? 'Di luar batas' : 'Dalam batas' }}</span>
                @endif
            </div>
            @if ($w)
                <div class="mt-5"><p class="text-4xl font-light">@num($w->ph_level)</p><p class="mt-1 text-xs text-slate-500">pH air</p></div>
            @elseif ($pond->isPanen())
                <p class="mt-5 text-sm text-slate-500">Kolam berstatus panen. Monitoring pH nonaktif sampai status diubah menjadi Aktif.</p>
            @else
                <p class="mt-5 text-sm text-slate-500">Belum ada pengukuran.</p>
            @endif
            <p class="mt-5 text-xs text-slate-400">Batas: pH {{ $range }}</p>
        </div>
    @endforeach
</div>

{{-- Grafik tren + form input (form hanya untuk pengelola) --}}
<div class="mt-6 grid gap-4 lg:grid-cols-3">
    <section class="rounded-xl border border-slate-200 bg-white p-6 {{ $canWrite ? 'lg:col-span-2' : 'lg:col-span-3' }}">
        <div class="flex items-start justify-between">
            <div>
                <h2 class="font-medium">Grafik tren pH</h2>
                <p class="mt-1 text-xs text-slate-500">Rata-rata harian per kolam • area hijau: batas {{ $range }} • titik merah: di luar batas</p>
            </div>
            <span class="rounded-md bg-slate-100 px-2.5 py-1 text-xs text-slate-500">7 hari terakhir</span>
        </div>
        <div class="mt-4">@include('partials.ph-chart', ['id' => 'waterPhChart', 'trend' => $trend])</div>
    </section>

    @if ($canWrite)
    <section class="rounded-xl border border-slate-200 bg-white p-6"
             x-data="{ ph: '{{ old('ph_level') }}', min: {{ $minPh }}, max: {{ $maxPh }},
                       get has() { return this.ph !== '' && !isNaN(parseFloat(this.ph)); },
                       get out() { const v = parseFloat(this.ph); return this.has && (v < this.min || v > this.max); } }">
        <h2 class="font-medium">Catat pengukuran</h2>
        <p class="mt-1 text-xs text-slate-500">Input manual dari alat ukur Anda</p>
        <form method="POST" action="{{ route('water.store') }}" class="mt-4 space-y-4">
            @csrf
            @if ($activePonds->isEmpty())
                <p class="rounded-lg bg-slate-50 px-3 py-3 text-sm text-slate-600">Tidak ada kolam aktif. Ubah status kolam menjadi Aktif di Data Kolam untuk mulai monitoring pH.</p>
            @endif
            <div>
                <label class="text-sm font-medium" for="pond_id">Kolam</label>
                <select id="pond_id" name="pond_id" required class="mt-2 w-full rounded-lg border border-slate-200 px-3 py-3 text-sm">
                    @foreach ($activePonds as $p)<option value="{{ $p->id }}" @selected(old('pond_id') == $p->id)>{{ $p->name }} • {{ $p->fish_type }}</option>@endforeach
                </select>
            </div>
            <div>
                <label class="text-sm font-medium" for="ph_level">pH air</label>
                <input id="ph_level" name="ph_level" type="number" step="0.01" min="0" max="14" required x-model="ph" placeholder="Masukkan pH"
                       class="mt-2 w-full rounded-lg border px-3 py-3 text-sm" :class="out ? 'border-red-400' : 'border-slate-200'">
            </div>
            <div>
                <label class="text-sm font-medium" for="measured_at">Waktu pengukuran</label>
                <input id="measured_at" name="measured_at" type="datetime-local" max="{{ now()->format('Y-m-d\TH:i') }}" value="{{ old('measured_at', now()->format('Y-m-d\TH:i')) }}"
                       class="mt-2 w-full rounded-lg border border-slate-200 px-3 py-3 text-sm">
            </div>
            <p x-show="has && !out" x-cloak class="rounded-lg bg-brand-50 p-3 text-xs text-brand-700">pH ini berada dalam batas {{ $range }}.</p>
            <p x-show="out" x-cloak class="rounded-lg bg-red-50 p-3 text-xs text-red-700">pH ini di luar batas {{ $range }}. Setelah disimpan, peringatan akan muncul di halaman ini.</p>
            <button class="inline-flex items-center gap-2 rounded-lg bg-brand-600 px-5 py-3 text-sm font-medium text-white hover:bg-brand-700">
                @include('partials.icon', ['name' => 'plus', 'class' => 'h-4 w-4']) Simpan pengukuran
            </button>
        </form>
    </section>
    @endif
</div>

{{-- Riwayat pengukuran --}}
<section class="mt-6 rounded-xl border border-slate-200 bg-white p-6">
    <h2 class="font-medium">Riwayat pengukuran</h2>
    <div class="mt-4 overflow-x-auto">
        <table class="w-full min-w-[640px] text-left text-sm">
            <thead class="bg-slate-50 text-xs text-slate-500">
                <tr>
                    <th class="px-4 py-3 font-medium">Waktu</th>
                    <th class="px-4 py-3 font-medium">Kolam</th>
                    <th class="px-4 py-3 font-medium">pH air</th>
                    <th class="px-4 py-3 font-medium">Status</th>
                    <th class="px-4 py-3 font-medium">Pencatat</th>
                    @if ($canWrite)<th class="px-4 py-3 font-medium">Aksi</th>@endif
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($history as $r)
                    <tr>
                        <td class="px-4 py-3.5">{{ $r->measured_at->translatedFormat('d M • H.i') }}</td>
                        <td class="px-4 py-3.5">{{ $r->pond->name }}</td>
                        <td class="px-4 py-3.5">@num($r->ph_level)</td>
                        <td class="px-4 py-3.5"><span class="rounded-md px-2.5 py-1 text-xs font-medium {{ $r->isOutOfRange() ? 'bg-red-50 text-red-600' : 'bg-brand-50 text-brand-700' }}">{{ $r->isOutOfRange() ? 'Di luar batas' : 'Dalam batas' }}</span></td>
                        <td class="px-4 py-3.5 text-slate-500">{{ $r->user->name }}</td>
                        @if ($canWrite)
                            <td class="px-4 py-3.5">@include('partials.row-actions', ['destroy' => route('water.destroy', $r), 'confirm' => 'Hapus pengukuran ini?'])</td>
                        @endif
                    </tr>
                @empty
                    <tr><td colspan="{{ $canWrite ? 6 : 5 }}" class="px-4 py-6 text-slate-500">Belum ada pengukuran.{{ $canWrite ? ' Catat pH pertama Anda melalui formulir di atas.' : '' }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $history->links() }}</div>
</section>
@endsection
