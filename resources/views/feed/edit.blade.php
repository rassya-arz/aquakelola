@extends('layouts.app')
@section('title', 'Ubah Transaksi Pakan')
@section('crumb', 'Stok Pakan / Ubah')

@section('content')
<h1 class="text-3xl font-normal tracking-tight">Ubah transaksi {{ $t->code() }}</h1>
<p class="mt-2 text-sm text-slate-500">Efek transaksi lama pada stok dibatalkan, lalu efek baru diterapkan. Perubahan ditolak bila stok menjadi negatif.</p>

<section class="mt-6 max-w-2xl rounded-xl border border-slate-200 bg-white p-6" x-data="{ type: '{{ old('type', $t->type) }}' }">
    <form method="POST" action="{{ route('feed.update', $t) }}" class="space-y-4">
        @csrf @method('PUT')
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="text-sm font-medium" for="type">Transaksi</label>
                <select id="type" name="type" x-model="type" class="mt-2 w-full rounded-lg border border-slate-200 px-3 py-3 text-sm">
                    <option value="keluar">Pakan keluar (pemakaian)</option>
                    <option value="masuk">Pakan masuk (pembelian)</option>
                </select>
            </div>
            <div>
                <label class="text-sm font-medium" for="feed_id">Jenis pakan</label>
                <select id="feed_id" name="feed_id" class="mt-2 w-full rounded-lg border border-slate-200 px-3 py-3 text-sm">
                    @foreach ($stocks as $s)<option value="{{ $s->id }}" @selected(old('feed_id', $t->feed_id) == $s->id)>{{ $s->feed_type }} (stok @num($s->total_stock) kg)</option>@endforeach
                </select>
            </div>
            <div x-show="type==='keluar'">
                <label class="text-sm font-medium" for="pond_id">Kolam tujuan</label>
                <select id="pond_id" name="pond_id" :disabled="type!=='keluar'" class="mt-2 w-full rounded-lg border border-slate-200 px-3 py-3 text-sm">
                    @foreach ($ponds as $p)<option value="{{ $p->id }}" @selected(old('pond_id', $t->pond_id) == $p->id)>{{ $p->name }} • {{ $p->fish_type }}</option>@endforeach
                </select>
            </div>
            <div x-show="type==='masuk'" x-cloak>
                <label class="text-sm font-medium" for="price_per_kg">Harga beli / kg (Rp)</label>
                <input id="price_per_kg" name="price_per_kg" type="number" min="0" step="any" :disabled="type!=='masuk'" value="{{ old('price_per_kg', $price) }}" class="mt-2 w-full rounded-lg border border-slate-200 px-3 py-3 text-sm">
            </div>
        </div>
        <div>
            <label class="text-sm font-medium" for="quantity">Jumlah (kg)</label>
            <input id="quantity" name="quantity" type="number" min="0.01" step="any" required value="{{ old('quantity', $t->quantity) }}" class="mt-2 w-full rounded-lg border border-slate-200 px-3 py-3 text-sm">
        </div>
        <div>
            <label class="text-sm font-medium" for="notes">Catatan</label>
            <input id="notes" name="notes" type="text" maxlength="255" value="{{ old('notes', $t->notes) }}" class="mt-2 w-full rounded-lg border border-slate-200 px-3 py-3 text-sm">
        </div>
        <div class="flex gap-3">
            <button class="rounded-lg bg-brand-600 px-5 py-3 text-sm font-medium text-white hover:bg-brand-700">Simpan perubahan</button>
            <a href="{{ route('feed.index') }}" class="rounded-lg border border-slate-200 px-5 py-3 text-sm font-medium hover:bg-slate-50">Batal</a>
        </div>
    </form>
</section>
@endsection
