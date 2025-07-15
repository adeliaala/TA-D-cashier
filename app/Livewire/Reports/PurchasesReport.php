<?php

namespace App\Livewire\Reports;

use Livewire\Component;
use Livewire\WithPagination;
use Modules\Purchase\Entities\Purchase;

class PurchasesReport extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public $suppliers;
    public $month;
    public $year;
    public $supplier_id;
    public $purchase_status;
    public $payment_status;

    protected $rules = [
        'month' => 'required|integer|between:1,12',
        'year'  => 'required|integer|min:2020',
    ];

    public function mount($suppliers)
    {
        $this->suppliers = $suppliers;
        $this->month = now()->month;
        $this->year = now()->year;
        $this->supplier_id = '';
        $this->purchase_status = '';
        $this->payment_status = '';
    }

    public function generateReport()
    {
        $this->validate();
        // Livewire akan otomatis re-render
    }

    public function render()
    {
        $purchases = Purchase::query()
            ->when($this->month && $this->year, function ($query) {
                return $query->whereYear('date', $this->year)
                             ->whereMonth('date', $this->month);
            })
            ->when($this->supplier_id, fn($query) => $query->where('supplier_id', $this->supplier_id))
            ->when($this->purchase_status, fn($query) => $query->where('status', $this->purchase_status))
            ->when($this->payment_status, fn($query) => $query->where('payment_status', $this->payment_status))
            ->orderBy('date', 'desc')
            ->paginate(10);

        return view('livewire.reports.purchases-report', [
            'purchases' => $purchases
        ]);
    }
}
