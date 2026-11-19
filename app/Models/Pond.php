<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Pond extends Model
{
    public const STATUS_AKTIF = 'aktif';
    public const STATUS_PANEN = 'panen';
    public const STATUS_PERBAIKAN = 'perbaikan';

    public const STATUS_LABELS = [
        self::STATUS_AKTIF => 'Aktif',
        self::STATUS_PANEN => 'Panen',
        self::STATUS_PERBAIKAN => 'Perbaikan',
    ];

    /** Saran awal untuk input jenis ikan; jenis lain yang pernah disimpan ikut ditambahkan. */
    public const DEFAULT_FISH_TYPES = ['Nila', 'Lele', 'Gurame', 'Patin'];

    protected $fillable = ['name', 'fish_type', 'status', 'cycle_started_at'];

    protected function casts(): array
    {
        return ['cycle_started_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::updating(function (Pond $pond) {
            if (! $pond->isDirty('status')) {
                return;
            }

            // Hanya Panen -> Aktif yang memulai siklus baru. Perbaikan -> Aktif melanjutkan siklus berjalan.
            if ($pond->status === self::STATUS_AKTIF && $pond->getOriginal('status') === self::STATUS_PANEN) {
                $pond->cycle_started_at = now();
            }
        });
    }

    public function isAktif(): bool
    {
        return $this->status === self::STATUS_AKTIF;
    }

    public function isPanen(): bool
    {
        return $this->status === self::STATUS_PANEN;
    }

    public function isPerbaikan(): bool
    {
        return $this->status === self::STATUS_PERBAIKAN;
    }

    public function statusLabel(): string
    {
        return self::STATUS_LABELS[$this->status] ?? ucfirst((string) $this->status);
    }

    /** Daftar saran jenis ikan (bawaan + yang sudah tersimpan), tanpa duplikat huruf besar/kecil. */
    public static function fishTypeSuggestions(): array
    {
        return collect(self::DEFAULT_FISH_TYPES)
            ->merge(static::query()->distinct()->pluck('fish_type'))
            ->unique(fn (string $v) => mb_strtolower($v))
            ->sortBy(fn (string $v) => mb_strtolower($v))
            ->values()
            ->all();
    }

    public function scopeAktif(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_AKTIF);
    }

    /**
     * Hitung catatan terkait hanya untuk siklus berjalan (created_at >= cycle_started_at).
     * Data lama tetap tersimpan untuk riwayat dan laporan laba rugi.
     */
    public function scopeWithCycleCounts(Builder $query): Builder
    {
        $inCycle = fn (Builder $q) => $q->where(function ($w) use ($q) {
            $table = $q->getModel()->getTable();
            $w->whereNull('ponds.cycle_started_at')
                ->orWhereColumn("{$table}.created_at", '>=', 'ponds.cycle_started_at');
        });

        return $query->withCount([
            'waterQualities' => $inCycle,
            'feedTransactions' => $inCycle,
            'financialTransactions' => $inCycle,
        ]);
    }

    /** Jumlah catatan terkait untuk tampilan: selalu 0 saat kolam berstatus panen. */
    public function cycleCount(string $relation): int
    {
        return $this->isPanen() ? 0 : (int) $this->getAttribute($relation.'_count');
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
