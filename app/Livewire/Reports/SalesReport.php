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
        $this->month ;
        $this->year ;
        $this->customer_id = '';
        $this->sale_status = '';
        $this->payment_status = '';
        $this->customer_type = '';
    }

    public function render() {
        $query = Sale::query();
    
        if ($this->year) {
            $query->whereYear('date', $this->year);
        }
    
        if ($this->month) {
            $query->whereMonth('date', $this->month);
        }
    
        $query->when($this->customer_id, function ($query) {
            return $query->where('customer_id', $this->customer_id);
        });
    
        $query->when($this->customer_type, function ($query) {
            if ($this->customer_type === 'walk_in') {
                return $query->where('customer_id', 1);
            } elseif ($this->customer_type === 'member') {
                return $query->where('customer_id', '!=', 1);
            }
        });
    
        $query->when($this->sale_status, function ($query) {
            return $query->where('status', $this->sale_status);
        });
    
        $query->when($this->payment_status, function ($query) {
            return $query->where('payment_status', $this->payment_status);
        });
    
        $sales = $query->orderBy('date', 'desc')->paginate(10);
    
        return view('livewire.reports.sales-report', [
            'sales' => $sales
        ]);
    }
    

    public function generateReport() {
        $this->validate();
        $this->render();
    }
}
