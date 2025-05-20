<?php

namespace Modules\Purchase\Http\Controllers;

use Modules\Purchase\DataTables\PurchaseDataTable;
use Gloudemans\Shoppingcart\Facades\Cart;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Modules\People\Entities\Supplier;
use Modules\Product\Entities\Product;
use Modules\Purchase\Entities\Purchase;
use Modules\Purchase\Entities\PurchaseDetail;
use Modules\Purchase\Entities\PurchasePayment;
use Modules\Purchase\Http\Requests\StorePurchaseRequest;
use Modules\Purchase\Http\Requests\UpdatePurchaseRequest;
use App\Models\ProductBatch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Barryvdh\DomPDF\Facade\Pdf;

class PurchaseController extends Controller
{

    public function index(PurchaseDataTable $dataTable) {
        abort_if(Gate::denies('access_purchases'), 403);

        return $dataTable->render('purchase::index');
    }


    public function create()
    {
        return view('purchase::create');
    }


    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'reference_no' => 'required|string',
            'supplier_id' => 'required|exists:suppliers,id',
            'date' => 'required|date',
            'payment_method' => 'required|string',
            'paid_amount' => 'required|numeric|min:0',
            'products' => 'required|array|min:1',
            'products.*.product_id' => 'required|exists:products,id',
            'products.*.quantity' => 'required|integer|min:1',
            'products.*.purchase_price' => 'required|numeric|min:0',
            'products.*.expired_date' => 'nullable|date|after:today',
            'discount_percentage' => 'nullable|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0',
            'note' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();

        try {
            // Calculate total amount
            $totalAmount = collect($request->products)->sum(function ($product) {
                return $product['quantity'] * $product['purchase_price'];
            });

            // Calculate due amount
            $dueAmount = $totalAmount - $request->paid_amount;

            // Determine payment status
            $paymentStatus = $request->paid_amount == 0 ? 'Unpaid' : 
                           ($dueAmount == 0 ? 'Paid' : 'Partial');

            // 1. Simpan ke purchases
            $purchase = Purchase::create([
                'reference_no' => $request->reference_no,
                'supplier_id' => $request->supplier_id,
                'supplier_name' => Supplier::findOrFail($request->supplier_id)->name,
                'date' => $request->date,
                'discount_percentage' => $request->discount_percentage ?? 0,
                //'discount_amount' => $request->discount_amount ?? 0,
                'payment_method' => $request->payment_method,
                'paid_amount' => round($request->paid_amount * 100),
                'total_amount' => round($totalAmount * 100),
                'discount_amount' => round($request->discount_amount * 100),
                'due_amount' => $dueAmount,
                'payment_status' => $paymentStatus,
                'note' => $request->note,
                'user_id' => auth()->id(),
                'branch_id' => session(['active_branch' => 1]),// contoh ID cabang default
                'created_by' => auth()->user()->name,
                'updated_by' => auth()->user()->name
            ]);

            // 2. Simpan detail produk & batch
            foreach ($request->products as $product) {
                // Detail
                PurchaseDetail::create([
                    'purchase_id' => $purchase->id,
                    'product_id' => $product['product_id'],
                    'product_name' => Product::findOrFail($product['product_id'])->product_name,
                    'product_code' => Product::findOrFail($product['product_id'])->product_code,
                    'quantity' => $product['quantity'],
                    'price' => $product['purchase_price'],
                    'unit_price' => $product['purchase_price'],
                    'sub_total' => $product['quantity'] * $product['purchase_price'],
                    'product_discount_amount' => 0,
                    'product_discount_type' => 'fixed',
                    'product_tax_amount' => 0,
                    'created_by' => auth()->user()->name,
                    'updated_by' => auth()->user()->name
                ]);

                // Batch
                ProductBatch::addStock([
                    'product_id' => $product['product_id'],
                    'branch_id' => session(['active_branch' => 1]), // contoh ID cabang default,
                    'quantity' => $product['quantity'],
                    'purchase_price' => $product['purchase_price'],
                    'expired_date' => $product['expired_date'],
                    'purchase_id' => $purchase->id,
                    'batch_code' => $purchase->reference_no . '-' . $product['product_id'],
                    'created_by' => auth()->user()->name,
                    'updated_by' => auth()->user()->name
                ]);
            }

            // 3. Simpan pembayaran (jika ada)
            if ($purchase->paid_amount > 0) {
                PurchasePayment::create([
                    'purchase_id' => $purchase->id,
                    'branch_id' => session(['active_branch' => 1]), // contoh ID cabang default,
                    'amount' => $purchase->paid_amount,
                    'date' => $purchase->date,
                    'reference' => 'PAY-' . $purchase->reference_no,
                    'payment_method' => $purchase->payment_method,
                    'note' => 'Initial payment for purchase ' . $purchase->reference_no,
                    'created_by' => auth()->user()->name,
                    'updated_by' => auth()->user()->name
                ]);
            }

            DB::commit();

            return response()->json([
                'message' => 'Purchase created successfully',
                'data' => $purchase
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to create purchase',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getStock(Request $request, $productId, $branchId)
    {
        $stock = ProductBatch::getAvailableStock($productId, $branchId);
        return response()->json([
            'data' => $stock
        ]);
    }


    public function show(Purchase $purchase) {
        abort_if(Gate::denies('show_purchases'), 403);

        $supplier = Supplier::findOrFail($purchase->supplier_id);

        return view('purchase::show', compact('purchase', 'supplier'));
    }


    public function edit(Purchase $purchase) {
        abort_if(Gate::denies('edit_purchases'), 403);

        $purchase_details = $purchase->purchaseDetails;

        Cart::instance('purchase')->destroy();

        $cart = Cart::instance('purchase');

        foreach ($purchase_details as $purchase_detail) {
            $cart->add([
                'id'      => $purchase_detail->product_id,
                'name'    => $purchase_detail->product_name,
                'qty'     => $purchase_detail->quantity,
                'price'   => $purchase_detail->price,
                'weight'  => 1,
                'options' => [
                    'product_discount' => $purchase_detail->product_discount_amount,
                    'product_discount_type' => $purchase_detail->product_discount_type,
                    'sub_total'   => $purchase_detail->sub_total,
                    'code'        => $purchase_detail->product_code,
                    'stock'       => Product::findOrFail($purchase_detail->product_id)->product_quantity,
                    'product_tax' => $purchase_detail->product_tax_amount,
                    'unit_price'  => $purchase_detail->unit_price
                ]
            ]);
        }

        return view('purchase::edit', compact('purchase'));
    }


    public function update(UpdatePurchaseRequest $request, Purchase $purchase) {
        DB::transaction(function () use ($request, $purchase) {
            $due_amount = $request->total_amount - $request->paid_amount;
            if ($due_amount == $request->total_amount) {
                $payment_status = 'Unpaid';
            } elseif ($due_amount > 0) {
                $payment_status = 'Partial';
            } else {
                $payment_status = 'Paid';
            }

            foreach ($purchase->purchaseDetails as $purchase_detail) {
                if ($purchase->status == 'Completed') {
                    $product = Product::findOrFail($purchase_detail->product_id);
                    $product->update([
                        'product_quantity' => $product->product_quantity - $purchase_detail->quantity
                    ]);
                }
                $purchase_detail->delete();
            }

            $purchase->update([
                'date' => $request->date,
                'reference' => $request->reference,
                'supplier_id' => $request->supplier_id,
                'supplier_name' => Supplier::findOrFail($request->supplier_id)->supplier_name,
                'tax_percentage' => $request->tax_percentage,
                'discount_percentage' => $request->discount_percentage,
                'shipping_amount' => $request->shipping_amount * 100,
                'paid_amount' => $request->paid_amount * 100,
                'total_amount' => $request->total_amount * 100,
                'due_amount' => $due_amount * 100,
                'status' => $request->status,
                'payment_status' => $payment_status,
                'payment_method' => $request->payment_method,
                'note' => $request->note,
                'tax_amount' => Cart::instance('purchase')->tax() * 100,
                'discount_amount' => Cart::instance('purchase')->discount() * 100,
            ]);

            foreach (Cart::instance('purchase')->content() as $cart_item) {
                PurchaseDetail::create([
                    'purchase_id' => $purchase->id,
                    'product_id' => $cart_item->id,
                    'product_name' => $cart_item->name,
                    'product_code' => $cart_item->options->code,
                    'quantity' => $cart_item->qty,
                    'price' => $cart_item->price * 100,
                    'unit_price' => $cart_item->options->unit_price * 100,
                    'sub_total' => $cart_item->options->sub_total * 100,
                    'product_discount_amount' => $cart_item->options->product_discount * 100,
                    'product_discount_type' => $cart_item->options->product_discount_type,
                    'product_tax_amount' => $cart_item->options->product_tax * 100,
                ]);

                if ($request->status == 'Completed') {
                    $product = Product::findOrFail($cart_item->id);
                    $product->update([
                        'product_quantity' => $product->product_quantity + $cart_item->qty
                    ]);
                }
            }

            Cart::instance('purchase')->destroy();
        });

        toast('Purchase Updated!', 'info');

        return redirect()->route('purchases.index');
    }


    public function destroy(Purchase $purchase) {
        abort_if(Gate::denies('delete_purchases'), 403);

        $purchase->delete();

        toast('Purchase Deleted!', 'warning');

        return redirect()->route('purchases.index');
    }

    public function pdf($id)
    {
        $purchase = Purchase::findOrFail($id);
        $supplier = Supplier::findOrFail($purchase->supplier_id);

        $pdf = PDF::loadView('purchase::print', [
            'purchase' => $purchase,
            'supplier' => $supplier,
        ])->setPaper('a4');

        return $pdf->stream('purchase-'. $purchase->reference .'.pdf');
    }
}
