@extends('layouts.app')
@section('title', 'Jenis Pakan')
@section('crumb', 'Jenis Pakan')

@section('content')
<h1 class="text-3xl font-normal tracking-tight">Jenis pakan</h1>
<p class="mt-2 text-sm text-slate-500">Kelola jenis pakan, stok minimum, dan harga acuan. Jumlah stok berubah lewat transaksi di halaman Stok Pakan.</p>

<div class="mt-6 grid gap-4 lg:grid-cols-3">
    <section class="rounded-xl border border-slate-200 bg-white p-6 lg:col-span-2">
        <h2 class="font-medium">Daftar jenis pakan</h2>
        <div class="mt-4 overflow-x-auto">
            <table class="w-full min-w-[620px] text-left text-sm">
                <thead class="bg-slate-50 text-xs text-slate-500">
                    <tr><th class="px-4 py-3 font-medium">Jenis pakan</th><th class="px-4 py-3 font-medium">Stok</th><th class="px-4 py-3 font-medium">Minimum</th><th class="px-4 py-3 font-medium">Harga / kg</th><th class="px-4 py-3 font-medium">Transaksi</th><th class="px-4 py-3 font-medium">Aksi</th></tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($stocks as $s)
                        <tr>
                            <td class="px-4 py-3.5 font-medium">{{ $s->feed_type }}</td>
                            <td class="px-4 py-3.5">@num($s->total_stock) kg</td>
                            <td class="px-4 py-3.5 text-slate-500">@num($s->minimum_stock) kg</td>
                            <td class="px-4 py-3.5 text-slate-500">@rp($s->price_per_kg)</td>
                            <td class="px-4 py-3.5 text-slate-500">{{ $s->transactions_count }}</td>
                            <td class="px-4 py-3.5">@include('partials.row-actions', ['edit' => route('feedstocks.edit', $s), 'destroy' => route('feedstocks.destroy', $s), 'confirm' => 'Hapus '.$s->feed_type.'?'])</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-6 text-slate-500">Belum ada jenis pakan.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="rounded-xl border border-slate-200 bg-white p-6">
        <h2 class="font-medium">Tambah jenis pakan</h2>
        <form method="POST" action="{{ route('feedstocks.store') }}" class="mt-4 space-y-4">
            @csrf
            @include('feedstocks._fields', ['stock' => null])
            <div>
                <label class="text-sm font-medium" for="total_stock">Stok awal (kg)</label>
                <input id="total_stock" name="total_stock" type="number" min="0" step="any" required value="{{ old('total_stock', 0) }}" class="mt-2 w-full rounded-lg border border-slate-200 px-3 py-3 text-sm">
            </div>
            <button class="inline-flex items-center gap-2 rounded-lg bg-brand-600 px-5 py-3 text-sm font-medium text-white hover:bg-brand-700">@include('partials.icon', ['name' => 'plus', 'class' => 'h-4 w-4']) Simpan jenis pakan</button>
        </form>
    </section>
</div>
@endsection
