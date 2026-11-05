<div>
    <label class="text-sm font-medium" for="name">Nama</label>
    <input id="name" name="name" type="text" required maxlength="100" value="{{ old('name', $u?->name) }}" class="mt-2 w-full rounded-lg border border-slate-200 px-3 py-3 text-sm">
</div>
<div>
    <label class="text-sm font-medium" for="email">Email</label>
    <input id="email" name="email" type="email" required maxlength="150" value="{{ old('email', $u?->email) }}" class="mt-2 w-full rounded-lg border border-slate-200 px-3 py-3 text-sm">
</div>
<div>
    <label class="text-sm font-medium" for="role">Peran</label>
    <select id="role" name="role" class="mt-2 w-full rounded-lg border border-slate-200 px-3 py-3 text-sm">
        <option value="pengelola" @selected(old('role', $u?->role) === 'pengelola')>Pengelola</option>
        <option value="mitra_pemilik" @selected(old('role', $u?->role) === 'mitra_pemilik')>Mitra / Pemilik</option>
    </select>
</div>
<div class="grid grid-cols-2 gap-3">
    <div>
        <label class="text-sm font-medium" for="password">Kata sandi{{ $u ? ' baru' : '' }}</label>
        <input id="password" name="password" type="password" minlength="8" autocomplete="new-password" {{ $u ? '' : 'required' }} class="mt-2 w-full rounded-lg border border-slate-200 px-3 py-3 text-sm">
    </div>
    <div>
        <label class="text-sm font-medium" for="password_confirmation">Ulangi kata sandi</label>
        <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" {{ $u ? '' : 'required' }} class="mt-2 w-full rounded-lg border border-slate-200 px-3 py-3 text-sm">
    </div>
</div>
@if ($u)<p class="text-xs text-slate-500">Kosongkan kata sandi bila tidak ingin mengubahnya.</p>@endif
