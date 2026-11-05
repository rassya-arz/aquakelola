<div>
    <label class="text-sm font-medium" for="name">Nama kolam</label>
    <input id="name" name="name" type="text" required maxlength="100" value="{{ old('name', $pond?->name) }}" placeholder="Contoh: Kolam 04" class="mt-2 w-full rounded-lg border border-slate-200 px-3 py-3 text-sm">
</div>
<div>
    <label class="text-sm font-medium" for="fish_type">Jenis ikan</label>
    <select id="fish_type" name="fish_type" class="mt-2 w-full rounded-lg border border-slate-200 px-3 py-3 text-sm">
        @foreach (['Nila', 'Lele'] as $f)<option value="{{ $f }}" @selected(old('fish_type', $pond?->fish_type) === $f)>{{ $f }}</option>@endforeach
    </select>
</div>
<div>
    <label class="text-sm font-medium" for="status">Status</label>
    <select id="status" name="status" class="mt-2 w-full rounded-lg border border-slate-200 px-3 py-3 text-sm">
        <option value="aktif" @selected(old('status', $pond?->status ?? 'aktif') === 'aktif')>Aktif</option>
        <option value="panen" @selected(old('status', $pond?->status) === 'panen')>Panen</option>
    </select>
</div>
