<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FeedStock extends Model
{
    protected $fillable = ['feed_type', 'total_stock', 'minimum_stock', 'price_per_kg'];

    protected function casts(): array
    {
        return [
            'total_stock' => 'float',
            'minimum_stock' => 'float',
            'price_per_kg' => 'float',
        ];
    }

    public function isLow(): bool
    {
        return $this->total_stock < $this->minimum_stock;
    }

    /** habis | rendah | tersedia */
    public function stockStatus(): string
    {
        if ($this->total_stock <= 0) {
            return 'habis';
        }

        return $this->isLow() ? 'rendah' : 'tersedia';
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(FeedTransaction::class, 'feed_id');
    }

    public function financialTransactions(): HasMany
    {
        return $this->hasMany(FinancialTransaction::class, 'feed_id');
    }
}
