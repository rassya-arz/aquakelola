<div>
    <label class="text-sm font-medium" for="name">Nama</label>
    <input id="name" name="name" type="text" required maxlength="100" value="{{ old('name', $u?->name) }}" class="mt-2 w-full rounded-lg border border-slate-200 px-3 py-3 text-sm">
</div>
<div>
    <label class="text-sm font-medium" for="email">Email</label>
    <input id="email" name="email" type="email" required maxlength="150" value="{{ old('email', $u?->email) }}" class="mt-2 w-full rounded-lg border border-slate-200 px-3 py-3 text-sm">
</div>
@php $isSelf = $u && $u->id === auth()->id(); @endphp
<div>
    <label class="text-sm font-medium" for="role">Peran</label>
    @if ($isSelf)
        {{-- Peran akun yang sedang dipakai tidak dapat diubah (mencegah terkunci dari menu Pengguna). --}}
        <input type="hidden" name="role" value="{{ $u->role }}">
        <p class="mt-2 rounded-lg bg-slate-50 px-3 py-3 text-sm text-slate-600">{{ $u->roleLabel() }} <span class="text-xs text-slate-400">• peran akun Anda sendiri tidak dapat diubah</span></p>
    @else
        <select id="role" name="role" class="mt-2 w-full rounded-lg border border-slate-200 px-3 py-3 text-sm">
            <option value="pengelola" @selected(old('role', $u?->role) === 'pengelola')>Pengelola</option>
            <option value="mitra_pemilik" @selected(old('role', $u?->role) === 'mitra_pemilik')>Mitra / Pemilik</option>
        </select>
    @endif
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
