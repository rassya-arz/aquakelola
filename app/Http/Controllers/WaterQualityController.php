<?php

namespace App\Http\Controllers;

use App\Models\Pond;
use App\Models\PendingWaterQualityReading;
use App\Models\User;
use App\Models\WaterQuality;
use App\Support\Fmt;
use Illuminate\Http\Request;

class WaterQualityController extends Controller
{
    public function index()
    {
        $ponds = Pond::with('latestWater')->orderBy('name')->get();

        // Peringatan aktif: pembacaan terakhir kolam masih di luar batas.
        $alerts = $ponds->filter(fn ($p) => $p->latestWater && $p->latestWater->isOutOfRange())->values();

        return view('water.index', [
            'ponds' => $ponds,
            'alerts' => $alerts,
            'pics' => User::where('role', 'pengelola')->orderBy('name')->get(),
            'history' => WaterQuality::with(['pond', 'user', 'actionPic'])->latest('measured_at')->paginate(10),
            'trend' => WaterQuality::trend(7),
            'minPh' => WaterQuality::MIN_PH,
            'maxPh' => WaterQuality::MAX_PH,
        ]);
    }

    
public function store(Request $request)
{
    $data = $request->validate([
        'pond_id' => ['required', 'exists:ponds,id'],
        'ph_level' => ['required', 'numeric', 'between:0,14'],
        'temperature' => ['required', 'numeric', 'between:0,50'],
        'measured_at' => ['nullable', 'date', 'before_or_equal:now'],
    ], [
        'pond_id.required' => 'Pilih kolam yang diukur.',
        'ph_level.required' => 'Nilai pH wajib diisi.',
        'ph_level.between' => 'Nilai pH harus antara 0 dan 14.',
        'temperature.required' => 'Suhu air wajib diisi.',
        'temperature.between' => 'Suhu air harus antara 0 dan 50 °C.',
        'measured_at.before_or_equal' =>
            'Waktu pengukuran tidak boleh di masa depan.',
    ]);

    $ph = (float) $data['ph_level'];
    $measuredAt = isset($data['measured_at'])
        ? \Illuminate\Support\Carbon::parse($data['measured_at'])
        : now();

    $pendingQuery = PendingWaterQualityReading::query()
        ->where('pond_id', $data['pond_id'])
        ->where('status', 'menunggu');

    if (WaterQuality::resolveStatus($ph) === 'di_luar_batas') {
        $pendingQuery->update([
            'status' => 'membaik',
        ]);

        PendingWaterQualityReading::create([
            'pond_id' => $data['pond_id'],
            'ph_level' => $ph,
            'temperature' => (float) $data['temperature'],
            'measured_at' => $measuredAt,
            'expires_at' => $measuredAt->copy()->addHours(24),
            'user_id' => $request->user()->id,
            'status' => 'menunggu',
        ]);

        return redirect()->route('water.index')->with(
            'alert',
            "Peringatan: pH {$ph} di luar batas. Pengukuran disimpan sebagai pending selama 24 jam."
        );
    }

    $pendingQuery->update([
        'status' => 'membaik',
    ]);

    $record = WaterQuality::create([
        'pond_id' => $data['pond_id'],
        'ph_level' => $ph,
        'temperature' => (float) $data['temperature'],
        'status' => WaterQuality::resolveStatus($ph),
        'measured_at' => $measuredAt,
        'user_id' => $request->user()->id,
    ]);

    return redirect()->route('water.index')->with(
        'success',
        "Pengukuran pH {$record->ph_level} tersimpan dalam riwayat resmi."
    );
}


    public function edit(WaterQuality $waterQuality)
    {
        return view('water.edit', [
            'record' => $waterQuality,
            'ponds' => Pond::orderBy('name')->get(),
            'minPh' => WaterQuality::MIN_PH,
            'maxPh' => WaterQuality::MAX_PH,
        ]);
    }

    /** UPDATE pengukuran: status dihitung ulang; catatan tindakan dibersihkan bila pH kini dalam batas. */
    public function update(Request $request, WaterQuality $waterQuality)
    {
        $data = $request->validate([
            'pond_id' => ['required', 'exists:ponds,id'],
            'ph_level' => ['required', 'numeric', 'between:0,14'],
            'temperature' => ['required', 'numeric', 'between:0,50'],
            'measured_at' => ['required', 'date', 'before_or_equal:now'],
        ], [
            'ph_level.between' => 'Nilai pH harus antara 0 dan 14.',
            'temperature.between' => 'Suhu air harus antara 0 dan 50 °C.',
            'measured_at.before_or_equal' => 'Waktu pengukuran tidak boleh di masa depan.',
        ]);

        $status = WaterQuality::resolveStatus((float) $data['ph_level']);

        $payload = $data + ['status' => $status];
        if ($status === 'dalam_batas') {
            $payload += ['corrective_action' => null, 'action_status' => null, 'action_pic_id' => null, 'recheck_at' => null, 'action_recorded_at' => null];
        }

        $waterQuality->update($payload);

        return redirect()->route('water.index')->with('success', 'Pengukuran diperbarui. Status: '.($status === 'dalam_batas' ? 'dalam batas.' : 'di luar batas.'));
    }

    public function destroy(WaterQuality $waterQuality)
    {
        $waterQuality->delete();

        return redirect()->route('water.index')->with('success', 'Pengukuran dihapus.');
    }

    /** Catat / perbarui tindakan korektif pada pengukuran di luar batas. */
    public function updateAction(Request $request, WaterQuality $waterQuality)
    {
        abort_unless($waterQuality->isOutOfRange(), 422, 'Tindakan korektif hanya untuk pengukuran di luar batas.');

        $data = $request->validate([
            'corrective_action' => ['required', 'string', 'max:1000'],
            'action_pic_id' => ['required', 'exists:users,id'],
            'recheck_at' => ['nullable', 'date', 'after_or_equal:'.$waterQuality->measured_at->toDateTimeString()],
            'action_status' => ['required', 'in:dalam_proses,selesai'],
        ], [
            'corrective_action.required' => 'Tindakan yang dilakukan wajib diisi.',
            'action_pic_id.required' => 'Pilih penanggung jawab.',
            'recheck_at.after_or_equal' => 'Jadwal cek ulang tidak boleh sebelum waktu pengukuran.',
        ]);

        $waterQuality->update($data + [
            'action_recorded_at' => $waterQuality->action_recorded_at ?? now(),
        ]);

        return redirect()->route('water.index')->with('success', "Catatan tindakan {$waterQuality->actionCode()} diperbarui. Peringatan baru hilang setelah pengukuran ulang berada dalam batas.");
    }
}
