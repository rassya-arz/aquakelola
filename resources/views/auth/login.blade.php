<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Masuk — AquaKelola</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { theme: { extend: {
            fontFamily: { sans: ['Inter', 'ui-sans-serif', 'system-ui', 'sans-serif'] },
            colors: { brand: { 50: '#eef7f2', 100: '#d6ece1', 500: '#2a8a62', 600: '#23805a', 700: '#1d6b4c', 900: '#143d2e' } }
        } } }
    </script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.1/dist/cdn.min.js"></script>
    <style>[x-cloak]{display:none!important}</style>
</head>
<body class="font-sans text-slate-800 antialiased">
<div class="grid min-h-screen lg:grid-cols-2">
    {{-- Kiri: form --}}
    <div class="flex flex-col px-8 py-10 sm:px-16">
        <div class="flex items-center gap-3">
            <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-brand-600 text-white">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12c3-5 8-6 12-4 2 1 4 3 6 4-2 1-4 3-6 4-4 2-9 1-12-4z"/><circle cx="16" cy="11" r=".6" fill="currentColor"/></svg>
            </span>
            <span class="text-xl font-semibold tracking-tight">AquaKelola</span>
        </div>

        <div class="flex w-full max-w-md flex-1 flex-col justify-center py-12"
             x-data="{ role: '{{ old('role', 'pengelola') }}', show: false, loading: false }">
            <h1 class="text-4xl font-normal tracking-tight">Selamat datang kembali.</h1>
            <p class="mt-4 text-slate-500">Masuk untuk memantau pakan, kualitas air, dan hasil budidaya dalam satu tempat.</p>

            <form method="POST" action="{{ route('login.attempt') }}" class="mt-8" @submit="loading = true" novalidate>
                @csrf
                <input type="hidden" name="role" :value="role">

                <div class="grid grid-cols-2 gap-1 rounded-xl bg-slate-100 p-1" role="tablist">
                    <button type="button" role="tab" @click="role='pengelola'" :aria-selected="role==='pengelola'"
                            :class="role==='pengelola' ? 'bg-white text-brand-700 shadow-sm ring-1 ring-slate-200 font-medium' : 'text-slate-500'"
                            class="rounded-lg py-2.5 text-sm transition">Pengelola</button>
                    <button type="button" role="tab" @click="role='mitra_pemilik'" :aria-selected="role==='mitra_pemilik'"
                            :class="role==='mitra_pemilik' ? 'bg-white text-brand-700 shadow-sm ring-1 ring-slate-200 font-medium' : 'text-slate-500'"
                            class="rounded-lg py-2.5 text-sm transition">Mitra / Pemilik</button>
                </div>
                <p class="mt-4 text-sm font-medium">Masuk sebagai</p>
                <p class="mt-1 text-xs text-slate-500" x-text="role==='pengelola' ? 'Pengelola mencatat data; pemilik memantau laporan.' : 'Mitra / pemilik melihat laporan keuangan dan kondisi budidaya.'"></p>

                <label for="email" class="mt-6 block text-sm font-medium">Alamat email</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="username"
                       class="mt-2 w-full rounded-lg border border-slate-200 px-3.5 py-3 text-sm outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-100 @error('email') border-red-400 @enderror"
                       placeholder="nama@email.com">
                @error('email')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror

                <label for="password" class="mt-5 block text-sm font-medium">Kata sandi</label>
                <div class="relative mt-2">
                    <input id="password" name="password" :type="show ? 'text' : 'password'" required autocomplete="current-password"
                           class="w-full rounded-lg border border-slate-200 px-3.5 py-3 pr-11 text-sm outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-100 @error('password') border-red-400 @enderror">
                    <button type="button" @click="show = !show" class="absolute inset-y-0 right-0 flex w-11 items-center justify-center text-slate-400 hover:text-slate-600"
                            :aria-label="show ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'">
                        <svg x-show="!show" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/></svg>
                        <svg x-show="show" x-cloak class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3l18 18M10.6 6.1A9.8 9.8 0 0112 5c6.5 0 10 7 10 7a17 17 0 01-3.2 4M6.5 6.5C3.6 8.4 2 12 2 12s3.5 7 10 7c1.7 0 3.2-.4 4.5-1M9.9 9.9a3 3 0 004.2 4.2"/></svg>
                    </button>
                </div>
                @error('password')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror

                <div class="mt-4 flex items-center justify-between text-sm">
                    <label class="flex items-center gap-2 text-slate-500">
                        <input type="checkbox" name="remember" value="1" class="h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500" @checked(old('remember'))>
                        Ingat saya
                    </label>
                    <a href="#" class="font-medium text-brand-600 hover:text-brand-700">Lupa kata sandi?</a>
                </div>

                <button type="submit" :disabled="loading"
                        class="mt-6 flex w-full items-center justify-center gap-2 rounded-lg bg-brand-600 py-3.5 text-sm font-medium text-white transition hover:bg-brand-700 disabled:opacity-70">
                    <span x-show="!loading" class="flex items-center gap-2">Masuk @include('partials.icon', ['name' => 'arrow', 'class' => 'h-4 w-4'])</span>
                    <span x-show="loading" x-cloak>Memproses…</span>
                </button>
            </form>
        </div>

        <div class="flex justify-between text-xs text-slate-400">
            <span>© {{ date('Y') }} AquaKelola</span>
            <span>Dirancang untuk budidaya yang lebih terukur</span>
        </div>
    </div>

    {{-- Kanan: panel visual --}}
    <div class="relative hidden overflow-hidden bg-brand-900 text-white lg:flex lg:flex-col lg:justify-between lg:p-14"
         style="background-image: linear-gradient(180deg, rgba(20,61,46,.55) 0%, rgba(20,61,46,.92) 70%), repeating-linear-gradient(90deg, rgba(255,255,255,.05) 0 2px, transparent 2px 64px), repeating-linear-gradient(0deg, rgba(255,255,255,.05) 0 2px, transparent 2px 64px), linear-gradient(135deg, #2f6b4f, #143d2e);">
        <span class="w-fit rounded-full bg-white px-4 py-2 text-xs font-medium text-brand-700">Dari kolam, untuk masa depan</span>
        <div>
            <h2 class="text-5xl font-semibold leading-tight tracking-tight">Budidaya lebih tenang.<br>Keputusan lebih jelas.</h2>
            <p class="mt-6 max-w-xl text-white/80">Dari stok pakan hingga hasil panen, setiap catatan membantu Anda memahami kondisi usaha.</p>
            <div class="mt-10 grid grid-cols-3 gap-6 border-t border-white/20 pt-8 text-sm">
                <div>@include('partials.icon', ['name' => 'box', 'class' => 'mb-3 h-6 w-6']) Stok terkontrol</div>
                <div>@include('partials.icon', ['name' => 'drop', 'class' => 'mb-3 h-6 w-6']) Air terpantau</div>
                <div>@include('partials.icon', ['name' => 'chart', 'class' => 'mb-3 h-6 w-6']) Hasil tercatat</div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
