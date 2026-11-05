<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinancialTransaction extends Model
{
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

    public function isRevenue(): bool
    {
        return $this->type === 'pendapatan_panen';
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
