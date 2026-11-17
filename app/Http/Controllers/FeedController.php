<?php

namespace App\Http\Controllers;

use App\Models\FeedStock;
use App\Models\FeedTransaction;
use App\Models\FinancialTransaction;
use App\Models\Pond;
use App\Support\Fmt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class FeedController extends Controller
{
    public function index()
    {
        $stocks = FeedStock::orderBy('feed_type')->get();

        $monthStart = now()->startOfMonth();
        $monthEnd = now()->endOfMonth();

        return view('feed.index', [
            'stocks' => $stocks,
            'lowStocks' => $stocks->filter->isLow()->values(),
            'ponds' => Pond::aktif()->orderBy('name')->get(),
            'history' => FeedTransaction::with(['pond', 'feed', 'user'])->latest()->paginate(10),
            'totalStock' => $stocks->sum('total_stock'),
            'usedThisMonth' => FeedTransaction::where('type', FeedTransaction::TYPE_KELUAR)
                ->whereBetween('created_at', [$monthStart, $monthEnd])->sum('quantity'),
            'purchaseCost' => FinancialTransaction::where('type', FinancialTransaction::TYPE_PEMBELIAN_PAKAN)
                ->whereBetween('transaction_date', [$monthStart, $monthEnd])->sum('total_amount'),
        ]);
    }

    /** CREATE: pakan masuk menambah stok + mencatat pembelian; pakan keluar mengurangi stok. */
    public function storeTransaction(Request $request)
    {
        $data = $this->validateTransaction($request);
        $isMasuk = $data['type'] === FeedTransaction::TYPE_MASUK;

        $tx = DB::transaction(function () use ($data, $request, $isMasuk) {
            $stock = FeedStock::lockForUpdate()->findOrFail($data['feed_id']);
            $price = $isMasuk ? $this->resolvePrice($data, $stock) : null;

            $this->applyEffect($stock, $data['type'], (float) $data['quantity']);

            $tx = FeedTransaction::create([
                'pond_id' => $isMasuk ? null : $data['pond_id'],
                'feed_id' => $stock->id,
                'type' => $data['type'],
                'quantity' => (float) $data['quantity'],
                'notes' => $data['notes'] ?? null,
                'user_id' => $request->user()->id,
            ]);

            if ($isMasuk) {
                $stock->price_per_kg = $price; // harga acuan mengikuti pembelian terakhir
                $this->createPurchase($tx, $stock, $price, now()->toDateString());
            }

            $stock->save();

            return $tx->load('feed');
        });

        $label = $tx->isKeluar() ? 'Pemakaian' : 'Pakan masuk';

        return redirect()->route('feed.index')->with('success',
            "Transaksi {$tx->code()} tersimpan. {$label} {$tx->feed->feed_type} ".Fmt::num($tx->quantity).' kg dicatat; stok sekarang '.Fmt::num($tx->feed->total_stock).' kg.');
    }

    public function editTransaction(FeedTransaction $feedTransaction)
    {
        $purchase = FinancialTransaction::where('feed_transaction_id', $feedTransaction->id)->first();

        return view('feed.edit', [
            't' => $feedTransaction,
            'stocks' => FeedStock::orderBy('feed_type')->get(),
            'ponds' => Pond::aktif()->orWhere('id', $feedTransaction->pond_id)->orderBy('name')->get(),
            'price' => $purchase?->price_per_kg,
        ]);
    }

    /** UPDATE: batalkan efek lama pada stok, lalu terapkan efek baru (satu transaksi DB, baris dikunci). */
    public function updateTransaction(Request $request, FeedTransaction $feedTransaction)
    {
        $data = $this->validateTransaction($request, $feedTransaction);
        $isMasuk = $data['type'] === FeedTransaction::TYPE_MASUK;

        $tx = DB::transaction(function () use ($data, $feedTransaction, $isMasuk) {
            // Baca ulang di dalam lock agar tidak memakai data usang.
            $tx = FeedTransaction::lockForUpdate()->findOrFail($feedTransaction->id);

            // Kunci stok lama & baru sekaligus, urut by id (mencegah deadlock).
            $newFeedId = (int) $data['feed_id'];
            $stocks = FeedStock::whereIn('id', [$tx->feed_id, $newFeedId])->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            $oldStock = $stocks->get((int) $tx->feed_id);
            $newStock = $stocks->get($newFeedId);

            $this->revertEffect($oldStock, $tx);
            $this->applyEffect($newStock, $data['type'], (float) $data['quantity']);

            $oldPurchase = FinancialTransaction::where('feed_transaction_id', $tx->id)->first();
            $purchaseDate = $oldPurchase?->transaction_date?->toDateString() ?? now()->toDateString();
            $oldPrice = $oldPurchase?->price_per_kg;
            $oldPurchase?->delete();

            $tx->update([
                'pond_id' => $isMasuk ? null : $data['pond_id'],
                'feed_id' => $newStock->id,
                'type' => $data['type'],
                'quantity' => (float) $data['quantity'],
                'notes' => $data['notes'] ?? null,
            ]);

            if ($isMasuk) {
                $this->createPurchase($tx, $newStock, $this->resolvePrice($data, $newStock, $oldPrice), $purchaseDate);
            }

            // Cek di akhir: pembatalan efek lama boleh sementara minus, hasil akhirnya tidak boleh.
            $stocks->each(fn (FeedStock $s) => $this->ensureNotNegative($s));
            $stocks->each->save();

            return $tx;
        });

        return redirect()->route('feed.index')->with('success', "Transaksi {$tx->code()} diperbarui dan stok disesuaikan.");
    }

    /** DELETE: batalkan efek transaksi pada stok; pembelian terkait ikut terhapus. */
    public function destroyTransaction(FeedTransaction $feedTransaction)
    {
        $code = DB::transaction(function () use ($feedTransaction) {
            $tx = FeedTransaction::lockForUpdate()->findOrFail($feedTransaction->id);
            $stock = FeedStock::lockForUpdate()->findOrFail($tx->feed_id);

            $this->revertEffect($stock, $tx);
            $this->ensureNotNegative($stock);
            $stock->save();

            FinancialTransaction::where('feed_transaction_id', $tx->id)->delete();
            $tx->delete();

            return $tx->code();
        });

        return redirect()->route('feed.index')->with('success', "Transaksi {$code} dihapus dan stok dikembalikan.");
    }

    // ---------------------------------------------------------------- helpers

    private function validateTransaction(Request $request, ?FeedTransaction $existing = null): array
    {
        // Pemberian pakan hanya ke kolam aktif (kolam asli transaksi yang diedit tetap boleh).
        $pondRule = Rule::exists('ponds', 'id')->where(function ($q) use ($existing) {
            $q->where('status', Pond::STATUS_AKTIF);
            if ($existing?->pond_id) {
                $q->orWhere('id', $existing->pond_id);
            }
        });

        return $request->validate([
            'type' => ['required', Rule::in([FeedTransaction::TYPE_MASUK, FeedTransaction::TYPE_KELUAR])],
            'feed_id' => ['required', 'exists:feed_stocks,id'],
            'pond_id' => ['required_if:type,'.FeedTransaction::TYPE_KELUAR, 'nullable', $pondRule],
            'quantity' => ['required', 'numeric', 'gt:0', 'decimal:0,2', 'max:100000'],
            'price_per_kg' => ['nullable', 'numeric', 'gt:0', 'decimal:0,2', 'max:10000000'],
            'notes' => ['nullable', 'string', 'max:255'],
        ], [
            'pond_id.required_if' => 'Pilih kolam tujuan untuk pemakaian pakan.',
            'pond_id.exists' => 'Kolam berstatus panen. Ubah status kolam menjadi Aktif di Data Kolam sebelum memberi pakan.',
            'quantity.required' => 'Jumlah pakan wajib diisi.',
            'quantity.gt' => 'Jumlah pakan harus lebih dari 0.',
            'quantity.decimal' => 'Jumlah pakan maksimal 2 angka di belakang koma.',
            'price_per_kg.gt' => 'Harga beli per kg harus lebih dari 0.',
            'price_per_kg.decimal' => 'Harga beli maksimal 2 angka di belakang koma.',
        ]);
    }

    /** Terapkan transaksi baru ke stok (di memori; pemanggil yang save). Pemakaian tidak boleh melebihi stok. */
    private function applyEffect(FeedStock $stock, string $type, float $qty): void
    {
        $qty = round($qty, 2);
        $current = round($stock->total_stock, 2);

        if ($type === FeedTransaction::TYPE_KELUAR) {
            if ($qty > $current) {
                throw ValidationException::withMessages([
                    'quantity' => 'Jumlah pemakaian ('.Fmt::num($qty).' kg) melebihi stok '.$stock->feed_type.' yang tersedia ('.Fmt::num(max($current, 0)).' kg).',
                ]);
            }
            $stock->total_stock = round($current - $qty, 2);
        } else {
            $stock->total_stock = round($current + $qty, 2);
        }
    }

    /** Batalkan efek transaksi lama dari stok (di memori). Hasil akhir dicek lewat ensureNotNegative(). */
    private function revertEffect(FeedStock $stock, FeedTransaction $tx): void
    {
        $delta = $tx->isKeluar() ? $tx->quantity : -$tx->quantity;
        $stock->total_stock = round($stock->total_stock + $delta, 2);
    }

    private function ensureNotNegative(FeedStock $stock): void
    {
        if (round($stock->total_stock, 2) < 0) {
            throw ValidationException::withMessages([
                'quantity' => 'Perubahan ini membuat stok '.$stock->feed_type.' menjadi negatif karena pakan dari transaksi ini sudah terpakai.',
            ]);
        }
    }

    /** Harga beli: input form, lalu harga lama, lalu harga acuan. Harga 0 ditolak agar biaya tidak tercatat Rp0. */
    private function resolvePrice(array $data, FeedStock $stock, ?float $fallback = null): float
    {
        $price = isset($data['price_per_kg']) ? (float) $data['price_per_kg'] : ($fallback ?? $stock->price_per_kg);

        if ($price <= 0) {
            throw ValidationException::withMessages([
                'price_per_kg' => 'Harga beli per kg wajib diisi karena harga acuan pakan ini masih Rp0.',
            ]);
        }

        return round($price, 2);
    }

    private function createPurchase(FeedTransaction $tx, FeedStock $stock, float $price, string $date): void
    {
        FinancialTransaction::create([
            'pond_id' => null,
            'feed_id' => $stock->id,
            'feed_transaction_id' => $tx->id,
            'type' => FinancialTransaction::TYPE_PEMBELIAN_PAKAN,
            'weight_kg' => $tx->quantity,
            'price_per_kg' => $price,
            'total_amount' => round($tx->quantity * $price, 2),
            'transaction_date' => $date,
            'user_id' => $tx->user_id,
        ]);
    }
}
