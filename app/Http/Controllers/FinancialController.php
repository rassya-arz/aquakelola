<?php

namespace App\Http\Controllers;

use App\Models\FinancialTransaction;
use App\Models\Pond;
use App\Support\Fmt;
use App\Support\Period;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class FinancialController extends Controller
{
    public function index(Request $request)
    {
        $month = Period::month($request);
        $start = $month->copy()->startOfMonth();
        $end = $month->copy()->endOfMonth();

        $filter = in_array($request->query('type'), [FinancialTransaction::TYPE_PENDAPATAN_PANEN, FinancialTransaction::TYPE_PEMBELIAN_PAKAN], true)
            ? $request->query('type') : 'semua';

        $history = FinancialTransaction::with(['pond', 'feed'])
            ->whereBetween('transaction_date', [$start, $end])
            ->when($filter !== 'semua', fn ($q) => $q->where('type', $filter))
            ->orderByDesc('transaction_date')->orderByDesc('id')
            ->paginate(10)->withQueryString();

        // Satu query total harian untuk rekap 6 bulan; grafik mingguan diambil dari bulan terpilih.
        $daily = $this->dailyTotals($month->copy()->subMonths(5)->startOfMonth(), $end);

        return view('finance.index', [
            'month' => $month,
            ...FinancialTransaction::monthSummary($month),
            'weekly' => $this->weekly($daily, $month),
            'recap' => $this->recap($daily, $month),
            'history' => $history,
            'filter' => $filter,
            'ponds' => Pond::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->rules(), $this->messages());

        $tx = DB::transaction(function () use ($data, $request) {
            $tx = FinancialTransaction::create($this->payload($data) + [
                'type' => FinancialTransaction::TYPE_PENDAPATAN_PANEN,
                'user_id' => $request->user()->id,
            ]);

            $this->markHarvestedIfRequested($request, (int) $data['pond_id']);

            return $tx;
        });

        return redirect()->route('finance.index', ['month' => $tx->transaction_date->format('Y-m')])
            ->with('success', 'Penjualan panen tersimpan: '.Fmt::rp($tx->total_amount).'.');
    }

    public function edit(FinancialTransaction $financialTransaction)
    {
        $this->ensureRevenue($financialTransaction);

        return view('finance.edit', ['tx' => $financialTransaction, 'ponds' => Pond::orderBy('name')->get()]);
    }

    public function update(Request $request, FinancialTransaction $financialTransaction)
    {
        $this->ensureRevenue($financialTransaction);

        $data = $request->validate($this->rules(), $this->messages());

        DB::transaction(function () use ($data, $request, $financialTransaction) {
            $financialTransaction->update($this->payload($data));
            $this->markHarvestedIfRequested($request, (int) $data['pond_id']);
        });

        return redirect()->route('finance.index', ['month' => $financialTransaction->transaction_date->format('Y-m')])
            ->with('success', 'Penjualan panen diperbarui. Total: '.Fmt::rp($financialTransaction->total_amount).'.');
    }

    public function destroy(FinancialTransaction $financialTransaction)
    {
        $this->ensureRevenue($financialTransaction);
        $month = $financialTransaction->transaction_date->format('Y-m');
        $financialTransaction->delete();

        return redirect()->route('finance.index', ['month' => $month])->with('success', 'Penjualan panen dihapus.');
    }

    public function export(Request $request)
    {
        $month = Period::month($request);
        $summary = FinancialTransaction::monthSummary($month);

        $rows = FinancialTransaction::with(['pond', 'feed'])
            ->whereBetween('transaction_date', [$month->copy()->startOfMonth(), $month->copy()->endOfMonth()])
            ->orderBy('transaction_date')->orderBy('id')->get();

        $filename = 'laba-rugi-'.$month->format('Y-m').'.csv';

        return response()->streamDownload(function () use ($rows, $summary) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // BOM agar Excel membaca UTF-8

            fputcsv($out, ['Tanggal', 'Transaksi', 'Rincian', 'Jumlah (kg)', 'Harga/kg', 'Nilai (Rp)']);
            foreach ($rows as $r) {
                fputcsv($out, [
                    $r->transaction_date->toDateString(),
                    $r->isRevenue() ? 'Pendapatan panen' : 'Pembelian pakan',
                    $this->csvSafe($r->isRevenue() ? trim($r->pond?->fish_type.' - '.$r->pond?->name, ' -') : ($r->feed?->feed_type ?? '-')),
                    $r->weight_kg,
                    $r->price_per_kg,
                    $r->isRevenue() ? $r->total_amount : -$r->total_amount,
                ]);
            }

            fputcsv($out, []);
            fputcsv($out, ['', 'Total pendapatan panen', '', '', '', $summary['revenue']]);
            fputcsv($out, ['', 'Total pembelian pakan', '', '', '', -$summary['purchase']]);
            fputcsv($out, ['', 'Laba / rugi', '', '', '', $summary['profit']]);
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    // ---------------------------------------------------------------- helpers

    private function rules(): array
    {
        // Batas dijaga agar berat × harga tidak melebihi kolom decimal(14,2).
        return [
            'pond_id' => ['required', 'exists:ponds,id'],
            'weight_kg' => ['required', 'numeric', 'gt:0', 'decimal:0,2', 'max:100000'],
            'price_per_kg' => ['required', 'numeric', 'gt:0', 'decimal:0,2', 'max:1000000'],
            'transaction_date' => ['required', 'date', 'before_or_equal:today'],
            'mark_harvested' => ['nullable', 'boolean'],
        ];
    }

    private function messages(): array
    {
        return [
            'pond_id.required' => 'Pilih kolam panen.',
            'weight_kg.gt' => 'Berat panen harus lebih dari 0 kg.',
            'weight_kg.decimal' => 'Berat panen maksimal 2 angka di belakang koma.',
            'weight_kg.max' => 'Berat panen maksimal 100.000 kg per transaksi.',
            'price_per_kg.gt' => 'Harga per kg harus lebih dari 0.',
            'price_per_kg.decimal' => 'Harga per kg maksimal 2 angka di belakang koma.',
            'price_per_kg.max' => 'Harga per kg maksimal Rp1.000.000.',
            'transaction_date.before_or_equal' => 'Tanggal penjualan tidak boleh di masa depan.',
        ];
    }

    /** Kolom transaksi dari data tervalidasi; total dihitung otomatis di server. */
    private function payload(array $data): array
    {
        $weight = (float) $data['weight_kg'];
        $price = (float) $data['price_per_kg'];

        return [
            'pond_id' => $data['pond_id'],
            'weight_kg' => $weight,
            'price_per_kg' => $price,
            'total_amount' => round($weight * $price, 2),
            'transaction_date' => $data['transaction_date'],
        ];
    }

    private function markHarvestedIfRequested(Request $request, int $pondId): void
    {
        if ($request->boolean('mark_harvested')) {
            Pond::findOrFail($pondId)->update(['status' => Pond::STATUS_PANEN]);
        }
    }

    /** Pembelian pakan dibuat otomatis dari transaksi pakan masuk, jadi dikelola dari halaman Stok Pakan. */
    private function ensureRevenue(FinancialTransaction $tx): void
    {
        abort_unless($tx->isRevenue(), 403, 'Pembelian pakan dikelola dari halaman Stok Pakan.');
    }

    /** Cegah formula injection saat CSV dibuka di Excel/Sheets. */
    private function csvSafe(string $value): string
    {
        return preg_match('/^[=+\-@\t\r]/', $value) ? "'".$value : $value;
    }

    /** Total per hari per jenis (SUM di database), tanpa memuat model satu per satu. */
    private function dailyTotals(Carbon $from, Carbon $to): Collection
    {
        return FinancialTransaction::query()
            ->whereBetween('transaction_date', [$from, $to])
            ->selectRaw('transaction_date, type, SUM(total_amount) as amount')
            ->groupBy('transaction_date', 'type')
            ->toBase()->get()
            ->map(fn ($r) => (object) [
                'date' => Carbon::parse($r->transaction_date),
                'type' => $r->type,
                'amount' => (float) $r->amount,
            ]);
    }

    /** Pendapatan vs pembelian per minggu (dalam juta rupiah). */
    private function weekly(Collection $daily, Carbon $month): array
    {
        $last = $month->daysInMonth;
        $short = $month->translatedFormat('M');
        $revenue = [0, 0, 0, 0];
        $purchase = [0, 0, 0, 0];

        foreach ($daily->filter(fn ($r) => $r->date->format('Y-m') === $month->format('Y-m')) as $r) {
            $i = min(3, intdiv($r->date->day - 1, 7));
            if ($r->type === FinancialTransaction::TYPE_PENDAPATAN_PANEN) {
                $revenue[$i] += $r->amount;
            } else {
                $purchase[$i] += $r->amount;
            }
        }

        return [
            'labels' => ["1–7 {$short}", "8–14 {$short}", "15–21 {$short}", "22–{$last} {$short}"],
            'revenue' => array_map(fn ($v) => round($v / 1_000_000, 2), $revenue),
            'purchase' => array_map(fn ($v) => round($v / 1_000_000, 2), $purchase),
        ];
    }

    /** Rekapitulasi laba rugi bulanan (6 bulan terakhir sampai bulan terpilih), terbaru di atas. */
    private function recap(Collection $daily, Carbon $month): array
    {
        $byMonth = $daily->groupBy(fn ($r) => $r->date->format('Y-m'));
        $rows = [];

        for ($i = 0; $i <= 5; $i++) {
            $m = $month->copy()->subMonths($i);
            $items = $byMonth->get($m->format('Y-m'), collect());
            $rev = round($items->where('type', FinancialTransaction::TYPE_PENDAPATAN_PANEN)->sum('amount'), 2);
            $buy = round($items->where('type', FinancialTransaction::TYPE_PEMBELIAN_PAKAN)->sum('amount'), 2);

            $rows[] = [
                'key' => $m->format('Y-m'),
                'label' => $m->translatedFormat('F Y'),
                'revenue' => $rev,
                'purchase' => $buy,
                'profit' => round($rev - $buy, 2),
            ];
        }

        return $rows;
    }
}
