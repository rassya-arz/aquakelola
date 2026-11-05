<?php

namespace App\Console\Commands;

use App\Models\PendingWaterQualityReading;
use App\Models\WaterQuality;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FinalizeExpiredWaterReadings extends Command
{
    protected $signature = 'water:finalize-expired';

    protected $description =
        'Mencatat hasil pH pending yang telah melewati 24 jam';

    public function handle(): int
    {
        PendingWaterQualityReading::query()
            ->where('status', 'menunggu')
            ->where('expires_at', '<=', now())
            ->orderBy('id')
            ->chunkById(100, function ($readings) {
                foreach ($readings as $reading) {
                    DB::transaction(function () use ($reading) {
                        $pending = PendingWaterQualityReading::query()
                            ->whereKey($reading->id)
                            ->lockForUpdate()
                            ->first();

                        if (
                            ! $pending ||
                            $pending->status !== 'menunggu' ||
                            $pending->expires_at->isFuture()
                        ) {
                            return;
                        }

                        WaterQuality::create([
                            'pond_id' => $pending->pond_id,
                            'ph_level' => $pending->ph_level,
                            'temperature' => $pending->temperature,
                            'status' => WaterQuality::resolveStatus(
                                (float) $pending->ph_level
                            ),
                            'measured_at' => $pending->measured_at,
                            'user_id' => $pending->user_id,
                        ]);

                        $pending->update([
                            'status' => 'dicatat',
                        ]);
                    });
                }
            });

        $this->info('Pemeriksaan pH pending selesai.');

        return self::SUCCESS;
    }
}
