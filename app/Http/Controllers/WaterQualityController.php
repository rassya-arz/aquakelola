<?php

namespace App\Http\Controllers;

use App\Models\Pond;
use App\Models\WaterQuality;
use App\Support\Fmt;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class WaterQualityController extends Controller
{
    public function index()
    {
        $ponds = Pond::with('latestWater')->orderBy('name')->get();
        $activePonds = $ponds->filter->isAktif()->values();

        // Peringatan aktif: kolam aktif yang pengukuran terakhirnya masih di luar batas.
        $alerts = $activePonds->filter(fn ($p) => $p->latestWater?->isOutOfRange())->values();

        return view('water.index', [
            'ponds' => $ponds,
            'activePonds' => $activePonds,
            'alerts' => $alerts,
            'history' => WaterQuality::with(['pond', 'user'])->latest('measured_at')->paginate(10),
            'trend' => WaterQuality::trend(7),
            'minPh' => WaterQuality::MIN_PH,
            'maxPh' => WaterQuality::MAX_PH,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'pond_id' => ['required', Rule::exists('ponds', 'id')->where('status', Pond::STATUS_AKTIF)],
            'ph_level' => ['required', 'numeric', 'between:0,14'],
            'measured_at' => ['nullable', 'date', 'before_or_equal:now'],
        ], [
            'pond_id.required' => 'Pilih kolam yang diukur.',
            'pond_id.exists' => 'Kolam berstatus panen. Ubah status kolam menjadi Aktif di Data Kolam sebelum monitoring pH.',
            'ph_level.required' => 'Nilai pH wajib diisi.',
            'ph_level.numeric' => 'Nilai pH harus berupa angka.',
            'ph_level.between' => 'Nilai pH harus antara 0 dan 14.',
            'measured_at.before_or_equal' => 'Waktu pengukuran tidak boleh di masa depan.',
        ]);

        $ph = round((float) $data['ph_level'], 2);

        $record = WaterQuality::create([
            'pond_id' => $data['pond_id'],
            'ph_level' => $ph,
            'status' => WaterQuality::resolveStatus($ph),
            'measured_at' => $data['measured_at'] ?? now(),
            'user_id' => $request->user()->id,
        ]);

        $range = Fmt::num(WaterQuality::MIN_PH).'–'.Fmt::num(WaterQuality::MAX_PH);

        return redirect()->route('water.index')->with(
            $record->isOutOfRange() ? 'alert' : 'success',
            $record->isOutOfRange()
                ? 'Peringatan: pH '.Fmt::num($ph)." di luar batas {$range}. Pengukuran tersimpan."
                : 'Pengukuran pH '.Fmt::num($ph).' tersimpan.'
        );
    }

    public function destroy(WaterQuality $waterQuality)
    {
        $waterQuality->delete();

        return redirect()->route('water.index')->with('success', 'Pengukuran dihapus.');
    }
}
