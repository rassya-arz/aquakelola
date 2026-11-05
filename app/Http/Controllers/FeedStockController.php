<?php

namespace App\Http\Controllers;

use App\Models\FeedStock;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class FeedStockController extends Controller
{
    public function index()
    {
        return view('feedstocks.index', [
            'stocks' => FeedStock::withCount('transactions')->orderBy('feed_type')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->rules() + [
            'total_stock' => ['required', 'numeric', 'min:0', 'max:1000000'],
        ], $this->messages());

        $stock = FeedStock::create($data);

        return redirect()->route('feedstocks.index')->with('success', "{$stock->feed_type} ditambahkan dengan stok awal ".rtrim(rtrim(number_format($stock->total_stock, 2, ',', '.'), '0'), ',').' kg.');
    }

    public function edit(FeedStock $feedStock)
    {
        return view('feedstocks.edit', ['stock' => $feedStock]);
    }

    /** Jumlah stok tidak diubah di sini; stok hanya berubah lewat transaksi pakan. */
    public function update(Request $request, FeedStock $feedStock)
    {
        $feedStock->update($request->validate($this->rules($feedStock), $this->messages()));

        return redirect()->route('feedstocks.index')->with('success', "{$feedStock->feed_type} diperbarui.");
    }

    public function destroy(FeedStock $feedStock)
    {
        if ($feedStock->transactions()->exists() || $feedStock->financialTransactions()->exists()) {
            return redirect()->route('feedstocks.index')->with('alert', "{$feedStock->feed_type} sudah memiliki transaksi sehingga tidak dapat dihapus.");
        }

        $feedStock->delete();

        return redirect()->route('feedstocks.index')->with('success', 'Jenis pakan dihapus.');
    }

    private function rules(?FeedStock $stock = null): array
    {
        return [
            'feed_type' => ['required', 'string', 'max:100', Rule::unique('feed_stocks', 'feed_type')->ignore($stock?->id)],
            'minimum_stock' => ['required', 'numeric', 'min:0', 'max:1000000'],
            'price_per_kg' => ['required', 'numeric', 'min:0', 'max:10000000'],
        ];
    }

    private function messages(): array
    {
        return [
            'feed_type.required' => 'Nama jenis pakan wajib diisi.',
            'feed_type.unique' => 'Jenis pakan ini sudah ada.',
            'total_stock.required' => 'Stok awal wajib diisi (isi 0 bila kosong).',
        ];
    }
}
