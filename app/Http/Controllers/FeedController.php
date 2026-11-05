<?php

namespace App\Http\Controllers;

use App\Models\FeedStock;
use App\Models\FeedTransaction;
use App\Models\FinancialTransaction;
use App\Models\Pond;
use App\Support\Fmt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FeedController extends Controller
{
    public function index(Request $request)
    {
        $stocks = FeedStock::orderBy('feed_type')->get();

        $monthStart = now()->startOfMonth();
        $monthEnd = now()->endOfMonth();

        return view('feed.index', [
            'stocks' => $stocks,
            'ponds' => Pond::aktif()->orderBy('name')->get(),
            'history' => FeedTransaction::with(['pond', 'feed', 'user'])->latest()->paginate(10),
            'totalStock' => $stocks->sum('total_stock'),
            'usedThisMonth' => FeedTransaction::where('type', 'keluar')->whereBetween('created_at', [$monthStart, $monthEnd])->sum('quantity'),
            'purchaseCost' => FinancialTransaction::where('type', 'pembelian_pakan')->whereBetween('transaction_date', [$monthStart, $monthEnd])->sum('total_amount'),
        ]);
    }

    /** CREATE: pakan masuk menambah stok + mencatat pembelian; pakan keluar mengurangi stok. */
    public function storeTransaction(Request $request)
    {
        $data = $this->validateTransaction($request);

        $tx = DB::transaction(function () use ($data, $request) {
            $stock = FeedStock::lockForUpdate()->findOrFail($data['feed_id']);
            $this->applyEffect($stock, $data);

            $tx = FeedTransaction::create([
                'pond_id' => $data['type'] === 'keluar' ? $data['pond_id'] : null,
                'feed_id' => $stock->id,
                'type' => $data['type'],
                'quantity' => (float) $data['quantity'],
                'notes' => $data['notes'] ?? null,
                'user_id' => $request->user()->id,
            ]);

            if ($data['type'] === 'masuk') {
                $price = $this->resolvePrice($data, $stock);
                $stock->price_per_kg = $price;
                $this->createPurchase($tx, $stock, $price, now()->toDateString());
            }

            $stock->save();

            return $tx->load('feed');
        });

        $label = $tx->type === 'keluar' ? 'Pemakaian' : 'Pakan masuk';

        return redirect()->route('feed.index')->with('success',
            "Transaksi {$tx->code()} tersimpan. {$label} {$tx->feed->feed_type} ".Fmt::num($tx->quantity).' kg dicatat; stok sekarang '.Fmt::num($tx->feed->total_stock).' kg.');
    }

    public function editTransaction(FeedTransaction $feedTransaction)
    {
        $purchase = FinancialTransaction::where('feed_transaction_id', $feedTransaction->id)->first();

        return view('feed.edit', [
            't' => $feedTransaction,
            'stocks' => FeedStock::orderBy('feed_type')->get(),
            'ponds' => Pond::orderBy('name')->get(),
            'price' => $purchase?->price_per_kg,
        ]);
    }

    /** UPDATE: batalkan efek lama pada stok, lalu terapkan efek baru (semua dalam satu transaksi DB). */
    public function updateTransaction(Request $request, FeedTransaction $feedTransaction)
    {
        $data = $this->validateTransaction($request);

        DB::transaction(function () use ($data, $feedTransaction) {
            $oldStock = FeedStock::lockForUpdate()->findOrFail($feedTransaction->feed_id);
            $this->revertEffect($oldStock, $feedTransaction);

            $newStock = (int) $data['feed_id'] === $oldStock->id
                ? $oldStock
                : FeedStock::lockForUpdate()->findOrFail($data['feed_id']);
            $this->applyEffect($newStock, $data);

            $oldPurchase = FinancialTransaction::where('feed_transaction_id', $feedTransaction->id)->first();
            $purchaseDate = $oldPurchase?->transaction_date?->toDateString() ?? now()->toDateString();
            $oldPrice = $oldPurchase?->price_per_kg;
            FinancialTransaction::where('feed_transaction_id', $feedTransaction->id)->delete();

            $feedTransaction->update([
                'pond_id' => $data['type'] === 'keluar' ? $data['pond_id'] : null,
                'feed_id' => $newStock->id,
                'type' => $data['type'],
                'quantity' => (float) $data['quantity'],
                'notes' => $data['notes'] ?? null,
            ]);

            if ($data['type'] === 'masuk') {
                $price = isset($data['price_per_kg']) ? (float) $data['price_per_kg'] : ($oldPrice ?? $newStock->price_per_kg);
                $newStock->price_per_kg = $price;
                $this->createPurchase($feedTransaction, $newStock, $price, $purchaseDate);
            }

            if ($oldStock->id !== $newStock->id) {
                $oldStock->save();
            }
            $newStock->save();
        });

        return redirect()->route('feed.index')->with('success', "Transaksi {$feedTransaction->code()} diperbarui dan stok disesuaikan.");
    }

    /** DELETE: batalkan efek transaksi pada stok; pembelian terkait ikut terhapus (cascade). */
    public function destroyTransaction(FeedTransaction $feedTransaction)
    {
        DB::transaction(function () use ($feedTransaction) {
            $stock = FeedStock::lockForUpdate()->findOrFail($feedTransaction->feed_id);
            $this->revertEffect($stock, $feedTransaction);
            $stock->save();
            FinancialTransaction::where('feed_transaction_id', $feedTransaction->id)->delete();
            $feedTransaction->delete();
        });

        return redirect()->route('feed.index')->with('success', "Transaksi {$feedTransaction->code()} dihapus dan stok dikembalikan.");
    }

    // ---------------------------------------------------------------- helpers

    private function validateTransaction(Request $request): array
    {
        return $request->validate([
            'type' => ['required', 'in:masuk,keluar'],
            'feed_id' => ['required', 'exists:feed_stocks,id'],
            'pond_id' => ['required_if:type,keluar', 'nullable', 'exists:ponds,id'],
            'quantity' => ['required', 'numeric', 'gt:0', 'max:100000'],
            'price_per_kg' => ['nullable', 'numeric', 'min:0', 'max:10000000'],
            'notes' => ['nullable', 'string', 'max:255'],
        ], [
            'pond_id.required_if' => 'Pilih kolam tujuan untuk pemakaian pakan.',
            'quantity.required' => 'Jumlah pakan wajib diisi.',
            'quantity.gt' => 'Jumlah pakan harus lebih dari 0.',
        ]);
    }

    /** Terapkan transaksi baru ke stok (di memori; pemanggil yang save). */
    private function applyEffect(FeedStock $stock, array $data): void
    {
        $qty = (float) $data['quantity'];

        if ($data['type'] === 'keluar') {
            if ($qty > $stock->total_stock) {
                throw ValidationException::withMessages([
                    'quantity' => 'Jumlah pemakaian ('.Fmt::num($qty).' kg) melebihi stok '.$stock->feed_type.' yang tersedia ('.Fmt::num($stock->total_stock).' kg).',
                ]);
            }
            $stock->total_stock -= $qty;
        } else {
            $stock->total_stock += $qty;
        }
    }

    /** Batalkan efek transaksi lama dari stok; tolak bila membuat stok negatif. */
    private function revertEffect(FeedStock $stock, FeedTransaction $tx): void
    {
        $stock->total_stock += $tx->type === 'keluar' ? $tx->quantity : -$tx->quantity;

        if ($stock->total_stock < -0.0001) {
            throw ValidationException::withMessages([
                'quantity' => 'Perubahan ini membuat stok '.$stock->feed_type.' menjadi negatif karena pakan dari transaksi ini sudah terpakai.',
            ]);
        }
    }

    private function resolvePrice(array $data, FeedStock $stock): float
    {
        return isset($data['price_per_kg']) ? (float) $data['price_per_kg'] : $stock->price_per_kg;
    }

    private function createPurchase(FeedTransaction $tx, FeedStock $stock, float $price, string $date): void
    {
        FinancialTransaction::create([
            'pond_id' => null,
            'feed_id' => $stock->id,
            'feed_transaction_id' => $tx->id,
            'type' => 'pembelian_pakan',
            'weight_kg' => $tx->quantity,
            'price_per_kg' => $price,
            'total_amount' => round($tx->quantity * $price, 2),
            'transaction_date' => $date,
            'user_id' => $tx->user_id,
        ]);
    }
}
