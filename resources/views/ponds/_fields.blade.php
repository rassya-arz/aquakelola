@php $fishTypes = \App\Models\Pond::fishTypeSuggestions(); @endphp
<div>
    <label class="text-sm font-medium" for="name">Nama kolam</label>
    <input id="name" name="name" type="text" required maxlength="100" value="{{ old('name', $pond?->name) }}" placeholder="Contoh: Kolam 04" class="mt-2 w-full rounded-lg border border-slate-200 px-3 py-3 text-sm">
</div>
<div>
    <label class="text-sm font-medium" for="fish_type">Jenis ikan</label>
    <input id="fish_type" name="fish_type" type="text" list="fish-type-options" required maxlength="50" autocomplete="off"
           value="{{ old('fish_type', $pond?->fish_type) }}" placeholder="Pilih saran atau ketik jenis ikan baru"
           class="mt-2 w-full rounded-lg border border-slate-200 px-3 py-3 text-sm">
    <datalist id="fish-type-options">
        @foreach ($fishTypes as $f)<option value="{{ $f }}"></option>@endforeach
    </datalist>
    <p class="mt-2 text-xs text-slate-500">Pilih dari saran atau bebas mengetik nama jenis ikan lain.</p>
</div>
<div>
    <label class="text-sm font-medium" for="status">Status</label>
    <select id="status" name="status" class="mt-2 w-full rounded-lg border border-slate-200 px-3 py-3 text-sm">
        @foreach (\App\Models\Pond::STATUS_LABELS as $value => $label)
            <option value="{{ $value }}" @selected(old('status', $pond?->status ?? 'aktif') === $value)>{{ $label }}</option>
        @endforeach
    </select>
    <p class="mt-2 text-xs text-slate-500">Panen mengosongkan catatan terkait dan menonaktifkan monitoring pH &amp; pemberian pakan; ubah ke Aktif untuk siklus baru. Perbaikan menonaktifkannya sementara tanpa mengakhiri siklus.</p>
</div>
