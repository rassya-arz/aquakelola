<?php

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Http\Request;

class Period
{
    /** Ambil bulan dari ?month=YYYY-MM, default bulan berjalan. */
    public static function month(Request $request): Carbon
    {
        $m = $request->query('month');

        if (is_string($m) && preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $m)) {
            return Carbon::createFromFormat('Y-m-d', $m.'-01')->startOfDay();
        }

        return now()->startOfMonth();
    }
}
