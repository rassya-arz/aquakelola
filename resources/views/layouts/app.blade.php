<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'AquaKelola') — AquaKelola</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    {{-- Tailwind via CDN agar langsung jalan. Untuk produksi, pindahkan ke Vite + @tailwindcss/vite. --}}
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { theme: { extend: {
            fontFamily: { sans: ['Inter', 'ui-sans-serif', 'system-ui', 'sans-serif'] },
            colors: { brand: { 50: '#eef7f2', 100: '#d6ece1', 500: '#2a8a62', 600: '#23805a', 700: '#1d6b4c', 900: '#143d2e' } }
        } } }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.1/dist/cdn.min.js"></script>
    <style>[x-cloak]{display:none!important}</style>
</head>
@php
    $user = auth()->user();
    $nav = [
        ['dashboard', 'Home', 'home', 'dashboard', ['pengelola', 'mitra_pemilik']],
        ['feed.index', 'Stok Pakan', 'box', 'feed.*', ['pengelola']],
        ['water.index', 'Pengecekan pH Air', 'drop', 'water.*', ['pengelola']],
        ['finance.index', 'Laba Rugi', 'chart', 'finance.*', ['pengelola', 'mitra_pemilik']],
        ['ponds.index', 'Data Kolam', 'pond', 'ponds.*', ['pengelola']],
        ['feedstocks.index', 'Jenis Pakan', 'tag', 'feedstocks.*', ['pengelola']],
        ['users.index', 'Pengguna', 'users', 'users.*', ['pengelola']],
    ];
@endphp
<body class="bg-slate-50 font-sans text-slate-800 antialiased" x-data="{ open: false }">
<div class="flex min-h-screen">
    {{-- Sidebar --}}
    <aside class="fixed inset-y-0 left-0 z-40 flex w-64 -translate-x-full flex-col border-r border-slate-200 bg-white p-4 transition-transform lg:static lg:translate-x-0"
           :class="{ 'translate-x-0': open }">
        <div class="flex items-center gap-3 px-2 py-3">
            <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-brand-600 text-white">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12c3-5 8-6 12-4 2 1 4 3 6 4-2 1-4 3-6 4-4 2-9 1-12-4z"/><circle cx="16" cy="11" r=".6" fill="currentColor"/></svg>
            </span>
            <span class="text-lg font-semibold tracking-tight">AquaKelola</span>
        </div>

        <p class="mt-6 px-2 text-[11px] font-medium text-slate-400">Kelola budidaya</p>
        <nav class="mt-2 space-y-1">
            @foreach ($nav as [$route, $label, $icon, $pattern, $roles])
                @if (in_array($user->role, $roles, true))
                    <a href="{{ route($route) }}"
                       class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm {{ request()->routeIs($pattern) ? 'bg-brand-50 font-medium text-brand-700' : 'text-slate-600 hover:bg-slate-50' }}">
                        @include('partials.icon', ['name' => $icon])
                        {{ $label }}
                    </a>
                @endif
            @endforeach
        </nav>

        <div class="mt-auto space-y-3">
            <div class="rounded-xl bg-slate-50 p-4">
                <p class="text-sm font-medium">Pencatatan manual</p>
                <p class="mt-1 text-xs leading-relaxed text-slate-500">Semua nilai berasal dari input pengelola, bukan data sensor langsung.</p>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-sm text-slate-600 hover:bg-slate-50">
                    @include('partials.icon', ['name' => 'logout']) Keluar akun
                </button>
            </form>
        </div>
    </aside>
    <div x-show="open" x-cloak @click="open=false" class="fixed inset-0 z-30 bg-black/30 lg:hidden"></div>

    {{-- Konten --}}
    <div class="flex min-w-0 flex-1 flex-col">
        <header class="flex h-16 items-center justify-between border-b border-slate-200 bg-white px-4 sm:px-8">
            <div class="flex items-center gap-3 text-sm">
                <button class="lg:hidden" @click="open=true" aria-label="Buka menu">@include('partials.icon', ['name' => 'menu'])</button>
                <span class="font-medium">Budidaya Mina Sejahtera</span>
                <span class="text-slate-300">/</span>
                <span class="text-slate-500">@yield('crumb')</span>
            </div>
            <div class="flex items-center gap-3">
                <span class="flex h-9 w-9 items-center justify-center rounded-full bg-brand-50 text-xs font-medium text-brand-700">{{ $user->initials() }}</span>
                <div class="hidden text-sm leading-tight sm:block">
                    <p class="font-medium">{{ $user->name }}</p>
                    <p class="text-xs text-slate-500">{{ $user->roleLabel() }}</p>
                </div>
            </div>
        </header>

        <main class="flex-1 px-4 py-8 sm:px-8">
            @if (session('success'))
                <div class="mb-6 flex items-start gap-3 rounded-xl bg-brand-50 p-4 text-sm text-brand-700">
                    @include('partials.icon', ['name' => 'check', 'class' => 'mt-0.5 h-5 w-5 shrink-0'])
                    <p>{{ session('success') }}</p>
                </div>
            @endif
            @if (session('alert'))
                <div class="mb-6 flex items-start gap-3 rounded-xl bg-red-50 p-4 text-sm text-red-700">
                    @include('partials.icon', ['name' => 'alert', 'class' => 'mt-0.5 h-5 w-5 shrink-0'])
                    <p>{{ session('alert') }}</p>
                </div>
            @endif
            @if ($errors->any() && ! request()->routeIs('login'))
                <div class="mb-6 rounded-xl bg-red-50 p-4 text-sm text-red-700">
                    <p class="font-medium">Data belum tersimpan. Periksa isian berikut:</p>
                    <ul class="mt-1 list-disc pl-5">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                </div>
            @endif

            @yield('content')
        </main>

        <footer class="px-4 pb-6 text-xs text-slate-400 sm:px-8">AquaKelola • Pencatatan manual</footer>
    </div>
</div>
@stack('scripts')
</body>
</html>
