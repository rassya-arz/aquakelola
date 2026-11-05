<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Suhu udara dari prakiraan cuaca terbuka BMKG.
 * Data BMKG per 3 jam per kelurahan; di sini diinterpolasi ke jam sekarang
 * supaya angka berubah halus, bukan loncat tiap 3 jam.
 * Wajib mencantumkan BMKG sebagai sumber data di tampilan.
 */
class BmkgWeather
{
    private const URL = 'https://api.bmkg.go.id/publik/prakiraan-cuaca';

    public function current(): ?array
    {
        $code = config('services.bmkg.adm4');
        if (! $code) {
            return null;
        }

        $raw = Cache::get("bmkg:raw:$code");

        if ($raw === null) {
            $raw = $this->fetch($code);

            if ($raw !== null) {
                Cache::put("bmkg:raw:$code", $raw, now()->addMinutes(30));   // segar
                Cache::put("bmkg:stale:$code", $raw, now()->addDays(2));      // cadangan
            } else {
                $raw = Cache::get("bmkg:stale:$code");                        // BMKG gangguan -> pakai data terakhir
            }
        }

        return $raw ? $this->interpolate($raw) : null;
    }

    /** Ambil & ratakan semua slot waktu (3 hari) dari respons BMKG. */
    private function fetch(string $code): ?array
    {
        try {
            $res = Http::timeout(8)->acceptJson()->get(self::URL, ['adm4' => $code]);

            if (! $res->successful()) {
                return null;
            }

            $json = $res->json();
            $lokasi = $json['lokasi'] ?? [];
            $slots = collect($json['data'][0]['cuaca'] ?? [])->flatten(1)
                ->map(fn ($s) => [
                    'ts' => Carbon::parse($s['utc_datetime'] ?? $s['datetime'], 'UTC')->timestamp,
                    't' => (float) $s['t'],
                    'hu' => isset($s['hu']) ? (float) $s['hu'] : null,
                    'desc' => $s['weather_desc'] ?? null,
                    'image' => isset($s['image']) ? str_replace(' ', '%20', $s['image']) : null,
                ])
                ->sortBy('ts')->values()->all();

            if (! $slots) {
                return null;
            }

            return [
                'slots' => $slots,
                'place' => trim(($lokasi['desa'] ?? '').', '.($lokasi['kotkab'] ?? ''), ', '),
            ];
        } catch (\Throwable $e) {
            Log::warning('BMKG gagal diambil: '.$e->getMessage());

            return null;
        }
    }

    /** Suhu pada jam sekarang = interpolasi linear antara slot sebelum & sesudah. */
    private function interpolate(array $raw): ?array
    {
        $now = now('UTC')->timestamp;
        $prev = $next = null;

        foreach ($raw['slots'] as $s) {
            if ($s['ts'] <= $now) {
                $prev = $s;
            } elseif ($next === null) {
                $next = $s;
            }
        }

        $a = $prev ?? $next;
        $b = $next ?? $prev;
        if (! $a) {
            return null;
        }

        $f = ($a['ts'] === $b['ts']) ? 0 : ($now - $a['ts']) / ($b['ts'] - $a['ts']);
        $f = max(0, min(1, $f));
        $lerp = fn ($x, $y) => $x === null || $y === null ? $x : $x + ($y - $x) * $f;

        $near = $f < 0.5 ? $a : $b;   // deskripsi/ikon cuaca dari slot terdekat

        return [
            'temperature' => round($lerp($a['t'], $b['t']), 1),
            'humidity' => ($h = $lerp($a['hu'], $b['hu'])) === null ? null : round($h),
            'desc' => $near['desc'],
            'image' => $near['image'],
            'place' => $raw['place'],
            'source' => 'BMKG',
        ];
    }
}
