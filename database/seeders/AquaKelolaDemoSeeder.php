<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class AquaKelolaDemoSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $start = Carbon::parse('2026-08-01');
            $end = Carbon::parse('2026-10-29');

            $user = DB::table('users')
                ->where('email', 'pengelola@aquakelola.test')
                ->first();

            if (!$user) {
                throw new RuntimeException(
                    'Akun pengelola tidak ditemukan.'
                );
            }

            $userId = $user->id;

            // 1. DATA KOLAM
            $pondData = [
                [
                    'name' => 'Kolam Nila 1',
                    'fish_type' => 'Nila',
                    'status' => 'aktif',
                ],
                [
                    'name' => 'Kolam Lele 1',
                    'fish_type' => 'Lele',
                    'status' => 'aktif',
                ],
                [
                    'name' => 'Kolam Nila 2',
                    'fish_type' => 'Nila',
                    'status' => 'panen',
                ],
            ];

            $ponds = [];

            foreach ($pondData as $item) {
                $pond = DB::table('ponds')
                    ->where('name', $item['name'])
                    ->first();

                if (!$pond) {
                    $id = DB::table('ponds')->insertGetId([
                        ...$item,
                        'created_at' => $start,
                        'updated_at' => $start,
                    ]);

                    $pond = DB::table('ponds')
                        ->where('id', $id)
                        ->first();
                }

                $ponds[] = $pond;
            }

            // 2. DATA PAKAN
            $feedData = [
                [
                    'feed_type' => 'Pelet Nila',
                    'minimum_stock' => 25,
                    'price_per_kg' => 12000,
                ],
                [
                    'feed_type' => 'Pelet Lele',
                    'minimum_stock' => 20,
                    'price_per_kg' => 11000,
                ],
            ];

            $feeds = [];

            foreach ($feedData as $item) {
                $feed = DB::table('feed_stocks')
                    ->where('feed_type', $item['feed_type'])
                    ->first();

                if (!$feed) {
                    $id = DB::table('feed_stocks')->insertGetId([
                        ...$item,
                        'total_stock' => 0,
                        'created_at' => $start,
                        'updated_at' => $start,
                    ]);

                    $feed = DB::table('feed_stocks')
                        ->where('id', $id)
                        ->first();
                } else {
                    $hasOtherTransactions = DB::table('feed_transactions')
                        ->where('feed_id', $feed->id)
                        ->where(function ($query) {
                            $query->whereNull('notes')
                                ->orWhere('notes', 'not like', '[AQK:%');
                        })
                        ->exists();

                    if ($hasOtherTransactions) {
                        throw new RuntimeException(
                            'Pakan "' . $item['feed_type']
                            . '" memiliki transaksi lain. '
                            . 'Periksa data sebelum melanjutkan.'
                        );
                    }

                    $hasMarkedTransactions = DB::table('feed_transactions')
                        ->where('feed_id', $feed->id)
                        ->where('notes', 'like', '[AQK:%')
                        ->exists();

                    if (
                        (float) $feed->total_stock > 0
                        && !$hasMarkedTransactions
                    ) {
                        throw new RuntimeException(
                            'Stok pakan "' . $item['feed_type']
                            . '" sudah tersedia. Proses dibatalkan '
                            . 'agar stok lama tidak berubah.'
                        );
                    }
                }

                $feeds[] = $feed;
            }

            // 3. HELPER TRANSAKSI PAKAN
            // Penanda AQK digunakan untuk mencegah duplikasi.
            $addFeedTransaction = function (
                $pondId,
                $feedId,
                string $type,
                float $quantity,
                string $date,
                string $marker
            ) use ($userId) {
                $notes = '[AQK:' . $marker . '] Catatan operasional';

                $existing = DB::table('feed_transactions')
                    ->where('notes', $notes)
                    ->first();

                if ($existing) {
                    return $existing;
                }

                $id = DB::table('feed_transactions')->insertGetId([
                    'pond_id' => $pondId,
                    'feed_id' => $feedId,
                    'type' => $type,
                    'quantity' => $quantity,
                    'notes' => $notes,
                    'user_id' => $userId,
                    'created_at' => Carbon::parse($date),
                    'updated_at' => Carbon::parse($date),
                ]);

                return DB::table('feed_transactions')
                    ->where('id', $id)
                    ->first();
            };

            // 4. STOK AWAL DAN PEMBELIAN PAKAN
            $purchases = [
                ['2026-08-12', 80],
                ['2026-09-10', 70],
                ['2026-10-08', 60],
            ];

            foreach ($feeds as $index => $feed) {
                $prefix = $index === 0 ? 'NILA' : 'LELE';

                $addFeedTransaction(
                    null,
                    $feed->id,
                    'masuk',
                    200,
                    '2026-08-01',
                    $prefix . '-STOK-AWAL'
                );

                foreach ($purchases as $i => [$date, $quantity]) {
                    $addFeedTransaction(
                        null,
                        $feed->id,
                        'masuk',
                        (float) $quantity,
                        $date,
                        $prefix . '-BELI-' . ($i + 1)
                    );
                }
            }

            // 5. PEMBERIAN PAKAN MINGGUAN
            foreach ($ponds as $pond) {
                $feed = $pond->fish_type === 'Nila'
                    ? $feeds[0]
                    : $feeds[1];

                for (
                    $date = $start->copy();
                    $date->lte($end);
                    $date->addDays(7)
                ) {
                    $addFeedTransaction(
                        $pond->id,
                        $feed->id,
                        'keluar',
                        12,
                        $date->format('Y-m-d'),
                        'PAKAN-' . $pond->id . '-'
                            . $date->format('Ymd')
                    );
                }
            }

            // 6. PEMANTAUAN KUALITAS AIR
            // Nilai berikut adalah data simulasi, bukan pengukuran nyata.
            $measurements = [
                ['2026-08-03 07:00:00', 7.1, 28.0],
                ['2026-08-17 07:00:00', 6.4, 29.0],
                ['2026-09-01 07:00:00', 7.0, 28.5],
                ['2026-09-15 07:00:00', 7.2, 29.0],
                ['2026-10-01 07:00:00', 6.3, 30.0],
                ['2026-10-15 07:00:00', 7.0, 28.0],
                ['2026-10-29 07:00:00', 7.1, 28.5],
            ];

            foreach ($ponds as $pond) {
                foreach ($measurements as $measurement) {
                    $measuredAt = $measurement[0];
                    $ph = $measurement[1];
                    $temperature = $measurement[2];

                    $marker = '[AQK:' . $pond->id . ':'
                        . str_replace(
                            [' ', ':', '-'],
                            '',
                            $measuredAt
                        ) . ']';

                    $exists = DB::table('water_qualities')
                        ->where('pond_id', $pond->id)
                        ->where('measured_at', $measuredAt)
                        ->where(
                            'corrective_action',
                            'like',
                            $marker . '%'
                        )
                        ->exists();

                    if ($exists) {
                        continue;
                    }

                    $outside = $ph < 6.5;

                    DB::table('water_qualities')->insert([
                        'pond_id' => $pond->id,
                        'ph_level' => $ph,
                        'temperature' => $temperature,
                        'status' => $outside
                            ? 'di_luar_batas'
                            : 'dalam_batas',
                        'measured_at' => $measuredAt,
                        'user_id' => $userId,
                        'corrective_action' => $marker . (
                            $outside
                                ? ' Evaluasi kualitas air dan lakukan '
                                    . 'pemeriksaan ulang.'
                                : ' Lanjutkan pemantauan berkala.'
                        ),
                        'action_status' => $outside ? 'selesai' : null,
                        'action_pic_id' => $outside ? $userId : null,
                        'recheck_at' => $outside
                            ? Carbon::parse($measuredAt)->addDay()
                            : null,
                        'action_recorded_at' => $outside
                            ? Carbon::parse($measuredAt)->addHours(2)
                            : null,
                        'created_at' => Carbon::parse($measuredAt),
                        'updated_at' => Carbon::parse($measuredAt),
                    ]);
                }
            }

            // 7. PENDAPATAN PANEN
            $harvests = [
                [
                    'pond_index' => 2,
                    'weight' => 120,
                    'price' => 25000,
                    'date' => '2026-08-25',
                ],
                [
                    'pond_index' => 0,
                    'weight' => 100,
                    'price' => 28000,
                    'date' => '2026-09-20',
                ],
                [
                    'pond_index' => 2,
                    'weight' => 140,
                    'price' => 25000,
                    'date' => '2026-10-20',
                ],
            ];

            foreach ($harvests as $harvest) {
                $pond = $ponds[$harvest['pond_index']];
                $amount = $harvest['weight'] * $harvest['price'];

                $exists = DB::table('financial_transactions')
                    ->where('pond_id', $pond->id)
                    ->where('type', 'pendapatan_panen')
                    ->where('transaction_date', $harvest['date'])
                    ->where('total_amount', $amount)
                    ->exists();

                if ($exists) {
                    continue;
                }

                DB::table('financial_transactions')->insert([
                    'pond_id' => $pond->id,
                    'feed_id' => null,
                    'feed_transaction_id' => null,
                    'type' => 'pendapatan_panen',
                    'weight_kg' => $harvest['weight'],
                    'price_per_kg' => $harvest['price'],
                    'total_amount' => $amount,
                    'transaction_date' => $harvest['date'],
                    'user_id' => $userId,
                    'created_at' => Carbon::parse($harvest['date']),
                    'updated_at' => Carbon::parse($harvest['date']),
                ]);
            }

            // 8. CATAT PEMBELIAN PAKAN DALAM KEUANGAN
            foreach ($feeds as $index => $feed) {
                $prefix = $index === 0 ? 'NILA' : 'LELE';

                foreach ($purchases as $i => [$date, $quantity]) {
                    $marker = '[AQK:' . $prefix
                        . '-BELI-' . ($i + 1)
                        . '] Catatan operasional';

                    $feedTransaction = DB::table('feed_transactions')
                        ->where('notes', $marker)
                        ->first();

                    if (!$feedTransaction) {
                        continue;
                    }

                    $price = (float) $feed->price_per_kg;
                    $amount = $quantity * $price;

                    $exists = DB::table('financial_transactions')
                        ->where('feed_transaction_id', $feedTransaction->id)
                        ->exists();

                    if ($exists) {
                        continue;
                    }

                    DB::table('financial_transactions')->insert([
                        'pond_id' => null,
                        'feed_id' => $feed->id,
                        'feed_transaction_id' => $feedTransaction->id,
                        'type' => 'pembelian_pakan',
                        'weight_kg' => $quantity,
                        'price_per_kg' => $price,
                        'total_amount' => $amount,
                        'transaction_date' => $date,
                        'user_id' => $userId,
                        'created_at' => Carbon::parse($date),
                        'updated_at' => Carbon::parse($date),
                    ]);
                }
            }

            // 9. HITUNG ULANG STOK PAKAN
            foreach ($feeds as $feed) {
                $masuk = DB::table('feed_transactions')
                    ->where('feed_id', $feed->id)
                    ->where('notes', 'like', '[AQK:%')
                    ->where('type', 'masuk')
                    ->sum('quantity');

                $keluar = DB::table('feed_transactions')
                    ->where('feed_id', $feed->id)
                    ->where('notes', 'like', '[AQK:%')
                    ->where('type', 'keluar')
                    ->sum('quantity');

                DB::table('feed_stocks')
                    ->where('id', $feed->id)
                    ->update([
                        'total_stock' => max(0, $masuk - $keluar),
                        'updated_at' => now(),
                    ]);
            }
        });

        $this->command?->info(
            'Data kolam, pakan, kualitas air, dan keuangan berhasil diproses.'
        );
    }
}