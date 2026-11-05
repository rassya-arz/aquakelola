<?php

namespace App\Support;

class Fmt
{
    /** 120.00 -> "120", 10.5 -> "10,5" */
    public static function num($value): string
    {
        return rtrim(rtrim(number_format((float) $value, 2, ',', '.'), '0'), ',');
    }

    /** 9600000 -> "Rp9.600.000" */
    public static function rp($value): string
    {
        return 'Rp'.number_format((float) $value, 0, ',', '.');
    }
}
