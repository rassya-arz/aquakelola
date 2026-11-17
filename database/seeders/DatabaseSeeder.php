<?php

namespace Database\Seeders;

use App\Models\FeedStock;
use App\Models\FeedTransaction;
use App\Models\FinancialTransaction;
use App\Models\Pond;
use App\Models\User;
use App\Models\WaterQuality;
use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Data contoh untuk demo: bulan lalu + bulan berjalan, tidak pernah melewati hari ini.
 * Stok dihitung dari transaksi (bukan angka manual) sehingga selalu sinkron dengan riwayat.
 *
 * Akun (kata sandi: password):
 *   pengelola@aquakelola.test  -> Pengelola (CRUD penuh)
 *   pemilik@aquakelola.test    -> Mitra / Pemilik (hanya lihat)
 */
class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    private Carbon $now;

    private User $pengelola;

    public function run(): void
    {
        $this->now = now();

        $this->pengelola = User::create([
            'name' => 'Andi Rahman', 'email' => 'pengelola@aquakelola.test',
            'password' => Hash::make('password'), 'role' => 'pengelola',
        ]);
        User::create([
            'name' => 'Budi Santoso', 'email' => 'pemilik@aquakelola.test',
            'password' => Hash::make('password'), 'role' => 'mitra_pemilik',
        ]);

        $k1 = Pond::create(['name' => 'Kolam 01', 'fish_type' => 'Nila', 'status' => Pond::STATUS_AKTIF]);
        $k2 = Pond::create(['name' => 'Kolam 02', 'fish_type' => 'Lele', 'status' => Pond::STATUS_AKTIF]);
        $k3 = Pond::create(['name' => 'Kolam 03', 'fish_type' => 'Nila', 'status' => Pond::STATUS_AKTIF]);

        $this->seedFeed($k1, $k2, $k3);
        $this->seedHarvests($k1, $k2, $k3);
        $this->seedWater([$k1, $k2, $k3]);
    }

    /** Pakan masuk/keluar. Stok awal 0, seluruh stok berasal dari transaksi. */
    private function seedFeed(Pond $k1, Pond $k2, Pond $k3): void
    {
        $grower = FeedStock::create(['feed_type' => 'Pelet Grower', 'total_stock' => 0, 'minimum_stock' => 50, 'price_per_kg' => 10000]);
        $starter = FeedStock::create(['feed_type' => 'Pelet Starter', 'total_stock' => 0, 'minimum_stock' => 30, 'price_per_kg' => 12000]);

        $in = FeedTransaction::TYPE_MASUK;
        $out = FeedTransaction::TYPE_KELUAR;

        $events = [
            // Bulan lalu
            [$this->lastMonth(3, 9), $grower, $in, 200, null, 10000, 'Pembelian pelet grower'],
            [$this->lastMonth(6, 9), $starter, $in, 100, null, 12000, 'Pembelian pelet starter'],
            [$this->lastMonth(10, 16), $grower, $out, 25, $k1, null, 'Pemberian pakan sore Kolam 01.'],
            [$this->lastMonth(10, 16), $starter, $out, 10, $k2, null, 'Pemberian pakan sore Kolam 02.'],
            [$this->lastMonth(15, 16), $grower, $out, 30, $k1, null, 'Pemberian pakan sore Kolam 01.'],
            [$this->lastMonth(15, 16), $grower, $out, 20, $k3, null, 'Pemberian pakan sore Kolam 03.'],
            [$this->lastMonth(20, 16), $starter, $out, 15, $k2, null, 'Pemberian pakan sore Kolam 02.'],
            [$this->lastMonth(24, 16), $grower, $out, 25, $k3, null, 'Pemberian pakan sore Kolam 03.'],
            [$this->lastMonth(26, 16), $starter, $out, 10, $k2, null, 'Pemberian pakan sore Kolam 02.'],
            // Bulan ini (posisi hari mengikuti tanggal hari ini)
            [$this->thisMonth(0.15, 9), $grower, $in, 150, null, 10500, 'Pembelian pelet grower'],
            [$this->thisMonth(0.30, 16), $grower, $out, 30, $k1, null, 'Pemberian pakan sore Kolam 01.'],
            [$this->thisMonth(0.30, 16), $starter, $out, 12, $k2, null, 'Pemberian pakan sore Kolam 02.'],
            [$this->thisMonth(0.55, 16), $grower, $out, 25, $k3, null, 'Pemberian pakan sore Kolam 03.'],
            [$this->thisMonth(0.55, 16), $starter, $out, 15, $k2, null, 'Pemberian pakan sore Kolam 02.'],
            [$this->thisMonth(0.80, 16), $grower, $out, 20, $k1, null, 'Pemberian pakan sore Kolam 01.'],
            [$this->thisMonth(0.80, 16), $starter, $out, 10, $k2, null, 'Pemberian pakan sore Kolam 02.'],
        ];

        // Proses berurutan menurut waktu (urutan penulisan menjadi pemutus bila waktunya sama).
        foreach ($events as $i => $e) {
            $events[$i]['seq'] = $i;
        }
        usort($events, fn ($a, $b) => [$a[0]->getTimestamp(), $a['seq']] <=> [$b[0]->getTimestamp(), $b['seq']]);

        foreach ($events as [$at, $feed, $type, $qty, $pond, $price, $note]) {
            $feed->total_stock = round($feed->total_stock + ($type === $in ? $qty : -$qty), 2);

            if ($feed->total_stock < 0) {
                throw new \RuntimeException("Seeder membuat stok {$feed->feed_type} menjadi minus.");
            }

            $tx = new FeedTransaction([
                'pond_id' => $pond?->id,
                'feed_id' => $feed->id,
                'type' => $type,
                'quantity' => $qty,
                'notes' => $note,
                'user_id' => $this->pengelola->id,
            ]);
            $tx->forceFill(['created_at' => $at, 'updated_at' => $at])->save();

            if ($type === $in) {
                $feed->price_per_kg = $price; // harga acuan mengikuti pembelian terakhir

                FinancialTransaction::create([
                    'pond_id' => null,
                    'feed_id' => $feed->id,
                    'feed_transaction_id' => $tx->id,
                    'type' => FinancialTransaction::TYPE_PEMBELIAN_PAKAN,
                    'weight_kg' => $qty,
                    'price_per_kg' => $price,
                    'total_amount' => round($qty * $price, 2),
                    'transaction_date' => $at->toDateString(),
                    'user_id' => $this->pengelola->id,
                ]);
            }
        }

        $grower->save();
        $starter->save();
    }

    /** Penjualan panen (pendapatan). */
    private function seedHarvests(Pond $k1, Pond $k2, Pond $k3): void
    {
        foreach ([
            [$k2, 120, 22000, $this->lastMonth(22, 10)],
            [$k1, 180, 30000, $this->lastMonth(27, 10)],
            [$k3, 140, 31000, $this->thisMonth(0.70, 10)],
        ] as [$pond, $kg, $price, $at]) {
            FinancialTransaction::create([
                'pond_id' => $pond->id,
                'type' => FinancialTransaction::TYPE_PENDAPATAN_PANEN,
                'weight_kg' => $kg,
                'price_per_kg' => $price,
                'total_amount' => round($kg * $price, 2),
                'transaction_date' => $at->toDateString(),
                'user_id' => $this->pengelola->id,
            ]);
        }
    }

    /**
     * Riwayat pH: jarang di bulan lalu, harian pada 7 hari terakhir.
     * Kolam 01 sempat di bawah batas (riwayat), Kolam 02 saat ini di atas batas (peringatan aktif).
     *
     * @param  array<int, Pond>  $ponds  [Kolam 01, Kolam 02, Kolam 03]
     */
    private function seedWater(array $ponds): void
    {
        // Indeks 0 = 6 hari lalu ... indeks 6 = hari ini.
        $recent = [
            [7.2, 7.1, 7.3, 6.3, 6.8, 7.0, 7.1],
            [7.4, 7.5, 7.3, 7.6, 7.9, 8.2, 8.7],
            [7.0, 7.1, 7.0, 7.2, 7.1, 7.3, 7.2],
        ];
        $sparse = [7.0, 7.2, 7.1, 7.4, 7.3, 7.0];

        $rows = []; // satu pengukuran per kolam per tanggal

        foreach ($ponds as $i => $pond) {
            foreach ([2, 7, 12, 17, 22, 27] as $j => $day) {
                $at = $this->lastMonth($day, 8);
                $rows[$pond->id.'|'.$at->toDateString()] = [$pond, $at, $sparse[($i + $j) % 6]];
            }

            foreach ($recent[$i] as $k => $ph) {
                $at = $this->capped($this->now->copy()->subDays(6 - $k), 8);
                $rows[$pond->id.'|'.$at->toDateString()] = [$pond, $at, $ph];
            }
        }

        foreach ($rows as [$pond, $at, $ph]) {
            WaterQuality::create([
                'pond_id' => $pond->id,
                'ph_level' => $ph,
                'status' => WaterQuality::resolveStatus($ph),
                'measured_at' => $at,
                'user_id' => $this->pengelola->id,
            ]);
        }
    }

    // ---------------------------------------------------------------- tanggal

    /** Tanggal di bulan lalu (maks. tanggal 27 agar valid di semua bulan). */
    private function lastMonth(int $day, int $hour): Carbon
    {
        return $this->capped($this->now->copy()->startOfMonth()->subMonthNoOverflow()->day($day), $hour);
    }

    /** Tanggal di bulan ini, proporsional terhadap hari ini sehingga tidak pernah melewati hari ini. */
    private function thisMonth(float $fraction, int $hour): Carbon
    {
        $day = max(1, (int) ceil($this->now->day * $fraction));

        return $this->capped($this->now->copy()->startOfMonth()->day($day), $hour);
    }

    /** Set jam; bila hasilnya lebih baru dari sekarang, mundurkan ke awal jam ini. */
    private function capped(Carbon $date, int $hour): Carbon
    {
        $date = $date->setTime($hour, 0);

        return $date->greaterThan($this->now) ? $this->now->copy()->startOfHour() : $date;
    }
}
