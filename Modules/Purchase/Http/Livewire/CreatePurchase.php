<?php

namespace Modules\Purchase\Http\Livewire;

use Livewire\Component;
use Modules\People\Entities\Supplier;
use Modules\Product\Entities\Product;
use Modules\Purchase\Entities\Purchase;
use Modules\Purchase\Entities\PurchaseDetail;
use Modules\Purchase\Entities\PurchasePayment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreatePurchase extends Component
{
    public $supplier_id;
    public $reference_no;
    public $date;
    public $payment_method;
    public $items = [];
    public $discount = 0;
    public $discount_percentage = 0;
    public $total_amount = 0;
    public $total_after_discount = 0;
    public $paid_amount = 0;
    public $due_amount = 0;
    public $note;

    protected $rules = [
        'supplier_id' => 'required|exists:suppliers,id',
        'reference_no' => 'required|unique:purchases,reference_no',
        'date' => 'required|date',
        'payment_method' => 'required|string',
        'items' => 'required|array|min:1',
        'items.*.product_id' => 'required|exists:products,id',
        'items.*.qty' => 'required|integer|min:1',
        'items.*.purchase_price' => 'required|numeric|min:0',
        'items.*.unit_price' => 'required|numeric|min:0',
        'discount' => 'nullable|numeric|min:0',
        'discount_percentage' => 'nullable|numeric|min:0|max:100',
        'paid_amount' => 'nullable|numeric|min:0',
        'note' => 'nullable|string',
    ];

    protected $messages = [
        'supplier_id.required' => 'Please select a supplier',
        'supplier_id.exists' => 'The selected supplier is invalid',
        'reference_no.required' => 'Reference number is required',
        'reference_no.unique' => 'This reference number has already been used',
        'date.required' => 'Date is required',
        'date.date' => 'Please enter a valid date',
        'payment_method.required' => 'Please select a payment method',
        'items.required' => 'At least one product is required',
        'items.min' => 'At least one product is required',
        'items.*.product_id.required' => 'Product is required',
        'items.*.product_id.exists' => 'Selected product is invalid',
        'items.*.qty.required' => 'Quantity is required',
        'items.*.qty.min' => 'Quantity must be at least 1',
        'items.*.purchase_price.required' => 'Purchase price is required',
        'items.*.purchase_price.min' => 'Purchase price must be greater than 0',
        'items.*.unit_price.required' => 'Unit price is required',
        'items.*.unit_price.min' => 'Unit price must be greater than 0',
        'discount.numeric' => 'Discount must be a number',
        'discount.min' => 'Discount cannot be negative',
        'discount_percentage.numeric' => 'Discount percentage must be a number',
        'discount_percentage.min' => 'Discount percentage cannot be negative',
        'discount_percentage.max' => 'Discount percentage cannot be more than 100',
        'paid_amount.numeric' => 'Paid amount must be a number',
        'paid_amount.min' => 'Paid amount cannot be negative',
    ];

    public function mount()
    {
        $this->date = date('Y-m-d');
        $this->reference_no = 'PR-' . date('Ymd') . '-' . rand(1000, 9999);
    }

    public function addItem()
    {
        $this->items[] = [
            'product_id' => '',
            'qty' => 1,
            'purchase_price' => 0,
            'unit_price' => 0,
            'discount' => 0,
            'discount_type' => null,
            'tax' => 0
        ];
    }

    public function removeItem($index)
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);
        $this->calculateTotal();
    }

    public function calculateTotal()
    {
        $total = 0;
        foreach ($this->items as $item) {
            $subtotal = $item['qty'] * $item['unit_price'];
            $total += $subtotal;
        }
        $this->total_amount = $total;
        $this->calculateTotalAfterDiscount();
    }

    public function calculateTotalAfterDiscount()
    {
        $this->total_after_discount = $this->total_amount - $this->discount;
        $this->due_amount = $this->total_after_discount - $this->paid_amount;
    }

    public function updated($propertyName)
    {
        $this->validateOnly($propertyName);
        
        if (str_contains($propertyName, 'items')) {
            $this->calculateTotal();
        }

        if (in_array($propertyName, ['discount', 'discount_percentage', 'paid_amount'])) {
            $this->calculateTotalAfterDiscount();
        }
    }

    public function save()
    {
        $this->validate();

        try {
            DB::beginTransaction();

            $supplier = Supplier::findOrFail($this->supplier_id);

            $purchase = Purchase::create([
                'branch_id' => session('branch_id'),
                'user_id' => auth()->id(),
                'date' => $this->date,
                'reference_no' => $this->reference_no,
                'supplier_id' => $this->supplier_id,
                'supplier_name' => $supplier->supplier_name,
                'discount_percentage' => $this->discount_percentage,
                'discount' => $this->discount,
                'total' => $this->total_amount,
                'paid_amount' => $this->paid_amount,
                'due_amount' => $this->due_amount,
                'payment_status' => ($this->paid_amount >= $this->total_after_discount) ? 'paid' : 'due',
                'payment_method' => $this->payment_method,
                'note' => $this->note,
            ]);

            // Save purchase items and update product quantities
            foreach ($this->items as $item) {
                $product = Product::findOrFail($item['product_id']);
                
                // Create purchase detail
                PurchaseDetail::create([
                    'purchase_id' => $purchase->id,
                    'product_id' => $item['product_id'],
                    'product_name' => $product->product_name,
                    'product_code' => $product->product_code,
                    'qty' => $item['qty'],
                    'purchase_price' => $item['purchase_price'],
                    'unit_price' => $item['unit_price'],
                    'subtotal' => $item['qty'] * $item['unit_price'],
                    'product_discount_amount' => $item['discount'] ?? 0,
                    'product_discount_type' => $item['discount_type'] ?? null,
                    'product_tax_amount' => $item['tax'] ?? 0,
                ]);

                // Update product quantity
                $product->update([
                    'product_quantity' => $product->product_quantity + $item['qty']
                ]);

                // Create or update product batch
                DB::table('product_batches')->updateOrInsert(
                    [
                        'product_id' => $item['product_id'],
                        'batch_number' => $this->reference_no,
                    ],
                    [
                        'product_id' => $item['product_id'],
                        'batch_number' => $this->reference_no,
                        'qty' => $item['qty'],
                        'expiry_date' => null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }

            // Create purchase payment if there's a paid amount
            if ($this->paid_amount > 0) {
                PurchasePayment::create([
                    'branch_id' => session('branch_id'),
                    'purchase_id' => $purchase->id,
                    'amount' => $this->paid_amount,
                    'date' => now(),
                    'reference' => 'PAY-' . strtoupper(Str::random(6)),
                    'payment_method' => $this->payment_method,
                    'note' => $this->note,
                ]);
            }

            DB::commit();

            $this->dispatch('showSuccessMessage', [
                'message' => 'Purchase created successfully!',
                'redirect' => route('purchases.index')
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('showErrorMessage', [
                'message' => 'Error creating purchase: ' . $e->getMessage()
            ]);
        }
    }

    public function render()
    {
        return view('purchase::livewire.create-purchase', [
            'suppliers' => Supplier::all(),
            'products' => Product::all()
        ]);
    }
} 