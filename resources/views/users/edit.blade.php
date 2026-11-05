@extends('layouts.app')
@section('title', 'Ubah Pengguna')
@section('crumb', 'Pengguna / Ubah')

@section('content')
<h1 class="text-3xl font-normal tracking-tight">Ubah akun {{ $user->name }}</h1>
<section class="mt-6 max-w-lg rounded-xl border border-slate-200 bg-white p-6">
    <form method="POST" action="{{ route('users.update', $user) }}" class="space-y-4">
        @csrf @method('PUT')
        @include('users._fields', ['u' => $user])
        <div class="flex gap-3">
            <button class="rounded-lg bg-brand-600 px-5 py-3 text-sm font-medium text-white hover:bg-brand-700">Simpan perubahan</button>
            <a href="{{ route('users.index') }}" class="rounded-lg border border-slate-200 px-5 py-3 text-sm font-medium hover:bg-slate-50">Batal</a>
        </div>
    </form>
</section>
@endsection
