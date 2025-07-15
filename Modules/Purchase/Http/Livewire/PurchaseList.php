<?php

namespace Modules\Purchase\Http\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use Modules\Purchase\Entities\Purchase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PurchaseList extends Component
{
    use WithPagination;

    public $search = '';
    public $dateFrom = '';
    public $dateTo = '';
    public $supplier_id = '';
    public $payment_status = '';
    public $perPage = 10;

    protected $queryString = [
        'search' => ['except' => ''],
        'dateFrom' => ['except' => ''],
        'dateTo' => ['except' => ''],
        'supplier_id' => ['except' => ''],
        'payment_status' => ['except' => ''],
    ];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function deletePurchase($id)
    {
        $this->dispatch('deletePurchase', id: $id);
    }

    public function confirmDelete($id)
    {
        $purchase = Purchase::findOrFail($id);
        
        if ($purchase->payment_status === 'Paid') {
            session()->flash('error', 'Tidak dapat menghapus pembelian yang sudah dibayar.');
            return;
        }

        try {
            DB::beginTransaction();
            
            // Delete related records
            $purchase->purchaseDetails()->delete();
            $purchase->purchasePayments()->delete();
            
            // Delete the purchase
            $purchase->delete();
            
            DB::commit();
            session()->flash('message', 'Pembelian berhasil dihapus.');
        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'Error menghapus pembelian: ' . $e->getMessage());
        }
    }

    public function render()
    {
        $purchases = Purchase::query()
            ->with(['supplier', 'purchaseDetails', 'purchasePayments'])
            ->when($this->search, function ($query) {
                $query->where('reference_no', 'like', '%' . $this->search . '%')
                    ->orWhereHas('supplier', function ($q) {
                        $q->where('supplier_name', 'like', '%' . $this->search . '%');
                    });
            })
            ->when($this->dateFrom, function ($query) {
                $query->whereDate('date', '>=', $this->dateFrom);
            })
            ->when($this->dateTo, function ($query) {
                $query->whereDate('date', '<=', $this->dateTo);
            })
            ->when($this->supplier_id, function ($query) {
                $query->where('supplier_id', $this->supplier_id);
            })
            ->when($this->payment_status, function ($query) {
                $query->where('payment_status', $this->payment_status);
            })
            ->where('branch_id', session('active_branch'))
            ->latest()
            ->paginate($this->perPage);

        return view('purchase::livewire.purchase-list', [
            'purchases' => $purchases,
            'suppliers' => \Modules\People\Entities\Supplier::all()
        ]);
    }
} 