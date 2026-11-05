@extends('layouts.app')
@section('title', 'Pengguna')
@section('crumb', 'Pengguna')

@section('content')
<h1 class="text-3xl font-normal tracking-tight">Pengguna</h1>
<p class="mt-2 text-sm text-slate-500">Kelola akun pengelola dan mitra / pemilik yang dapat masuk ke aplikasi.</p>

<div class="mt-6 grid gap-4 lg:grid-cols-3">
    <section class="rounded-xl border border-slate-200 bg-white p-6 lg:col-span-2">
        <h2 class="font-medium">Daftar akun</h2>
        <div class="mt-4 overflow-x-auto">
            <table class="w-full min-w-[560px] text-left text-sm">
                <thead class="bg-slate-50 text-xs text-slate-500">
                    <tr><th class="px-4 py-3 font-medium">Nama</th><th class="px-4 py-3 font-medium">Email</th><th class="px-4 py-3 font-medium">Peran</th><th class="px-4 py-3 font-medium">Aksi</th></tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($users as $u)
                        <tr>
                            <td class="px-4 py-3.5 font-medium">{{ $u->name }} @if ($u->id === auth()->id())<span class="ml-1 text-xs text-slate-400">(Anda)</span>@endif</td>
                            <td class="px-4 py-3.5 text-slate-500">{{ $u->email }}</td>
                            <td class="px-4 py-3.5"><span class="rounded-md px-2.5 py-1 text-xs font-medium {{ $u->isPengelola() ? 'bg-brand-50 text-brand-700' : 'bg-slate-100 text-slate-600' }}">{{ $u->isPengelola() ? 'Pengelola' : 'Mitra / Pemilik' }}</span></td>
                            <td class="px-4 py-3.5">@include('partials.row-actions', ['edit' => route('users.edit', $u), 'destroy' => route('users.destroy', $u), 'confirm' => 'Hapus akun '.$u->name.'?'])</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>

    <section class="rounded-xl border border-slate-200 bg-white p-6">
        <h2 class="font-medium">Tambah akun</h2>
        <form method="POST" action="{{ route('users.store') }}" class="mt-4 space-y-4">
            @csrf
            @include('users._fields', ['u' => null])
            <button class="inline-flex items-center gap-2 rounded-lg bg-brand-600 px-5 py-3 text-sm font-medium text-white hover:bg-brand-700">@include('partials.icon', ['name' => 'plus', 'class' => 'h-4 w-4']) Simpan akun</button>
        </form>
    </section>
</div>
@endsection
