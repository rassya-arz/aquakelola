@extends('layouts.app')
@section('title', 'Pengecekan pH Air')
@section('crumb', 'Pengecekan pH Air')

@section('content')
<h1 class="text-3xl font-normal tracking-tight">Pengecekan pH air</h1>
<p class="mt-2 text-sm text-slate-500">Catat hasil pengukuran manual dan kenali perubahan kualitas air lebih awal.</p>

{{-- Peringatan aktif + tindakan korektif --}}
@foreach ($alerts as $pond)
    @php $w = $pond->latestWater; @endphp
    <section class="mt-6 overflow-hidden rounded-xl border border-red-200 bg-white">
        <div class="flex items-start gap-3 bg-red-50 p-4 text-sm text-red-700">
            @include('partials.icon', ['name' => 'alert', 'class' => 'mt-0.5 h-5 w-5 shrink-0'])
            <div>
                <p class="font-medium">Peringatan aktif: {{ $pond->name }} pH @num($w->ph_level) {{ $w->ph_level > $maxPh ? '> batas atas' : '< batas bawah' }} {{ \App\Support\Fmt::num($w->ph_level > $maxPh ? $maxPh : $minPh) }}</p>
                <p class="mt-1 text-xs">Batas pH {{ \App\Support\Fmt::num($minPh) }}–{{ \App\Support\Fmt::num($maxPh) }} dipakai sebagai acuan. Pengukuran {{ $w->measured_at->translatedFormat('d M Y, H.i') }} • suhu @num($w->temperature) °C. Tindakan korektif tidak otomatis menutup peringatan; catat pengukuran ulang untuk mengevaluasi kondisi kolam.</p>
            </div>
        </div>

        <form method="POST" action="{{ route('water.action', $w) }}" class="grid gap-4 p-6 md:grid-cols-2">
            @csrf @method('PATCH')
            <div class="md:col-span-2 flex items-center justify-between">
                <h2 class="font-medium">Catatan tindakan korektif {{ $w->corrective_action ? '• '.$w->actionCode() : '' }}</h2>
                @if ($w->corrective_action)
                    <span class="rounded-md px-2.5 py-1 text-xs font-medium {{ $w->action_status === 'selesai' ? 'bg-brand-50 text-brand-700' : 'bg-amber-50 text-amber-700' }}">{{ $w->action_status === 'selesai' ? 'Selesai' : 'Dalam proses' }}</span>
                @endif
            </div>
            <div class="md:col-span-2">
                <label class="text-sm font-medium" for="ca{{ $w->id }}">Tindakan yang dilakukan</label>
                <textarea id="ca{{ $w->id }}" name="corrective_action" rows="3" required maxlength="1000"
                          class="mt-2 w-full rounded-lg border border-slate-200 px-3 py-3 text-sm"
                          placeholder="Contoh: Verifikasi alat ukur manual dan konsultasi penanganan dengan penanggung jawab kolam.">{{ old('corrective_action', $w->corrective_action) }}</textarea>
            </div>
            <div>
                <label class="text-sm font-medium" for="pic{{ $w->id }}">Penanggung jawab</label>
                <select id="pic{{ $w->id }}" name="action_pic_id" class="mt-2 w-full rounded-lg border border-slate-200 px-3 py-3 text-sm">
                    @foreach ($pics as $u)<option value="{{ $u->id }}" @selected(old('action_pic_id', $w->action_pic_id ?? auth()->id()) == $u->id)>{{ $u->name }} • Pengelola</option>@endforeach
                </select>
            </div>
            <div>
                <label class="text-sm font-medium" for="rc{{ $w->id }}">Jadwal cek ulang</label>
                <input id="rc{{ $w->id }}" type="datetime-local" name="recheck_at" value="{{ old('recheck_at', optional($w->recheck_at)->format('Y-m-d\TH:i')) }}"
                       class="mt-2 w-full rounded-lg border border-slate-200 px-3 py-3 text-sm">
            </div>
            <div>
                <label class="text-sm font-medium" for="as{{ $w->id }}">Status penanganan</label>
                <select id="as{{ $w->id }}" name="action_status" class="mt-2 w-full rounded-lg border border-slate-200 px-3 py-3 text-sm">
                    <option value="dalam_proses" @selected(old('action_status', $w->action_status) !== 'selesai')>Dalam proses</option>
                    <option value="selesai" @selected(old('action_status', $w->action_status) === 'selesai')>Selesai</option>
                </select>
            </div>
            <div class="flex items-end">
                <button class="inline-flex items-center gap-2 rounded-lg bg-brand-600 px-5 py-3 text-sm font-medium text-white hover:bg-brand-700">
                    @include('partials.icon', ['name' => 'check', 'class' => 'h-4 w-4']) {{ $w->corrective_action ? 'Perbarui catatan tindakan' : 'Simpan catatan tindakan' }}
                </button>
            </div>
        </form>
    </section>
@endforeach

{{-- Kartu per kolam --}}
<div class="mt-6 grid gap-4 md:grid-cols-3">
    @foreach ($ponds as $pond)
        @php $w = $pond->latestWater; @endphp
        <div class="rounded-xl border bg-white p-5 {{ $w && $w->isOutOfRange() ? 'border-red-200' : 'border-slate-200' }}">
            <div class="flex items-start justify-between">
                <div>
                    <p class="font-medium">{{ $pond->name }}</p>
                    <p class="mt-1 text-xs text-slate-500">{{ $pond->fish_type }}{{ $w ? ' • '.$w->measured_at->translatedFormat('d M Y, H.i') : '' }}</p>
                </div>
                @if ($w)
                    <span class="rounded-md px-2.5 py-1 text-xs font-medium {{ $w->isOutOfRange() ? 'bg-red-50 text-red-600' : 'bg-brand-50 text-brand-700' }}">{{ $w->isOutOfRange() ? 'Di luar batas' : 'Dalam batas' }}</span>
                @endif
            </div>
            @if ($w)
                <div class="mt-5 flex gap-8">
                    <div><p class="text-4xl font-light">@num($w->ph_level)</p><p class="mt-1 text-xs text-slate-500">pH air</p></div>
                    <div><p class="text-2xl">@num($w->temperature) °C</p><p class="mt-1 text-xs text-slate-500">Suhu udara</p></div>
                </div>
            @else
                <p class="mt-5 text-sm text-slate-500">Belum ada pengukuran.</p>
            @endif
            <p class="mt-5 text-xs text-slate-400">Batas: pH {{ \App\Support\Fmt::num($minPh) }}–{{ \App\Support\Fmt::num($maxPh) }}</p>
        </div>
    @endforeach
</div>

{{-- Kartu pH sensor + form: satu state Alpine dipakai bersama --}}
<div class="mt-6 grid gap-4 lg:grid-cols-3"
     x-data="phLive({
        minPh: {{ $minPh }}, maxPh: {{ $maxPh }},
        pond: '{{ old('pond_id', $ponds->first()?->id) }}',
        ph: '{{ old('ph_level') }}', temp: '{{ old('temperature') }}',
        url: '{{ url('/pengecekan-ph/sensor') }}',
        weatherUrl: '{{ url('/pengecekan-ph/cuaca') }}'
     })">

    {{-- Kadar pH saat ini (dari sensor) --}}
    <section class="rounded-xl border border-slate-200 bg-white p-6 lg:col-span-2">
        <div class="flex items-start justify-between">
            <div>
                <h2 class="font-medium">Kadar pH saat ini</h2>
                <p class="mt-1 text-xs text-slate-500">Dibaca langsung dari sensor • kolam sesuai pilihan di form</p>
            </div>
            <span class="rounded-md px-2.5 py-1 text-xs font-medium"
                  :class="!active ? 'bg-slate-100 text-slate-500' : (online ? 'bg-brand-50 text-brand-700' : 'bg-amber-50 text-amber-700')"
                  x-text="!active ? 'Nonaktif' : (online ? 'Sensor terhubung' : 'Menunggu sensor…')"></span>
        </div>

        <div class="flex flex-col items-center py-10 text-center">
            <p class="text-[96px] font-light leading-none tracking-tight"
               :class="active && online && sensorOut ? 'text-red-600' : ''"
               x-text="active && sensorPh !== null ? fmt(sensorPh) : '–'"></p>
            <p class="mt-4 text-2xl">Kadar pH saat ini</p>
            <p class="mt-1 text-sm text-slate-500" x-show="weather" x-cloak>
                Suhu udara <span class="font-medium text-slate-700" x-text="weather && fmt(weather.temperature)"></span> °C
                <template x-if="weather && weather.desc"><span>• <span x-text="weather.desc"></span></span></template>
            </p>
            <p class="mt-0.5 text-[11px] text-slate-400" x-show="weather" x-cloak>
                Sumber: BMKG • <span x-text="weather && weather.place"></span>
            </p>
            <p class="mt-1 text-xs text-slate-400" x-show="active && lastAt" x-cloak>
                Pembacaan terakhir <span x-text="lastAt"></span> WIB
            </p>

            <button type="button" @click="toggle()"
                    class="mt-6 rounded-full px-10 py-2.5 text-sm font-medium text-white"
                    :class="active ? 'bg-slate-500 hover:bg-slate-600' : 'bg-brand-700 hover:bg-brand-900'"
                    x-text="active ? 'Nonaktifkan' : 'Aktifkan'"></button>
        </div>

        <div class="flex gap-3 rounded-xl bg-brand-50/60 p-4 text-xs text-slate-600">
            @include('partials.icon', ['name' => 'info', 'class' => 'h-5 w-5 shrink-0'])
            <p>Nilai sensor otomatis terisi ke form di samping. Tekan “Simpan pengukuran” untuk memasukkannya ke riwayat. Rentang pH {{ \App\Support\Fmt::num($minPh) }}–{{ \App\Support\Fmt::num($maxPh) }} hanya acuan awal, bukan standar universal.</p>
        </div>
    </section>

    {{-- Form input --}}
    <section class="rounded-xl border border-slate-200 bg-white p-6">
        <h2 class="font-medium">Catat pengukuran</h2>
        <p class="mt-1 text-xs text-slate-500" x-text="active && online ? 'Terisi dari sensor • bisa diubah manual' : 'Input manual'"></p>
        <form method="POST" action="{{ route('water.store') }}" class="mt-4 space-y-4">
            @csrf
            <div>
                <label class="text-sm font-medium" for="pond_id">Kolam</label>
                <select id="pond_id" name="pond_id" required x-model="pond" @change="pondChanged()" class="mt-2 w-full rounded-lg border border-slate-200 px-3 py-3 text-sm">
                    @foreach ($ponds as $p)<option value="{{ $p->id }}">{{ $p->name }} • {{ $p->fish_type }}</option>@endforeach
                </select>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="text-sm font-medium" for="ph_level">pH air</label>
                    <input id="ph_level" name="ph_level" type="number" step="0.01" min="0" max="14" required x-model="ph" placeholder="Masukkan pH"
                           class="mt-2 w-full rounded-lg border px-3 py-3 text-sm" :class="out ? 'border-red-400' : 'border-slate-200'">
                </div>
                <div>
                    <label class="text-sm font-medium" for="temperature">Suhu udara <span class="text-xs font-normal text-slate-400">(BMKG)</span></label>
                    <div class="relative mt-2">
                        <input id="temperature" name="temperature" type="number" step="0.1" min="0" max="50" required x-model="temp" @input="tempTouched = true" placeholder="Suhu"
                               class="w-full rounded-lg border border-slate-200 px-3 py-3 pr-9 text-sm">
                        <span class="absolute inset-y-0 right-3 flex items-center text-xs text-slate-400">°C</span>
                    </div>
                </div>
            </div>
            <div>
                <label class="text-sm font-medium" for="measured_at">Waktu pengukuran</label>
                <input id="measured_at" name="measured_at" type="datetime-local" max="{{ now()->format('Y-m-d\TH:i') }}" value="{{ old('measured_at', now()->format('Y-m-d\TH:i')) }}"
                       class="mt-2 w-full rounded-lg border border-slate-200 px-3 py-3 text-sm">
            </div>
            <p x-show="has && !out" x-cloak class="rounded-lg bg-brand-50 p-3 text-xs text-brand-700">pH ini berada dalam batas {{ \App\Support\Fmt::num($minPh) }}–{{ \App\Support\Fmt::num($maxPh) }}.</p>
            <p x-show="out" x-cloak class="rounded-lg bg-red-50 p-3 text-xs text-red-700">pH ini di luar batas. Setelah disimpan, peringatan muncul dan Anda dapat mencatat tindakan korektif.</p>
            <button class="inline-flex items-center gap-2 rounded-lg bg-brand-600 px-5 py-3 text-sm font-medium text-white hover:bg-brand-700">
                @include('partials.icon', ['name' => 'plus', 'class' => 'h-4 w-4']) Simpan pengukuran
            </button>
        </form>
    </section>
</div>

@push('scripts')
<script>
function phLive(cfg) {
    return {
        ...cfg,
        active: false, online: false, timer: null,
        weather: null, tempTouched: false,
        sensorPh: null, lastAt: null,

        init() {
            this.loadWeather();
            setInterval(() => this.loadWeather(), 10 * 60 * 1000);   // BMKG di-cache 30 menit di server
        },
        async loadWeather() {
            try {
                const res = await fetch(this.weatherUrl, { headers: { Accept: 'application/json' } });
                if (!res.ok) return;
                this.weather = await res.json();
                if (!this.tempTouched) this.temp = String(this.weather.temperature);   // isi form kecuali sudah diketik manual
            } catch (e) {}
        },

        get has() { return this.ph !== '' && !isNaN(parseFloat(this.ph)); },
        get out() { const v = parseFloat(this.ph); return this.has && (v < this.minPh || v > this.maxPh); },
        get sensorOut() { return this.sensorPh !== null && (this.sensorPh < this.minPh || this.sensorPh > this.maxPh); },

        fmt(n) { return Number(n).toLocaleString('id-ID', { minimumFractionDigits: 1, maximumFractionDigits: 2 }); },

        toggle() {
            this.active = !this.active;
            if (this.active) { this.poll(); this.timer = setInterval(() => this.poll(), 3000); }
            else { clearInterval(this.timer); this.online = false; }
        },
        pondChanged() { this.sensorPh = null; this.lastAt = null; if (this.active) this.poll(); },

        async poll() {
            try {
                const res = await fetch(`${this.url}/${this.pond}`, { headers: { Accept: 'application/json' } });
                if (!res.ok) throw new Error(res.status);
                const d = await res.json();
                this.online = d.online;
                this.sensorPh = d.ph; this.lastAt = d.at;
                if (d.online) {   // isi form otomatis hanya jika data segar
                    this.ph = String(d.ph);
                }
            } catch (e) { this.online = false; }
        },
    };
}
</script>
@endpush

{{-- Riwayat pengukuran --}}
<section class="mt-6 rounded-xl border border-slate-200 bg-white p-6">
    <h2 class="font-medium">Riwayat pengukuran</h2>
    <div class="mt-4 overflow-x-auto">
        <table class="w-full min-w-[760px] text-left text-sm">
            <thead class="bg-slate-50 text-xs text-slate-500">
                <tr><th class="px-4 py-3 font-medium">Waktu</th><th class="px-4 py-3 font-medium">Kolam</th><th class="px-4 py-3 font-medium">pH air</th><th class="px-4 py-3 font-medium">Suhu</th><th class="px-4 py-3 font-medium">Status</th><th class="px-4 py-3 font-medium">Tindak lanjut</th><th class="px-4 py-3 font-medium">Pencatat</th><th class="px-4 py-3 font-medium">Aksi</th></tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($history as $r)
                    <tr>
                        <td class="px-4 py-3.5">{{ $r->measured_at->translatedFormat('d M • H.i') }}</td>
                        <td class="px-4 py-3.5">{{ $r->pond->name }}</td>
                        <td class="px-4 py-3.5">@num($r->ph_level)</td>
                        <td class="px-4 py-3.5">@num($r->temperature) °C</td>
                        <td class="px-4 py-3.5"><span class="rounded-md px-2.5 py-1 text-xs font-medium {{ $r->isOutOfRange() ? 'bg-red-50 text-red-600' : 'bg-brand-50 text-brand-700' }}">{{ $r->isOutOfRange() ? 'Di luar batas' : 'Dalam batas' }}</span></td>
                        <td class="px-4 py-3.5 text-slate-500">
                            @if (! $r->isOutOfRange()) Tidak diperlukan
                            @elseif ($r->corrective_action) {{ $r->actionCode() }} • {{ $r->action_status === 'selesai' ? 'Selesai' : 'Dalam proses' }}
                            @else Belum dicatat @endif
                        </td>
                        <td class="px-4 py-3.5 text-slate-500">{{ $r->user->name }}</td>
                        <td class="px-4 py-3.5">@include('partials.row-actions', ['edit' => route('water.edit', $r), 'destroy' => route('water.destroy', $r), 'confirm' => 'Hapus pengukuran ini?'])</td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="px-4 py-6 text-slate-500">Belum ada pengukuran. Catat pH pertama Anda melalui formulir di atas.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $history->links() }}</div>
</section>
@endsection
