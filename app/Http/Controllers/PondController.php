<?php

namespace App\Http\Controllers;

use App\Models\Pond;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PondController extends Controller
{
    public function index()
    {
        return view('ponds.index', [
            'ponds' => Pond::withCycleCounts()->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $pond = Pond::create($this->validated($request));

        return redirect()->route('ponds.index')->with('success', "{$pond->name} ditambahkan.");
    }

    public function edit(Pond $pond)
    {
        return view('ponds.edit', ['pond' => $pond]);
    }

    public function update(Request $request, Pond $pond)
    {
        $previous = $pond->status;
        $pond->update($this->validated($request, $pond));

        $note = ! $pond->wasChanged('status') ? '' : match ($pond->status) {
            Pond::STATUS_PANEN => ' Status Panen: catatan terkait dikosongkan, monitoring pH dan pemberian pakan dinonaktifkan.',
            Pond::STATUS_PERBAIKAN => ' Status Perbaikan: monitoring pH dan pemberian pakan dinonaktifkan sementara, siklus berjalan tidak berubah.',
            default => $previous === Pond::STATUS_PANEN
                ? ' Siklus baru dimulai, monitoring pH dan pemberian pakan aktif kembali.'
                : ' Kolam aktif kembali, monitoring pH dan pemberian pakan dilanjutkan.',
        };

        return redirect()->route('ponds.index')->with('success', "{$pond->name} diperbarui.{$note}");
    }

    public function destroy(Pond $pond)
    {
        if ($pond->waterQualities()->exists() || $pond->feedTransactions()->exists() || $pond->financialTransactions()->exists()) {
            return redirect()->route('ponds.index')->with('alert', "{$pond->name} sudah memiliki data pengukuran/transaksi sehingga tidak dapat dihapus. Ubah statusnya menjadi Panen atau Perbaikan bila kolam tidak dipakai.");
        }

        $pond->delete();

        return redirect()->route('ponds.index')->with('success', "{$pond->name} dihapus.");
    }

    private function validated(Request $request, ?Pond $pond = null): array
    {
        $data = $request->validate($this->rules($pond), $this->messages());

        // Rapikan jenis ikan ("  nila   merah " -> "Nila Merah") agar saran tidak berduplikat.
        $data['fish_type'] = Str::of($data['fish_type'])->squish()->title()->toString();

        return $data;
    }

    private function rules(?Pond $pond = null): array
    {
        return [
            'name' => ['required', 'string', 'max:100', Rule::unique('ponds', 'name')->ignore($pond?->id)],
            'fish_type' => ['required', 'string', 'max:50'],
            'status' => ['required', Rule::in(array_keys(Pond::STATUS_LABELS))],
        ];
    }

    private function messages(): array
    {
        return [
            'name.required' => 'Nama kolam wajib diisi.',
            'name.unique' => 'Nama kolam sudah dipakai.',
            'fish_type.required' => 'Jenis ikan wajib diisi.',
            'fish_type.max' => 'Jenis ikan maksimal 50 karakter.',
            'status.in' => 'Status kolam harus Aktif, Panen, atau Perbaikan.',
        ];
    }
}
