@extends('layouts.app')
@section('title', 'Ubah Penjualan Panen')
@section('crumb', 'Laba Rugi / Ubah')

@section('content')
<h1 class="text-3xl font-normal tracking-tight">Ubah penjualan panen</h1>
<section class="mt-6 max-w-lg rounded-xl border border-slate-200 bg-white p-6">
    <form method="POST" action="{{ route('finance.update', $tx) }}" class="space-y-4"
          x-data="{ w: '{{ old('weight_kg', $tx->weight_kg) }}', p: '{{ old('price_per_kg', $tx->price_per_kg) }}', get total() { return (parseFloat(this.w) || 0) * (parseFloat(this.p) || 0); } }">
        @csrf @method('PUT')
        <div>
            <label class="text-sm font-medium" for="pond_id">Kolam panen</label>
            <select id="pond_id" name="pond_id" class="mt-2 w-full rounded-lg border border-slate-200 px-3 py-3 text-sm">
                @foreach ($ponds as $p)<option value="{{ $p->id }}" @selected(old('pond_id', $tx->pond_id) == $p->id)>{{ $p->name }} • {{ $p->fish_type }}</option>@endforeach
            </select>
        </div>
        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="text-sm font-medium" for="weight_kg">Berat panen (kg)</label>
                <input id="weight_kg" name="weight_kg" type="number" min="0.01" step="any" required x-model="w" class="mt-2 w-full rounded-lg border border-slate-200 px-3 py-3 text-sm">
            </div>
            <div>
                <label class="text-sm font-medium" for="price_per_kg">Harga / kg (Rp)</label>
                <input id="price_per_kg" name="price_per_kg" type="number" min="1" step="any" required x-model="p" class="mt-2 w-full rounded-lg border border-slate-200 px-3 py-3 text-sm">
            </div>
        </div>
        <div>
            <label class="text-sm font-medium" for="transaction_date">Tanggal penjualan</label>
            <input id="transaction_date" name="transaction_date" type="date" required max="{{ now()->toDateString() }}" value="{{ old('transaction_date', $tx->transaction_date->toDateString()) }}" class="mt-2 w-full rounded-lg border border-slate-200 px-3 py-3 text-sm">
        </div>
        <label class="flex items-center gap-2 text-xs text-slate-500"><input type="checkbox" name="mark_harvested" value="1" class="h-4 w-4 rounded border-slate-300 text-brand-600"> Tandai kolam sudah panen</label>
        <div class="flex justify-between text-sm"><span class="text-slate-500">Total pendapatan</span><span class="font-medium" x-text="'Rp' + new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 }).format(total)"></span></div>
        <div class="flex gap-3">
            <button class="rounded-lg bg-brand-600 px-5 py-3 text-sm font-medium text-white hover:bg-brand-700">Simpan perubahan</button>
            <a href="{{ route('finance.index', ['month' => $tx->transaction_date->format('Y-m')]) }}" class="rounded-lg border border-slate-200 px-5 py-3 text-sm font-medium hover:bg-slate-50">Batal</a>
        </div>
    </form>
</section>
@endsection
