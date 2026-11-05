@extends('layouts.app')
@section('title', 'Ubah Kolam')
@section('crumb', 'Data Kolam / Ubah')

@section('content')
<h1 class="text-3xl font-normal tracking-tight">Ubah {{ $pond->name }}</h1>
<section class="mt-6 max-w-lg rounded-xl border border-slate-200 bg-white p-6">
    <form method="POST" action="{{ route('ponds.update', $pond) }}" class="space-y-4">
        @csrf @method('PUT')
        @include('ponds._fields', ['pond' => $pond])
        <div class="flex gap-3">
            <button class="rounded-lg bg-brand-600 px-5 py-3 text-sm font-medium text-white hover:bg-brand-700">Simpan perubahan</button>
            <a href="{{ route('ponds.index') }}" class="rounded-lg border border-slate-200 px-5 py-3 text-sm font-medium hover:bg-slate-50">Batal</a>
        </div>
    </form>
</section>
@endsection
