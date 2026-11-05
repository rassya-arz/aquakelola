@extends('layouts.app')
@section('title', 'Ubah Jenis Pakan')
@section('crumb', 'Jenis Pakan / Ubah')

@section('content')
<h1 class="text-3xl font-normal tracking-tight">Ubah {{ $stock->feed_type }}</h1>
<p class="mt-2 text-sm text-slate-500">Stok saat ini @num($stock->total_stock) kg. Untuk mengubah jumlah, catat transaksi pakan masuk atau keluar.</p>
<section class="mt-6 max-w-lg rounded-xl border border-slate-200 bg-white p-6">
    <form method="POST" action="{{ route('feedstocks.update', $stock) }}" class="space-y-4">
        @csrf @method('PUT')
        @include('feedstocks._fields', ['stock' => $stock])
        <div class="flex gap-3">
            <button class="rounded-lg bg-brand-600 px-5 py-3 text-sm font-medium text-white hover:bg-brand-700">Simpan perubahan</button>
            <a href="{{ route('feedstocks.index') }}" class="rounded-lg border border-slate-200 px-5 py-3 text-sm font-medium hover:bg-slate-50">Batal</a>
        </div>
    </form>
</section>
@endsection
