<?php

namespace App\Http\Controllers;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\Expense\Entities\Expense;
use Modules\Purchase\Entities\Purchase;
use Modules\Purchase\Entities\PurchasePayment;
use Modules\PurchasesReturn\Entities\PurchaseReturn;
use Modules\PurchasesReturn\Entities\PurchaseReturnPayment;
use Modules\Sale\Entities\Sale;
use Modules\Sale\Entities\SalePayment;

class HomeController extends Controller
{

    public function index() {
        // Cek session dulu
        $activeBranchId = session('active_branch_id');
        
        // Jika tidak ada di session, coba ambil dari user
        if (!$activeBranchId) {
            $user = auth()->user();
            if ($user && $user->active_branch) {
                $activeBranchId = $user->active_branch->id;
            } else {
                // Default ke branch ID 1 jika tidak ada
                $activeBranchId = 1;
                session(['active_branch_id' => $activeBranchId]);
            }
        }

        $sales = Sale::where('payment_status', 'Completed')
            ->where('branch_id', $activeBranchId)
            ->sum('total_amount');
            
        $purchase_returns = PurchaseReturn::where('payment_status', 'Completed')
            ->where('branch_id', $activeBranchId)
            ->sum('total_amount');
            
        $product_costs = 0;

        foreach (Sale::where('payment_status', 'Completed')
            ->where('branch_id', $activeBranchId)
            ->with('saleDetails')->get() as $sale) {
            foreach ($sale->saleDetails as $saleDetail) {
                if (!is_null($saleDetail->product)) {
                    $product_costs += $saleDetail->product->product_cost * $saleDetail->quantity;
                }
            }
        }

        $revenue = $sales / 100;
        $profit = $revenue - $product_costs;

        return view('home', [
            'revenue'          => $revenue,
            'purchase_returns' => $purchase_returns / 100,
            'profit'           => $profit
        ]);
    }


    public function currentMonthChart() {
        abort_if(!request()->ajax(), 404);

        $activeBranchId = session('active_branch_id') ?? 1;

        $currentMonthSales = Sale::where('payment_status', 'Completed')
            ->where('branch_id', $activeBranchId)
            ->whereMonth('date', date('m'))
            ->whereYear('date', date('Y'))
            ->sum('total_amount') / 100;
            
        $currentMonthPurchases = Purchase::where('payment_status', 'Completed')
            ->where('branch_id', $activeBranchId)
            ->whereMonth('date', date('m'))
            ->whereYear('date', date('Y'))
            ->sum('total_amount') / 100;
            
        $currentMonthExpenses = Expense::where('branch_id', $activeBranchId)
            ->whereMonth('date', date('m'))
            ->whereYear('date', date('Y'))
            ->sum('amount') / 100;

        return response()->json([
            'sales'     => $currentMonthSales,
            'purchases' => $currentMonthPurchases,
            'expenses'  => $currentMonthExpenses
        ]);
    }


    public function salesPurchasesChart() {
        abort_if(!request()->ajax(), 404);

        $activeBranchId = session('active_branch_id') ?? 1;

        $sales = $this->salesChartData();
        $purchases = $this->purchasesChartData();

        return response()->json(['sales' => $sales, 'purchases' => $purchases]);
    }


    public function paymentChart() {
        abort_if(!request()->ajax(), 404);

        $activeBranchId = session('active_branch_id') ?? 1;

        $dates = collect();
        foreach (range(-11, 0) as $i) {
            $date = Carbon::now()->addMonths($i)->format('m-Y');
            $dates->put($date, 0);
        }

        $date_range = Carbon::today()->subYear()->format('Y-m-d');

        $sale_payments = SalePayment::where('date', '>=', $date_range)
            ->where('branch_id', $activeBranchId)
            ->select([
                DB::raw("DATE_FORMAT(date, '%m-%Y') as month"),
                DB::raw("SUM(amount) as amount")
            ])
            ->groupBy('month')->orderBy('month')
            ->get()->pluck('amount', 'month');

        $purchase_payments = PurchasePayment::where('date', '>=', $date_range)
            ->where('branch_id', $activeBranchId)
            ->select([
                DB::raw("DATE_FORMAT(date, '%m-%Y') as month"),
                DB::raw("SUM(amount) as amount")
            ])
            ->groupBy('month')->orderBy('month')
            ->get()->pluck('amount', 'month');

        $purchase_return_payments = PurchaseReturnPayment::where('date', '>=', $date_range)
            ->where('branch_id', $activeBranchId)
            ->select([
                DB::raw("DATE_FORMAT(date, '%m-%Y') as month"),
                DB::raw("SUM(amount) as amount")
            ])
            ->groupBy('month')->orderBy('month')
            ->get()->pluck('amount', 'month');

        $expenses = Expense::where('date', '>=', $date_range)
            ->where('branch_id', $activeBranchId)
            ->select([
                DB::raw("DATE_FORMAT(date, '%m-%Y') as month"),
                DB::raw("SUM(amount) as amount")
            ])
            ->groupBy('month')->orderBy('month')
            ->get()->pluck('amount', 'month');

        $payment_received = $sale_payments;
        $payment_sent = array_merge_numeric_values($purchase_payments, $expenses);

        $dates_received = $dates->merge($payment_received);
        $dates_sent = $dates->merge($payment_sent);

        $received_payments = [];
        $sent_payments = [];
        $months = [];

        foreach ($dates_received as $key => $value) {
            $received_payments[] = $value;
            $months[] = $key;
        }

        foreach ($dates_sent as $key => $value) {
            $sent_payments[] = $value;
        }

        return response()->json([
            'payment_sent' => $sent_payments,
            'payment_received' => $received_payments,
            'months' => $months,
        ]);
    }

    public function salesChartData() {
        $activeBranchId = session('active_branch_id') ?? 1;

        $dates = collect();
        foreach (range(-6, 0) as $i) {
            $date = Carbon::now()->addDays($i)->format('d-m-y');
            $dates->put($date, 0);
        }

        $date_range = Carbon::today()->subDays(6);

        $sales = Sale::where('payment_status', 'Completed')
            ->where('branch_id', $activeBranchId)
            ->where('date', '>=', $date_range)
            ->groupBy(DB::raw("DATE_FORMAT(date,'%d-%m-%y')"))
            ->orderBy('date')
            ->get([
                DB::raw(DB::raw("DATE_FORMAT(date,'%d-%m-%y') as date")),
                DB::raw('SUM(total_amount) AS count'),
            ])
            ->pluck('count', 'date');

        $dates = $dates->merge($sales);

        $data = [];
        $days = [];
        foreach ($dates as $key => $value) {
            $data[] = $value / 100;
            $days[] = $key;
        }

        return response()->json(['data' => $data, 'days' => $days]);
    }


    public function purchasesChartData() {
        $activeBranchId = session('active_branch_id') ?? 1;

        $dates = collect();
        foreach (range(-6, 0) as $i) {
            $date = Carbon::now()->addDays($i)->format('d-m-y');
            $dates->put($date, 0);
        }

        $date_range = Carbon::today()->subDays(6);

        $purchases = Purchase::where('payment_status', 'Completed')
            ->where('branch_id', $activeBranchId)
            ->where('date', '>=', $date_range)
            ->groupBy(DB::raw("DATE_FORMAT(date,'%d-%m-%y')"))
            ->orderBy('date')
            ->get([
                DB::raw(DB::raw("DATE_FORMAT(date,'%d-%m-%y') as date")),
                DB::raw('SUM(total_amount) AS count'),
            ])
            ->pluck('count', 'date');

        $dates = $dates->merge($purchases);

        $data = [];
        $days = [];
        foreach ($dates as $key => $value) {
            $data[] = $value / 100;
            $days[] = $key;
        }

        return response()->json(['data' => $data, 'days' => $days]);

    }
}
