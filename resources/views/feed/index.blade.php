@extends('layouts.app')
@section('title', 'Stok Pakan')
@section('crumb', 'Stok Pakan')

@section('content')
@php
    $stockJson = $stocks->mapWithKeys(fn ($s) => [$s->id => [
        'name' => $s->feed_type, 'stock' => $s->total_stock, 'min' => $s->minimum_stock, 'price' => $s->price_per_kg,
    ]]);
@endphp

<h1 class="text-3xl font-normal tracking-tight">Stok pakan</h1>
<p class="mt-2 text-sm text-slate-500">Pantau persediaan dan catat pakan masuk atau pemakaian harian.</p>

<div class="mt-6 grid gap-4 sm:grid-cols-3">
    <div class="rounded-xl border border-slate-200 bg-white p-5">
        <p class="text-sm text-slate-500">Total stok</p>
        <p class="mt-3 text-3xl font-medium text-brand-600">@num($totalStock) kg</p>
    </div>
    <div class="rounded-xl border border-slate-200 bg-white p-5">
        <p class="text-sm text-slate-500">Terpakai bulan ini</p>
        <p class="mt-3 text-3xl font-medium">@num($usedThisMonth) kg</p>
    </div>
    <div class="rounded-xl border border-slate-200 bg-white p-5">
        <p class="text-sm text-slate-500">Biaya pembelian bulan ini</p>
        <p class="mt-3 text-3xl font-medium">@rp($purchaseCost)</p>
    </div>
</div>

{{-- Tabel persediaan --}}
<section class="mt-6 rounded-xl border border-slate-200 bg-white p-6">
    <h2 class="font-medium">Persediaan pakan</h2>
    <div class="mt-4 overflow-x-auto">
        <table class="w-full min-w-[640px] text-left text-sm">
            <thead class="bg-slate-50 text-xs text-slate-500">
                <tr><th class="px-4 py-3 font-medium">Jenis pakan</th><th class="px-4 py-3 font-medium">Stok</th><th class="px-4 py-3 font-medium">Minimum</th><th class="px-4 py-3 font-medium">Harga / kg</th><th class="px-4 py-3 font-medium">Status</th></tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($stocks as $s)
                    @php $st = $s->stockStatus(); @endphp
                    <tr>
                        <td class="px-4 py-4 font-medium">{{ $s->feed_type }}</td>
                        <td class="px-4 py-4">@num($s->total_stock) kg</td>
                        <td class="px-4 py-4 text-slate-500">@num($s->minimum_stock) kg</td>
                        <td class="px-4 py-4 text-slate-500">@rp($s->price_per_kg)</td>
                        <td class="px-4 py-4">
                            <span class="rounded-md px-2.5 py-1 text-xs font-medium
                                {{ $st === 'tersedia' ? 'bg-brand-50 text-brand-700' : ($st === 'rendah' ? 'bg-amber-50 text-amber-700' : 'bg-red-50 text-red-600') }}">
                                {{ ['tersedia' => 'Tersedia', 'rendah' => 'Stok rendah', 'habis' => 'Habis'][$st] }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-6 text-slate-500">Belum ada jenis pakan.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>

{{-- Form catat transaksi + pratinjau --}}
<div class="mt-6 grid gap-4 lg:grid-cols-5"
     x-data="feedForm({{ Js::from($stockJson) }}, {{ Js::from(['type' => old('type', 'keluar'), 'feed_id' => old('feed_id'), 'quantity' => old('quantity'), 'price' => old('price_per_kg')]) }})">
    <form method="POST" action="{{ route('feed.store') }}" class="rounded-xl border border-slate-200 bg-white p-6 lg:col-span-3">
        @csrf
        <h2 class="font-medium">Catat transaksi</h2>
        <p class="mt-1 text-xs text-slate-500" x-text="type==='keluar' ? 'Pemakaian mengurangi stok, bukan mencatat pembelian baru.' : 'Pakan masuk menambah stok dan otomatis tercatat sebagai pembelian di Laba Rugi.'"></p>

        <div class="mt-5 grid grid-cols-2 gap-1 rounded-xl bg-slate-100 p-1">
            <button type="button" @click="type='keluar'" :class="type==='keluar' ? 'bg-white text-brand-700 shadow-sm ring-1 ring-slate-200 font-medium' : 'text-slate-500'" class="rounded-lg py-2 text-sm">Pakan keluar</button>
            <button type="button" @click="type='masuk'" :class="type==='masuk' ? 'bg-white text-brand-700 shadow-sm ring-1 ring-slate-200 font-medium' : 'text-slate-500'" class="rounded-lg py-2 text-sm">Pakan masuk</button>
        </div>
        <input type="hidden" name="type" :value="type">

        <div class="mt-5 grid gap-4 sm:grid-cols-2">
            <div>
                <label for="feed_id" class="text-sm font-medium">Jenis pakan</label>
                <select id="feed_id" name="feed_id" x-model="feedId" class="mt-2 w-full rounded-lg border border-slate-200 px-3 py-3 text-sm">
                    @foreach ($stocks as $s)<option value="{{ $s->id }}">{{ $s->feed_type }}</option>@endforeach
                </select>
            </div>
            <div x-show="type==='keluar'">
                <label for="pond_id" class="text-sm font-medium">Kolam tujuan</label>
                <select id="pond_id" name="pond_id" :disabled="type!=='keluar'" class="mt-2 w-full rounded-lg border border-slate-200 px-3 py-3 text-sm">
                    @foreach ($ponds as $p)<option value="{{ $p->id }}" @selected(old('pond_id') == $p->id)>{{ $p->name }} • {{ $p->fish_type }}</option>@endforeach
                </select>
            </div>
            <div x-show="type==='masuk'" x-cloak>
                <label for="price_per_kg" class="text-sm font-medium">Harga beli / kg (Rp)</label>
                <input id="price_per_kg" name="price_per_kg" type="number" min="0" step="any" x-model="price" :disabled="type!=='masuk'" :placeholder="s.price"
                       class="mt-2 w-full rounded-lg border border-slate-200 px-3 py-3 text-sm">
            </div>
        </div>

        <div class="mt-4">
            <label for="quantity" class="text-sm font-medium">Jumlah</label>
            <div class="relative mt-2">
                <input id="quantity" name="quantity" type="number" min="0.01" step="any" x-model="qty" required
                       class="w-full rounded-lg border px-3 py-3 pr-12 text-sm" :class="invalid ? 'border-red-400' : 'border-slate-200'">
                <span class="absolute inset-y-0 right-4 flex items-center text-sm text-slate-400">kg</span>
            </div>
            <p class="mt-2 text-xs" :class="invalid ? 'text-red-600' : 'text-slate-500'"
               x-text="invalid ? 'Jumlah melebihi stok tersedia (' + fmt(s.stock) + ' kg).' : 'Stok saat ini ' + fmt(s.stock) + ' kg.' + (type==='keluar' ? ' Jumlah pemakaian tidak boleh melebihi stok.' : '')"></p>
        </div>

        <div class="mt-4">
            <label for="notes" class="text-sm font-medium">Catatan</label>
            <input id="notes" name="notes" type="text" maxlength="255" value="{{ old('notes') }}" placeholder="Contoh: Pemberian pakan siang untuk Kolam 01."
                   class="mt-2 w-full rounded-lg border border-slate-200 px-3 py-3 text-sm">
        </div>

        <div class="mt-6 flex justify-end gap-3">
            <button type="reset" @click="qty=''; price=''" class="rounded-lg border border-slate-200 px-5 py-2.5 text-sm font-medium hover:bg-slate-50">Batal</button>
            <button type="submit" :disabled="invalid || q <= 0" class="inline-flex items-center gap-2 rounded-lg bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700 disabled:cursor-not-allowed disabled:opacity-50">
                @include('partials.icon', ['name' => 'check', 'class' => 'h-4 w-4'])
                <span x-text="type==='keluar' ? 'Simpan pemakaian' : 'Simpan pakan masuk'"></span>
            </button>
        </div>
    </form>

    <aside class="space-y-4 lg:col-span-2">
        <div class="rounded-xl border border-slate-200 bg-white p-6">
            <h2 class="font-medium">Pratinjau perubahan stok</h2>
            <p class="mt-1 text-xs text-slate-500">Sebelum → sesudah disimpan</p>
            <div class="mt-4 flex items-center gap-3">
                <div class="flex-1 rounded-xl bg-slate-50 p-4"><p class="text-xs text-slate-500">Sebelum</p><p class="mt-1 text-2xl font-medium" x-text="fmt(s.stock) + ' kg'"></p></div>
                <span class="text-slate-400">→</span>
                <div class="flex-1 rounded-xl p-4" :class="invalid ? 'bg-red-50' : 'bg-brand-50'">
                    <p class="text-xs text-slate-500">Sesudah</p>
                    <p class="mt-1 text-2xl font-medium" :class="invalid ? 'text-red-600' : 'text-brand-600'" x-text="invalid ? '—' : fmt(after) + ' kg'"></p>
                </div>
            </div>
            <p class="mt-4 text-sm" x-text="s.name + ': ' + fmt(s.stock) + ' ' + (type==='keluar' ? '−' : '+') + ' ' + fmt(q) + ' = ' + (invalid ? 'tidak valid' : fmt(after) + ' kg')"></p>
            <div class="mt-4 flex justify-between border-t border-slate-100 pt-4 text-sm">
                <span class="text-slate-500">Total seluruh pakan</span>
                <span class="font-medium" x-text="fmt(totalBefore) + ' → ' + (invalid ? '—' : fmt(totalAfter)) + ' kg'"></span>
            </div>
            <p x-show="!invalid && q > 0 && after < s.min" x-cloak class="mt-3 rounded-lg bg-amber-50 p-3 text-xs text-amber-700"
               x-text="'Setelah transaksi, ' + s.name + ' (' + fmt(after) + ' kg) berada di bawah stok minimum ' + fmt(s.min) + ' kg.'"></p>
            <p x-show="!invalid && q > 0 && after >= s.min" x-cloak class="mt-3 rounded-lg bg-brand-50 p-3 text-xs text-brand-700"
               x-text="'Stok mencukupi; masih di atas minimum ' + fmt(s.min) + ' kg.'"></p>
            <p x-show="type==='masuk' && q > 0" x-cloak class="mt-3 text-xs text-slate-500"
               x-text="'Biaya pembelian: ' + rupiah(q * (parseFloat(price) || s.price))"></p>
        </div>
        <div class="rounded-xl bg-brand-50/60 p-4 text-xs leading-relaxed text-slate-600">
            Pemakaian pakan tidak mengubah laporan biaya pembelian. Hanya pakan masuk yang dicatat sebagai biaya.
        </div>
    </aside>
</div>

{{-- Riwayat --}}
<section class="mt-6 rounded-xl border border-slate-200 bg-white p-6">
    <h2 class="font-medium">Riwayat pakan</h2>
    <div class="mt-4 overflow-x-auto">
        <table class="w-full min-w-[720px] text-left text-sm">
            <thead class="bg-slate-50 text-xs text-slate-500">
                <tr><th class="px-4 py-3 font-medium">Kode</th><th class="px-4 py-3 font-medium">Waktu</th><th class="px-4 py-3 font-medium">Jenis pakan</th><th class="px-4 py-3 font-medium">Transaksi</th><th class="px-4 py-3 font-medium">Jumlah</th><th class="px-4 py-3 font-medium">Tujuan</th><th class="px-4 py-3 font-medium">Pencatat</th><th class="px-4 py-3 font-medium">Aksi</th></tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($history as $t)
                    <tr>
                        <td class="px-4 py-3.5 text-slate-500">{{ $t->code() }}</td>
                        <td class="px-4 py-3.5">{{ $t->created_at->translatedFormat('d M • H.i') }}</td>
                        <td class="px-4 py-3.5">{{ $t->feed->feed_type }}</td>
                        <td class="px-4 py-3.5">{{ $t->type === 'keluar' ? 'Pemakaian' : 'Pakan masuk' }}</td>
                        <td class="px-4 py-3.5 font-medium {{ $t->type === 'keluar' ? 'text-slate-700' : 'text-brand-600' }}">{{ $t->type === 'keluar' ? '−' : '+' }}@num($t->quantity) kg</td>
                        <td class="px-4 py-3.5 text-slate-500">{{ $t->pond?->name ?? 'Gudang' }}</td>
                        <td class="px-4 py-3.5 text-slate-500">{{ $t->user->name }}</td>
                        <td class="px-4 py-3.5">@include('partials.row-actions', ['edit' => route('feed.edit', $t), 'destroy' => route('feed.destroy', $t), 'confirm' => 'Hapus transaksi '.$t->code().'? Stok akan dikembalikan.'])</td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="px-4 py-6 text-slate-500">Belum ada transaksi pakan. Catat pemakaian atau pakan masuk pertama Anda di atas.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $history->links() }}</div>
</section>
@endsection

@push('scripts')
<script>
function feedForm(stocks, old) {
    return {
        stocks, type: old.type || 'keluar',
        feedId: old.feed_id || Object.keys(stocks)[0],
        qty: old.quantity || '', price: old.price || '',
        get s() { return this.stocks[this.feedId] || { name: '', stock: 0, min: 0, price: 0 }; },
        get q() { return parseFloat(this.qty) || 0; },
        get delta() { return this.type === 'keluar' ? -this.q : this.q; },
        get after() { return this.s.stock + this.delta; },
        get invalid() { return this.type === 'keluar' && this.q > this.s.stock; },
        get totalBefore() { return Object.values(this.stocks).reduce((a, b) => a + b.stock, 0); },
        get totalAfter() { return this.totalBefore + this.delta; },
        fmt(n) { return new Intl.NumberFormat('id-ID', { maximumFractionDigits: 2 }).format(n); },
        rupiah(n) { return 'Rp' + new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 }).format(n); }
    };
}
</script>
@endpush
