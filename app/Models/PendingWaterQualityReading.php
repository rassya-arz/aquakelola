<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PendingWaterQualityReading extends Model
{
    protected $fillable = [
        'pond_id',
        'ph_level',
        'temperature',
        'measured_at',
        'expires_at',
        'user_id',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'ph_level' => 'float',
            'temperature' => 'float',
            'measured_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function pond(): BelongsTo
    {
        return $this->belongsTo(Pond::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
