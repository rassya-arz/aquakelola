<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WaterQuality extends Model
{
    public const MIN_PH = 6.5;
    public const MAX_PH = 8.5;

    protected $fillable = [
        'pond_id', 'ph_level', 'temperature', 'status', 'measured_at', 'user_id',
        'corrective_action', 'action_status', 'action_pic_id', 'recheck_at', 'action_recorded_at',
    ];

    protected function casts(): array
    {
        return [
            'ph_level' => 'float',
            'temperature' => 'float',
            'measured_at' => 'datetime',
            'recheck_at' => 'datetime',
            'action_recorded_at' => 'datetime',
        ];
    }

    /** Tentukan status otomatis dari nilai pH. */
    public static function resolveStatus(float $ph): string
    {
        return ($ph >= self::MIN_PH && $ph <= self::MAX_PH) ? 'dalam_batas' : 'di_luar_batas';
    }

    public function isOutOfRange(): bool
    {
        return $this->status === 'di_luar_batas';
    }

    public function pond(): BelongsTo
    {
        return $this->belongsTo(Pond::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function actionPic(): BelongsTo
    {
        return $this->belongsTo(User::class, 'action_pic_id');
    }

    public function actionCode(): string
    {
        return 'TK-'.str_pad((string) $this->id, 3, '0', STR_PAD_LEFT);
    }

    /** Data tren pH harian per kolam untuk Chart.js. */
    public static function trend(int $days = 7): array
    {
        $start = now()->subDays($days - 1)->startOfDay();
        $rows = static::where('measured_at', '>=', $start)->orderBy('measured_at')->get()->groupBy('pond_id');

        $dates = collect(range(0, $days - 1))->map(fn ($i) => $start->copy()->addDays($i));

        $datasets = Pond::orderBy('name')->get()->map(function (Pond $pond) use ($rows, $dates) {
            $byDay = ($rows[$pond->id] ?? collect())->groupBy(fn ($r) => $r->measured_at->toDateString());

            return [
                'label' => $pond->name,
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
