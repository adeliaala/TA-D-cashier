<?php

namespace Modules\Purchase\Http\Livewire;

use Livewire\Component;
use Modules\Purchase\Entities\Purchase;
use Illuminate\Support\Facades\Auth;

class PurchaseShow extends Component
{
    public $purchase;
    public $purchase_id;
    public $payment_amount = 0;
    public $payment_method = 'Cash';
    public $payment_note = '';

    protected $rules = [
        'payment_amount' => 'required|numeric|min:0',
        'payment_method' => 'required',
        'payment_note' => 'nullable|string|max:255'
    ];

    public function mount($id)
    {
        $this->purchase_id = $id;
        $this->loadPurchase();
    }

    public function loadPurchase()
    {
        $this->purchase = Purchase::with(['supplier', 'purchaseDetails.product', 'purchasePayments'])
            ->findOrFail($this->purchase_id);
    }

    public function addPayment()
    {
        $this->validate();

        if ($this->payment_amount > $this->purchase->due_amount) {
            session()->flash('error', 'Payment amount cannot be greater than due amount.');
            return;
        }

        try {
            $this->purchase->purchasePayments()->create([
                'amount' => $this->payment_amount,
                'payment_method' => $this->payment_method,
                'note' => $this->payment_note,
                'user_id' => Auth::id(),
                'created_by' => Auth::user()->name,
                'updated_by' => Auth::user()->name
            ]);

            // Update purchase payment status
            $total_paid = $this->purchase->purchasePayments()->sum('amount');
            $this->purchase->update([
                'paid_amount' => $total_paid,
                'due_amount' => $this->purchase->total_amount - $total_paid,
                'payment_status' => $total_paid >= $this->purchase->total_amount ? 'Paid' : 'Partial',
                'updated_by' => Auth::user()->name
            ]);

            $this->reset(['payment_amount', 'payment_method', 'payment_note']);
            $this->loadPurchase();
            session()->flash('message', 'Payment added successfully.');
        } catch (\Exception $e) {
            session()->flash('error', 'Error adding payment: ' . $e->getMessage());
        }
    }

    public function render()
    {
        return view('purchase::livewire.purchase-show');
    }
} 