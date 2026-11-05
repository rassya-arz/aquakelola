<div class="flex items-center gap-1">
    <a href="{{ $edit }}" title="Ubah" aria-label="Ubah" class="rounded-md p-2 text-slate-500 hover:bg-slate-100 hover:text-brand-700">@include('partials.icon', ['name' => 'edit', 'class' => 'h-4 w-4'])</a>
    <form method="POST" action="{{ $destroy }}" onsubmit="return confirm(@js($confirm))">
        @csrf @method('DELETE')
        <button title="Hapus" aria-label="Hapus" class="rounded-md p-2 text-slate-500 hover:bg-red-50 hover:text-red-600">@include('partials.icon', ['name' => 'trash', 'class' => 'h-4 w-4'])</button>
    </form>
</div>
