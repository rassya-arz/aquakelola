<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WaterQuality extends Model
{
    public const MIN_PH = 6.5;
    public const MAX_PH = 8.5;

    public const STATUS_DALAM_BATAS = 'dalam_batas';
    public const STATUS_DI_LUAR_BATAS = 'di_luar_batas';

    protected $fillable = ['pond_id', 'ph_level', 'status', 'measured_at', 'user_id'];

    protected function casts(): array
    {
        return [
            'ph_level' => 'float',
            'measured_at' => 'datetime',
        ];
    }

    /** Tentukan status otomatis dari nilai pH. */
    public static function resolveStatus(float $ph): string
    {
        return ($ph >= self::MIN_PH && $ph <= self::MAX_PH)
            ? self::STATUS_DALAM_BATAS
            : self::STATUS_DI_LUAR_BATAS;
    }

    public function isOutOfRange(): bool
    {
        return $this->status === self::STATUS_DI_LUAR_BATAS;
    }

    public function pond(): BelongsTo
    {
        return $this->belongsTo(Pond::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Data tren pH harian (rata-rata per hari) per kolam untuk Chart.js.
     * Hanya kolam yang punya data pada rentang tersebut yang ikut ditampilkan.
     */
    public static function trend(int $days = 7): array
    {
        $start = now()->subDays($days - 1)->startOfDay();
        $dates = collect(range(0, $days - 1))->map(fn ($i) => $start->copy()->addDays($i));

        $byPond = static::where('measured_at', '>=', $start)
            ->orderBy('measured_at')
            ->get(['pond_id', 'ph_level', 'measured_at'])
            ->groupBy('pond_id');

        $names = Pond::whereIn('id', $byPond->keys())->orderBy('name')->pluck('name', 'id');

        $datasets = $names->map(function (string $name, int $pondId) use ($byPond, $dates) {
            $byDay = $byPond[$pondId]->groupBy(fn ($r) => $r->measured_at->toDateString());

            return [
                'label' => $name,
                'data' => $dates->map(fn ($d) => isset($byDay[$d->toDateString()])
                    ? round($byDay[$d->toDateString()]->avg('ph_level'), 2)
                    : null)->all(),
            ];
        })->values()->all();

        return [
            'labels' => $dates->map(fn ($d) => $d->translatedFormat('d M'))->all(),
            'datasets' => $datasets,
        ];
    }
}
