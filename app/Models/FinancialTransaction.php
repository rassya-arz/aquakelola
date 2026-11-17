<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinancialTransaction extends Model
{
    public const TYPE_PENDAPATAN_PANEN = 'pendapatan_panen';
    public const TYPE_PEMBELIAN_PAKAN = 'pembelian_pakan';

    protected $fillable = [
        'pond_id', 'feed_id', 'feed_transaction_id', 'type', 'weight_kg', 'price_per_kg', 'total_amount', 'transaction_date', 'user_id',
    ];

    protected function casts(): array
    {
        return [
            'weight_kg' => 'float',
            'price_per_kg' => 'float',
            'total_amount' => 'float',
            'transaction_date' => 'date',
        ];
    }

    /**
     * Ringkasan satu bulan dalam SATU query agregat (dipakai Dashboard dan Laba Rugi).
     * Laba/rugi = pendapatan panen − biaya pembelian pakan.
     */
    public static function monthSummary(Carbon $month): array
    {
        $rows = static::query()
            ->whereBetween('transaction_date', [$month->copy()->startOfMonth(), $month->copy()->endOfMonth()])
            ->selectRaw('type, COUNT(*) as total_rows, COALESCE(SUM(total_amount), 0) as amount, COALESCE(SUM(weight_kg), 0) as kg')
            ->groupBy('type')
            ->get()
            ->keyBy('type');

        $sales = $rows->get(self::TYPE_PENDAPATAN_PANEN);
        $buys = $rows->get(self::TYPE_PEMBELIAN_PAKAN);

        $revenue = round((float) ($sales->amount ?? 0), 2);
        $purchase = round((float) ($buys->amount ?? 0), 2);

        return [
            'revenue' => $revenue,
            'salesCount' => (int) ($sales->total_rows ?? 0),
            'soldKg' => (float) ($sales->kg ?? 0),
            'purchase' => $purchase,
            'purchaseCount' => (int) ($buys->total_rows ?? 0),
            'purchaseKg' => (float) ($buys->kg ?? 0),
            'profit' => round($revenue - $purchase, 2),
        ];
    }

    public function isRevenue(): bool
    {
        return $this->type === self::TYPE_PENDAPATAN_PANEN;
    }

    public function pond(): BelongsTo
    {
        return $this->belongsTo(Pond::class);
    }

    public function feed(): BelongsTo
    {
        return $this->belongsTo(FeedStock::class, 'feed_id');
    }

    public function feedTransaction(): BelongsTo
    {
        return $this->belongsTo(FeedTransaction::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
