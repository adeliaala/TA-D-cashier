<?php

namespace App\Livewire\Reports;

use Livewire\Component;
use Livewire\WithPagination;
use Modules\Sale\Entities\Sale;

class SalesReport extends Component
{

    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public $customers;
    public $month;
    public $year;
    public $customer_id;
    public $sale_status;
    public $payment_status;
    public $customer_type;

    protected $rules = [
        'month' => 'required|integer|between:1,12',
        'year'  => 'required|integer|min:2020',
    ];

    public function mount($customers) {
        $this->customers = $customers;
        $this->month = now()->month;
        $this->year = now()->year;
        $this->customer_id = '';
        $this->sale_status = '';
        $this->payment_status = '';
        $this->customer_type = '';
    }

    public function render() {
        $sales = Sale::whereYear('date', $this->year)
            ->whereMonth('date', $this->month)
            ->when($this->customer_id, function ($query) {
                return $query->where('customer_id', $this->customer_id);
            })
            ->when($this->customer_type, function ($query) {
                if ($this->customer_type === 'walk_in') {
                    return $query->where('customer_id', 1);
                } elseif ($this->customer_type === 'member') {
                    return $query->where('customer_id', '!=', 1);
                }
                return $query;
            })
            ->when($this->sale_status, function ($query) {
                return $query->where('status', $this->sale_status);
            })
            ->when($this->payment_status, function ($query) {
                return $query->where('payment_status', $this->payment_status);
            })
            ->orderBy('date', 'desc')->paginate(10);

        return view('livewire.reports.sales-report', [
            'sales' => $sales
        ]);
    }

    public function generateReport() {
        $this->validate();
        $this->render();
    }
}
