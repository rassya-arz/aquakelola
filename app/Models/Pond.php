<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Pond extends Model
{
    protected $fillable = ['name', 'fish_type', 'status'];

    public function scopeAktif(Builder $query): Builder
    {
        return $query->where('status', 'aktif');
    }

    public function feedTransactions(): HasMany
    {
        return $this->hasMany(FeedTransaction::class);
    }

    public function waterQualities(): HasMany
    {
        return $this->hasMany(WaterQuality::class);
    }

    public function financialTransactions(): HasMany
    {
        return $this->hasMany(FinancialTransaction::class);
    }

    public function latestWater(): HasOne
    {
        return $this->hasOne(WaterQuality::class)->latestOfMany('measured_at');
    }
}
