@extends('layouts.app')
@section('title', 'Ubah Pengukuran')
@section('crumb', 'Pengecekan pH Air / Ubah')

@section('content')
<h1 class="text-3xl font-normal tracking-tight">Ubah pengukuran</h1>
<p class="mt-2 text-sm text-slate-500">Status dihitung ulang dari pH (batas {{ \App\Support\Fmt::num($minPh) }}–{{ \App\Support\Fmt::num($maxPh) }}). Bila pH menjadi dalam batas, catatan tindakan korektif dihapus.</p>

<section class="mt-6 max-w-lg rounded-xl border border-slate-200 bg-white p-6">
    <form method="POST" action="{{ route('water.update', $record) }}" class="space-y-4">
        @csrf @method('PUT')
        <div>
            <label class="text-sm font-medium" for="pond_id">Kolam</label>
            <select id="pond_id" name="pond_id" class="mt-2 w-full rounded-lg border border-slate-200 px-3 py-3 text-sm">
                @foreach ($ponds as $p)<option value="{{ $p->id }}" @selected(old('pond_id', $record->pond_id) == $p->id)>{{ $p->name }} • {{ $p->fish_type }}</option>@endforeach
            </select>
        </div>
        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="text-sm font-medium" for="ph_level">pH air</label>
                <input id="ph_level" name="ph_level" type="number" step="0.01" min="0" max="14" required value="{{ old('ph_level', $record->ph_level) }}" class="mt-2 w-full rounded-lg border border-slate-200 px-3 py-3 text-sm">
            </div>
            <div>
                <label class="text-sm font-medium" for="temperature">Suhu air (°C)</label>
                <input id="temperature" name="temperature" type="number" step="0.1" min="0" max="50" required value="{{ old('temperature', $record->temperature) }}" class="mt-2 w-full rounded-lg border border-slate-200 px-3 py-3 text-sm">
            </div>
        </div>
        <div>
            <label class="text-sm font-medium" for="measured_at">Waktu pengukuran</label>
            <input id="measured_at" name="measured_at" type="datetime-local" required max="{{ now()->format('Y-m-d\TH:i') }}" value="{{ old('measured_at', $record->measured_at->format('Y-m-d\TH:i')) }}" class="mt-2 w-full rounded-lg border border-slate-200 px-3 py-3 text-sm">
        </div>
        <div class="flex gap-3">
            <button class="rounded-lg bg-brand-600 px-5 py-3 text-sm font-medium text-white hover:bg-brand-700">Simpan perubahan</button>
            <a href="{{ route('water.index') }}" class="rounded-lg border border-slate-200 px-5 py-3 text-sm font-medium hover:bg-slate-50">Batal</a>
        </div>
    </form>
</section>
@endsection
