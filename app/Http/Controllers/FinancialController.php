<?php

namespace App\Http\Controllers;

use App\Models\FinancialTransaction;
use App\Models\Pond;
use App\Support\Period;
use Illuminate\Http\Request;

class FinancialController extends Controller
{
    public function index(Request $request)
    {
        $month = Period::month($request);
        $start = $month->copy()->startOfMonth();
        $end = $month->copy()->endOfMonth();

        $monthTx = FinancialTransaction::with(['pond', 'feed'])
            ->whereBetween('transaction_date', [$start, $end])
            ->orderByDesc('transaction_date')->orderByDesc('id')->get();

        $sales = $monthTx->where('type', 'pendapatan_panen');
        $buys = $monthTx->where('type', 'pembelian_pakan');
        $revenue = $sales->sum('total_amount');
        $purchase = $buys->sum('total_amount');

        $filter = in_array($request->query('type'), ['pendapatan_panen', 'pembelian_pakan'], true)
            ? $request->query('type') : 'semua';

        $historyQuery = FinancialTransaction::with(['pond', 'feed'])
            ->whereBetween('transaction_date', [$start, $end])
            ->when($filter !== 'semua', fn ($q) => $q->where('type', $filter))
            ->orderByDesc('transaction_date')->orderByDesc('id');

        return view('finance.index', [
            'month' => $month,
            'revenue' => $revenue,
            'purchase' => $purchase,
            'profit' => $revenue - $purchase,
            'salesCount' => $sales->count(),
            'soldKg' => $sales->sum('weight_kg'),
            'purchaseCount' => $buys->count(),
            'purchaseKg' => $buys->sum('weight_kg'),
            'weekly' => $this->weekly($monthTx, $month),
            'recap' => $this->recap($month),
            'history' => $historyQuery->paginate(10)->withQueryString(),
            'filter' => $filter,
            'ponds' => Pond::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'pond_id' => ['required', 'exists:ponds,id'],
            'weight_kg' => ['required', 'numeric', 'gt:0', 'max:1000000'],
            'price_per_kg' => ['required', 'numeric', 'gt:0', 'max:100000000'],
            'transaction_date' => ['required', 'date', 'before_or_equal:today'],
            'mark_harvested' => ['nullable', 'boolean'],
        ], [
            'pond_id.required' => 'Pilih kolam panen.',
            'weight_kg.gt' => 'Berat panen harus lebih dari 0 kg.',
            'price_per_kg.gt' => 'Harga per kg harus lebih dari 0.',
            'transaction_date.before_or_equal' => 'Tanggal penjualan tidak boleh di masa depan.',
        ]);

        $weight = (float) $data['weight_kg'];
        $price = (float) $data['price_per_kg'];

        $tx = FinancialTransaction::create([
            'pond_id' => $data['pond_id'],
            'type' => 'pendapatan_panen',
            'weight_kg' => $weight,
            'price_per_kg' => $price,
            'total_amount' => round($weight * $price, 2), // total otomatis
            'transaction_date' => $data['transaction_date'],
            'user_id' => $request->user()->id,
        ]);

        if ($request->boolean('mark_harvested')) {
            Pond::whereKey($data['pond_id'])->update(['status' => 'panen']);
        }

        return redirect()->route('finance.index', ['month' => $tx->transaction_date->format('Y-m')])
            ->with('success', 'Penjualan panen tersimpan: Rp'.number_format($tx->total_amount, 0, ',', '.').'.');
    }

    public function edit(FinancialTransaction $financialTransaction)
    {
        $this->ensureRevenue($financialTransaction);

        return view('finance.edit', ['tx' => $financialTransaction, 'ponds' => Pond::orderBy('name')->get()]);
    }

    public function update(Request $request, FinancialTransaction $financialTransaction)
    {
        $this->ensureRevenue($financialTransaction);

        $data = $request->validate([
            'pond_id' => ['required', 'exists:ponds,id'],
            'weight_kg' => ['required', 'numeric', 'gt:0', 'max:1000000'],
            'price_per_kg' => ['required', 'numeric', 'gt:0', 'max:100000000'],
            'transaction_date' => ['required', 'date', 'before_or_equal:today'],
            'mark_harvested' => ['nullable', 'boolean'],
        ]);

        $financialTransaction->update([
            'pond_id' => $data['pond_id'],
            'weight_kg' => $data['weight_kg'],
            'price_per_kg' => $data['price_per_kg'],
            'total_amount' => round((float) $data['weight_kg'] * (float) $data['price_per_kg'], 2),
            'transaction_date' => $data['transaction_date'],
        ]);

        if ($request->boolean('mark_harvested')) {
            Pond::whereKey($data['pond_id'])->update(['status' => 'panen']);
        }

        return redirect()->route('finance.index', ['month' => $financialTransaction->transaction_date->format('Y-m')])
            ->with('success', 'Penjualan panen diperbarui. Total: Rp'.number_format($financialTransaction->total_amount, 0, ',', '.').'.');
    }

    public function destroy(FinancialTransaction $financialTransaction)
    {
        $this->ensureRevenue($financialTransaction);
        $month = $financialTransaction->transaction_date->format('Y-m');
        $financialTransaction->delete();

        return redirect()->route('finance.index', ['month' => $month])->with('success', 'Penjualan panen dihapus.');
    }

    /** Pembelian pakan dibuat otomatis dari transaksi pakan masuk, jadi dikelola dari halaman Stok Pakan. */
    private function ensureRevenue(FinancialTransaction $tx): void
    {
        abort_unless($tx->isRevenue(), 403, 'Pembelian pakan dikelola dari halaman Stok Pakan.');
    }

    public function export(Request $request)
    {
        $month = Period::month($request);
        $rows = FinancialTransaction::with(['pond', 'feed'])
            ->whereBetween('transaction_date', [$month->copy()->startOfMonth(), $month->copy()->endOfMonth()])
            ->orderBy('transaction_date')->get();

        $filename = 'laba-rugi-'.$month->format('Y-m').'.csv';

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Tanggal', 'Transaksi', 'Rincian', 'Jumlah (kg)', 'Harga/kg', 'Nilai']);
            foreach ($rows as $r) {
                fputcsv($out, [
                    $r->transaction_date->toDateString(),
                    $r->isRevenue() ? 'Pendapatan panen' : 'Pembelian pakan',
                    $r->isRevenue() ? ($r->pond?->fish_type.' - '.$r->pond?->name) : ($r->feed?->feed_type ?? '-'),
                    $r->weight_kg, $r->price_per_kg,
                    ($r->isRevenue() ? '' : '-').$r->total_amount,
                ]);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    /** Pendapatan vs pembelian per minggu (dalam juta rupiah). */
    private function weekly($transactions, $month): array
    {
        $last = $month->daysInMonth;
        $short = $month->translatedFormat('M');
        $labels = ["1–7 {$short}", "8–14 {$short}", "15–21 {$short}", "22–{$last} {$short}"];
        $revenue = [0, 0, 0, 0];
        $purchase = [0, 0, 0, 0];

        foreach ($transactions as $t) {
            $i = min(3, intdiv($t->transaction_date->day - 1, 7));
            if ($t->isRevenue()) {
                $revenue[$i] += $t->total_amount;
            } else {
                $purchase[$i] += $t->total_amount;
            }
        }

        return [
            'labels' => $labels,
            'revenue' => array_map(fn ($v) => round($v / 1_000_000, 2), $revenue),
            'purchase' => array_map(fn ($v) => round($v / 1_000_000, 2), $purchase),
        ];
    }

    /** Rekapitulasi laba rugi bulanan (6 bulan terakhir sampai bulan terpilih). */
    private function recap($month): array
    {
        $from = $month->copy()->subMonths(5)->startOfMonth();
        $grouped = FinancialTransaction::whereBetween('transaction_date', [$from, $month->copy()->endOfMonth()])
            ->get()->groupBy(fn ($t) => $t->transaction_date->format('Y-m'));

        $rows = [];
        for ($i = 5; $i >= 0; $i--) {
            $m = $month->copy()->subMonths($i);
            $items = $grouped[$m->format('Y-m')] ?? collect();
            $rev = $items->where('type', 'pendapatan_panen')->sum('total_amount');
            $buy = $items->where('type', 'pembelian_pakan')->sum('total_amount');
            $rows[] = [
                'key' => $m->format('Y-m'),
                'label' => $m->translatedFormat('F Y'),
                'revenue' => $rev,
                'purchase' => $buy,
                'profit' => $rev - $buy,
            ];
        }

        return array_reverse($rows);
    }
}
