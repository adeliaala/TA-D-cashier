<?php

namespace App\Livewire\Reports;

use Livewire\Component;
use Modules\Expense\Entities\Expense;
use Modules\Purchase\Entities\Purchase;
use Modules\Purchase\Entities\PurchasePayment;
use Modules\Sale\Entities\Sale;
use Modules\Sale\Entities\SaleDetails;
use Modules\Sale\Entities\SalePayment;
use Illuminate\Support\Facades\DB;

class ProfitLossReport extends Component
{

    public $month;
    public $year;
    public $total_sales, $sales_amount;
    public $total_purchases, $purchases_amount;
    public $expenses_amount;
    public $profit_amount;
    public $payments_received_amount;
    public $payments_sent_amount;
    public $payments_net_amount;
    public $cost_product_amount;

    protected $rules = [
        'month' => 'required|integer|min:1|max:12',
        'year' => 'required|integer|min:2020',
    ];

    protected $casts = [
        'month' => 'integer',
        'year' => 'integer',
    ];

    public function mount() {
        $this->month = '';
        $this->year = '';
        $this->total_sales = 0;
        $this->sales_amount = 0;
        $this->total_purchases = 0;
        $this->purchases_amount = 0;
        $this->payments_received_amount = 0;
        $this->payments_sent_amount = 0;
        $this->payments_net_amount = 0;
        $this->cost_product_amount = 0;
    }

    public function render() {
        $this->setValues();

        return view('livewire.reports.profit-loss-report');
    }

    public function generateReport() {
        $this->validate();
    }

    public function setValues() {
        $month = (int) $this->month;
        $year = (int) $this->year;
        $this->total_sales = Sale::when($month && $year, function ($query) use ($month, $year) {
                return $query->whereMonth('date', $month)->whereYear('date', $year);
            })
            ->count();

        $this->sales_amount = Sale::when($month && $year, function ($query) use ($month, $year) {
                return $query->whereMonth('date', $month)->whereYear('date', $year);
            })
            ->sum('total_amount');

        $this->total_purchases = Purchase::when($month && $year, function ($query) use ($month, $year) {
                return $query->whereMonth('date', $month)->whereYear('date', $year);
            })
            ->count();

        $this->purchases_amount = Purchase::when($month && $year, function ($query) use ($month, $year) {
                return $query->whereMonth('date', $month)->whereYear('date', $year);
            })
            ->sum('total') / 100;

        $this->expenses_amount = Expense::when($month && $year, function ($query) use ($month, $year) {
                return $query->whereMonth('date', $month)->whereYear('date', $year);
            })
            ->sum('amount') / 100;

        $this->cost_product_amount = SaleDetails::when($month && $year, function ($query) use ($month, $year) {
                return $query->whereMonth('created_at', $month)->whereYear('created_at', $year);
            })
            ->sum(DB::raw('unit_price * quantity'));

        $this->profit_amount = $this->calculateProfit();
        $this->payments_received_amount = $this->calculatePaymentsReceived();
        $this->payments_sent_amount = $this->calculatePaymentsSent();
        $this->payments_net_amount = $this->payments_received_amount - $this->payments_sent_amount;
    }

    public function calculateProfit()
    {
        $revenue = $this->sales_amount;
        
        return round($revenue - $this->cost_product_amount - $this->expenses_amount, 2);
    }
    
    
    
    

    public function calculatePaymentsReceived() {
        $month = $this->month;
        $year = $this->year;
        $sale_payments = SalePayment::when($month && $year, function ($query) use ($month, $year) {
                return $query->whereMonth('date', $month)->whereYear('date', $year);
            })
            ->sum('amount') / 100;
        return $sale_payments;
    }

    public function calculatePaymentsSent() {
        return $this->expenses_amount;
    }
}
