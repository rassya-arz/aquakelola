<?php

namespace App\Http\Controllers;

use App\Services\BmkgWeather;
use Illuminate\Http\JsonResponse;

class WeatherController extends Controller
{
    /** GET /pengecekan-ph/cuaca -> suhu udara terkini dari BMKG. */
    public function current(BmkgWeather $bmkg): JsonResponse
    {
        $data = $bmkg->current();

        return $data
            ? response()->json($data)
            : response()->json(['message' => 'Data cuaca BMKG belum tersedia.'], 503);
    }
}
