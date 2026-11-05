<?php

namespace Database\Seeders;

use App\Models\FeedStock;
use App\Models\FeedTransaction;
use App\Models\FinancialTransaction;
use App\Models\Pond;
use App\Models\User;
use App\Models\WaterQuality;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $andi = User::create(['name' => 'Andi Rahman', 'email' => 'andi@aquakelola.test', 'password' => 'password', 'role' => 'pengelola']);
        User::create(['name' => 'Budi Santoso', 'email' => 'pemilik@aquakelola.test', 'password' => 'password', 'role' => 'mitra_pemilik']);

        $k1 = Pond::create(['name' => 'Kolam 01', 'fish_type' => 'Nila', 'status' => 'aktif']);
        $k2 = Pond::create(['name' => 'Kolam 02', 'fish_type' => 'Lele', 'status' => 'aktif']);
        $k3 = Pond::create(['name' => 'Kolam 03', 'fish_type' => 'Nila', 'status' => 'aktif']);

        $grower = FeedStock::create(['feed_type' => 'Pelet Grower', 'total_stock' => 120, 'minimum_stock' => 50, 'price_per_kg' => 10000]);
        $starter = FeedStock::create(['feed_type' => 'Pelet Starter', 'total_stock' => 25, 'minimum_stock' => 30, 'price_per_kg' => 12000]);

        // Riwayat pakan (pembelian + pemakaian)
        $m = now()->startOfMonth();
        $tx = fn ($feed, $type, $qty, $pond, $at, $note) => tap(FeedTransaction::create([
            'pond_id' => $pond?->id, 'feed_id' => $feed->id, 'type' => $type, 'quantity' => $qty,
            'notes' => $note, 'user_id' => $andi->id,
        ]), fn ($t) => $t->forceFill(['created_at' => $at, 'updated_at' => $at])->save());

        $in1 = $tx($grower, 'masuk', 200, null, $m->copy()->addDays(1)->setTime(9, 0), 'Pembelian pelet grower');
        $in2 = $tx($starter, 'masuk', 100, null, $m->copy()->addDays(8)->setTime(9, 0), 'Pembelian pelet starter');
        $in3 = $tx($grower, 'masuk', 100, null, $m->copy()->addDays(17)->setTime(9, 0), 'Pembelian pelet grower');
        $tx($starter, 'keluar', 5, $k2, now()->subDays(2)->setTime(16, 0), 'Pemberian pakan sore Kolam 02.');
        $tx($grower, 'keluar', 12, $k1, now()->subDay()->setTime(16, 0), 'Pemberian pakan sore Kolam 01.');

        // Keuangan
        $fin = fn ($type, $pond, $feed, $kg, $price, $date, $ftx = null) => FinancialTransaction::create([
            'pond_id' => $pond?->id, 'feed_id' => $feed?->id, 'feed_transaction_id' => $ftx?->id, 'type' => $type, 'weight_kg' => $kg,
            'price_per_kg' => $price, 'total_amount' => $kg * $price, 'transaction_date' => $date->toDateString(),
            'user_id' => $andi->id,
        ]);

        $fin('pembelian_pakan', null, $grower, 200, 10000, $m->copy()->addDays(1), $in1);
        $fin('pembelian_pakan', null, $starter, 100, 12000, $m->copy()->addDays(8), $in2);
        $fin('pembelian_pakan', null, $grower, 100, 10000, $m->copy()->addDays(17), $in3);
        $fin('pendapatan_panen', $k1, null, 180, 30000, $m->copy()->addDays(21));
        $fin('pendapatan_panen', $k3, null, 140, 30000, $m->copy()->addDays(27));

        // Kualitas air 7 hari terakhir (pukul 08.00)
        $data = [
            [$k1, [7.0, 7.2, 7.3, 7.1, 7.3, 7.2, 7.2], [27.8, 28.0, 28.2, 28.0, 28.1, 28.0, 28.0]],
            [$k2, [7.3, 7.5, 7.4, 7.6, 7.4, 7.3, 7.4], [28.4, 28.6, 28.5, 28.7, 28.3, 28.2, 28.5]],
            [$k3, [6.9, 7.0, 6.9, 7.1, 7.0, 7.1, 7.1], [27.5, 27.7, 27.6, 27.9, 27.8, 27.7, 27.8]],
        ];

        foreach ($data as [$pond, $phs, $temps]) {
            foreach ($phs as $i => $ph) {
                WaterQuality::create([
                    'pond_id' => $pond->id,
                    'ph_level' => $ph,
                    'temperature' => $temps[$i],
                    'status' => WaterQuality::resolveStatus($ph),
                    'measured_at' => now()->subDays(6 - $i)->setTime(8, 0),
                    'user_id' => $andi->id,
                ]);
            }
        }
    }
}
