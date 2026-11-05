@extends('layouts.app')
@section('title', 'Data Kolam')
@section('crumb', 'Data Kolam')

@section('content')
<h1 class="text-3xl font-normal tracking-tight">Data kolam</h1>
<p class="mt-2 text-sm text-slate-500">Kelola kolam yang dipakai untuk pencatatan pakan, kualitas air, dan panen.</p>

<div class="mt-6 grid gap-4 lg:grid-cols-3">
    <section class="rounded-xl border border-slate-200 bg-white p-6 lg:col-span-2">
        <h2 class="font-medium">Daftar kolam</h2>
        <div class="mt-4 overflow-x-auto">
            <table class="w-full min-w-[560px] text-left text-sm">
                <thead class="bg-slate-50 text-xs text-slate-500">
                    <tr><th class="px-4 py-3 font-medium">Nama</th><th class="px-4 py-3 font-medium">Jenis ikan</th><th class="px-4 py-3 font-medium">Status</th><th class="px-4 py-3 font-medium">Catatan terkait</th><th class="px-4 py-3 font-medium">Aksi</th></tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($ponds as $p)
                        <tr>
                            <td class="px-4 py-3.5 font-medium">{{ $p->name }}</td>
                            <td class="px-4 py-3.5">{{ $p->fish_type }}</td>
                            <td class="px-4 py-3.5"><span class="rounded-md px-2.5 py-1 text-xs font-medium {{ $p->status === 'aktif' ? 'bg-brand-50 text-brand-700' : 'bg-slate-100 text-slate-600' }}">{{ ucfirst($p->status) }}</span></td>
                            <td class="px-4 py-3.5 text-xs text-slate-500">{{ $p->water_qualities_count }} pengukuran • {{ $p->feed_transactions_count }} pakan • {{ $p->financial_transactions_count }} keuangan</td>
                            <td class="px-4 py-3.5">@include('partials.row-actions', ['edit' => route('ponds.edit', $p), 'destroy' => route('ponds.destroy', $p), 'confirm' => 'Hapus '.$p->name.'?'])</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-6 text-slate-500">Belum ada kolam. Tambahkan kolam pertama Anda di samping.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="rounded-xl border border-slate-200 bg-white p-6">
        <h2 class="font-medium">Tambah kolam</h2>
        <form method="POST" action="{{ route('ponds.store') }}" class="mt-4 space-y-4">
            @csrf
            @include('ponds._fields', ['pond' => null])
            <button class="inline-flex items-center gap-2 rounded-lg bg-brand-600 px-5 py-3 text-sm font-medium text-white hover:bg-brand-700">@include('partials.icon', ['name' => 'plus', 'class' => 'h-4 w-4']) Simpan kolam</button>
        </form>
    </section>
</div>
@endsection
