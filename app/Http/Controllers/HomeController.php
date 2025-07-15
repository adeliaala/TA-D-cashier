<?php

namespace App\Http\Controllers;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\Sale\Entities\Sale;
use App\Models\ProductBatch;

class HomeController extends Controller
{
    public function index()
    {
        $branch_id = session('branch_id');

        // Ambil filter bulan dan tahun dari query string, default: bulan dan tahun sekarang
        $bulan = request('filterBulan') ?? now()->format('m');
        $tahun = request('filterTahun') ?? now()->format('Y');

        $start = Carbon::createFromDate($tahun, $bulan)->startOfMonth();
        $end = Carbon::createFromDate($tahun, $bulan)->endOfMonth();

        // Pendapatan bulan ini
        $monthlyRevenue = Sale::whereBetween('date', [$start, $end])
            ->where('branch_id', $branch_id)
            ->sum('total_amount') ;

        // Pendapatan hari ini
        $todayRevenue = Sale::whereDate('date', now())
            ->where('branch_id', $branch_id)
            ->sum('total_amount') ;

        // Jumlah pembeli hari ini
        $todayCustomers = Sale::whereDate('date', now())
            ->where('branch_id', $branch_id)
            ->count();
        
        // Jumlah Pembeli bulan ini
        $monthCustomers = Sale::whereBetween('date', [$start, $end])
            ->where('branch_id', $branch_id)
            ->count();

        // Profit bulan ini
        $profit = Sale::whereBetween('date', [$start, $end])
            ->where('branch_id', $branch_id)
            ->sum(DB::raw('total_amount - paid_amount')) / 100;

        // Chart pendapatan harian berdasarkan range bulan & tahun
        $dailyRevenueChart = $this->getDailyRevenueChart($branch_id, $start, $end);

        // Chart penjualan per kategori
        $categorySalesChart = $this->getCategorySalesChart($branch_id, $bulan, $tahun);

        // Produk yang akan kadaluarsa (30 hari ke depan dari sekarang)
        $expiringBatches = ProductBatch::with(['product', 'branch'])
            ->where('branch_id', $branch_id)
            ->whereDate('exp_date', '<=', now()->addDays(30))
            ->orderBy('exp_date')
            ->get();

        return view('home', compact(
            'monthlyRevenue',
            'todayRevenue',
            'todayCustomers',
            'monthCustomers',
            'profit',
            'dailyRevenueChart',
            'categorySalesChart',
            'expiringBatches'
        ));
    }

    private function getDailyRevenueChart($branchId, $start, $end)
    {
        $labels = [];
        $data = [];

        $current = $start->copy();
        while ($current->lte($end)) {
            $labels[] = $current->format('d M');

            $revenue = Sale::whereDate('date', $current)
                ->where('branch_id', $branchId)
                ->sum('total_amount');

            $data[] = $revenue;
            $current->addDay();
        }

        return [
            'labels' => $labels,
            'data' => $data
        ];
    }

    private function getCategorySalesChart($branchId, $bulan, $tahun)
    {
        $categorySales = DB::table('sales')
            ->join('sale_details', 'sales.id', '=', 'sale_details.sale_id')
            ->join('products', 'sale_details.product_id', '=', 'products.id')
            ->join('categories', 'products.category_id', '=', 'categories.id')
            ->where('sales.branch_id', $branchId)
            ->whereMonth('sales.date', $bulan)
            ->whereYear('sales.date', $tahun)
            ->select(
                'categories.category_name',
                DB::raw('SUM(sale_details.quantity) as total_quantity')
            )
            ->groupBy('categories.category_name')
            ->orderByDesc('total_quantity')
            ->get();

        $labels = [];
        $data = [];
        $total = $categorySales->sum('total_quantity');

        foreach ($categorySales as $item) {
            $labels[] = $item->category_name;
            $data[] = $total > 0 ? round(($item->total_quantity / $total) * 100, 1) : 0;
        }

        return [
            'labels' => $labels ?: ['Tidak ada data'],
            'data' => $data ?: [100]
        ];
    }
}
