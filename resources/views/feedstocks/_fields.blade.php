<div>
    <label class="text-sm font-medium" for="feed_type">Nama jenis pakan</label>
    <input id="feed_type" name="feed_type" type="text" required maxlength="100" value="{{ old('feed_type', $stock?->feed_type) }}" placeholder="Contoh: Pelet Finisher" class="mt-2 w-full rounded-lg border border-slate-200 px-3 py-3 text-sm">
</div>
<div class="grid grid-cols-2 gap-3">
    <div>
        <label class="text-sm font-medium" for="minimum_stock">Stok minimum (kg)</label>
        <input id="minimum_stock" name="minimum_stock" type="number" min="0" step="any" required value="{{ old('minimum_stock', $stock?->minimum_stock) }}" class="mt-2 w-full rounded-lg border border-slate-200 px-3 py-3 text-sm">
    </div>
    <div>
        <label class="text-sm font-medium" for="price_per_kg">Harga / kg (Rp)</label>
        <input id="price_per_kg" name="price_per_kg" type="number" min="0" step="any" required value="{{ old('price_per_kg', $stock?->price_per_kg) }}" class="mt-2 w-full rounded-lg border border-slate-200 px-3 py-3 text-sm">
    </div>
</div>
