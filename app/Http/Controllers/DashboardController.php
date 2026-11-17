<?php

namespace App\Http\Controllers;

use App\Models\FeedStock;
use App\Models\FeedTransaction;
use App\Models\FinancialTransaction;
use App\Models\Pond;
use App\Models\WaterQuality;
use App\Support\Fmt;
use App\Support\Period;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $month = Period::month($request);
        $stocks = FeedStock::orderBy('feed_type')->get();

        return view('dashboard', [
            'month' => $month,
            'stocks' => $stocks,
            'totalStock' => $stocks->sum('total_stock'),
            'lowStocks' => $stocks->filter->isLow()->values(),
            ...FinancialTransaction::monthSummary($month),
            'trend' => WaterQuality::trend(7),
            'ponds' => Pond::with('latestWater')->orderBy('name')->get(),
            'activities' => $this->activities(),
        ]);
    }

    private function activities()
    {
        $items = collect();

        foreach (FeedTransaction::with(['pond', 'feed', 'user'])->latest()->take(4)->get() as $t) {
            $items->push([
                'icon' => 'box',
                'title' => ($t->isKeluar() ? 'Pemakaian ' : 'Pakan masuk ').$t->feed->feed_type.' • '.Fmt::num($t->quantity).' kg',
                'sub' => ($t->pond ? $t->pond->name : 'Gudang').' • Dicatat '.$t->user->name,
                'at' => $t->created_at,
            ]);
        }

        foreach (WaterQuality::with(['pond', 'user'])->latest('measured_at')->take(4)->get() as $w) {
            $items->push([
                'icon' => 'drop',
                'title' => 'Pengecekan air '.$w->pond->name.' • pH '.Fmt::num($w->ph_level),
                'sub' => $w->isOutOfRange() ? 'pH di luar batas' : 'pH dalam batas',
                'at' => $w->measured_at,
            ]);
        }

        foreach (FinancialTransaction::with('pond')->where('type', FinancialTransaction::TYPE_PENDAPATAN_PANEN)->latest('transaction_date')->latest('id')->take(3)->get() as $f) {
            $items->push([
                'icon' => 'chart',
                'title' => 'Penjualan panen • '.Fmt::rp($f->total_amount),
                'sub' => ($f->pond?->name ?? '-').' • '.Fmt::num($f->weight_kg).' kg '.strtolower($f->pond?->fish_type ?? 'ikan'),
                'at' => $f->transaction_date->copy()->setTime(10, 0),
            ]);
        }

        return $items->sortByDesc('at')->take(5)->values();
    }
}
