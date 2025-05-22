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
use Modules\Purchase\Http\Requests\StorePurchaseRequest;
use Modules\Purchase\Http\Requests\UpdatePurchaseRequest;
use App\Models\ProductBatch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class PurchaseController extends Controller
{

    public function index(PurchaseDataTable $dataTable) {
        abort_if(Gate::denies('access_purchases'), 403);

        // Log untuk debugging
        Log::info('PurchaseController@index called', [
            'active_branch' => session('active_branch'),
            'user' => auth()->user()->name ?? 'Unknown'
        ]);
        
        // Periksa data purchases secara langsung
        $purchases = DB::table('purchases')->get();
        Log::info('Direct Purchase Query', [
            'count' => $purchases->count(),
            'data' => $purchases->take(3)->toArray()
        ]);
        
        // Jika tidak ada data di DataTable, tampilkan data langsung dari DB
        if ($purchases->count() > 0 && request()->ajax()) {
            Log::info('Returning direct data for AJAX request');
            return datatables()
                ->of($purchases)
                ->addColumn('action', function ($data) {
                    return view('purchase::partials.actions', [
                        'id' => $data->id
                    ])->render();
                })
                ->addColumn('supplier_name', function ($data) {
                    $supplier = DB::table('suppliers')->where('id', $data->supplier_id)->first();
                    return $supplier ? $supplier->name : 'N/A';
                })
                ->editColumn('total', function ($data) {
                    return format_currency($data->total / 100);
                })
                ->editColumn('paid_amount', function ($data) {
                    return format_currency($data->paid_amount / 100);
                })
                ->editColumn('due_amount', function ($data) {
                    return format_currency($data->due_amount / 100);
                })
                ->editColumn('payment_status', function ($data) {
                    return view('purchase::partials.payment-status', [
                        'payment_status' => $data->payment_status
                    ])->render();
                })
                ->rawColumns(['action', 'payment_status'])
                ->make(true);
        }

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
        'products.*.qty' => 'required|integer|min:1',
        'products.*.unit_price' => 'required|numeric|min:0',
        'products.*.price' => 'required|numeric|min:0',
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
        // Log request data
        Log::info("Request Data", $request->all());

        // Calculate total amount
        $totalAmount = collect($request->products)->sum(function ($product) {
            return $product['qty'] * $product['purchase_price'];
        });

        // Calculate due amount
        $dueAmount = $totalAmount - $request->paid_amount;

        // Determine payment status
        $paymentStatus = $request->paid_amount == 0 ? 'Unpaid' : 
                       ($dueAmount == 0 ? 'Paid' : 'Partial');

        // Check active branch
        if (!session('active_branch')) {
            return response()->json([
                'message' => 'Branch not selected'
            ], 422);
        }

        // 1. Simpan ke purchases
        $purchase = Purchase::create([
            'reference_no' => $request->reference_no,
            'supplier_id' => $request->supplier_id,
            'date' => $request->date,
            'discount_percentage' => $request->discount_percentage ?? 0,
            'discount' => round($request->discount_amount * 100),
            'payment_method' => $request->payment_method,
            'paid_amount' => round($request->paid_amount * 100),
            'total' => round($totalAmount * 100),
            'due_amount' => round($dueAmount * 100),
            'payment_status' => $paymentStatus,
            'note' => $request->note,
            'user_id' => auth()->id(),
            'branch_id' => session('active_branch')
        ]);

        // Log purchase created
        Log::info("Purchase Created", ['purchase' => $purchase->toArray()]);

        // 2. Simpan detail produk & batch
        foreach ($request->products as $product) {
            // Detail
            PurchaseDetail::create([
                'purchase_id' => $purchase->id,
                'product_id' => $product['product_id'],
                'product_name' => Product::findOrFail($product['product_id'])->product_name,
                'product_code' => Product::findOrFail($product['product_id'])->product_code,
                'qty' => $product['qty'],
                // 'price' => $product['purchase_price'],
                'unit_price' => $product['unit_price'],
                'subtotal' => $product['qty'] * $product['unit_price'],
                'product_discount_amount' => 0,
                'product_discount_type' => 'fixed',
                'product_tax_amount' => 0
            ]);

            // Batch
            ProductBatch::addStock([
                'product_id' => $product['product_id'],
                'branch_id' => session('active_branch'),
                'qty' => $product['qty'],
                'unit_price' => $product['unit_price'],
                'price' => $product['price'],
                'exp_date' => $product['expired_date'],
                'purchase_id' => $purchase->id,
                'batch_code' => $purchase->reference_no . '-' . $product['product_id'],
                'created_by' => auth()->user()->name ?? 'system',
                'updated_by' => auth()->user()->name ?? 'system'
            ]);
        }

        // 3. Simpan pembayaran (jika ada)
        if ($purchase->paid_amount > 0) {
            // Kode ini dinonaktifkan karena tabel purchase_payments sudah dihapus
            /*
            PurchasePayment::create([
                'purchase_id' => $purchase->id,
                'branch_id' => session('active_branch'),
                'amount' => $purchase->paid_amount,
                'date' => $purchase->date,
                'reference' => 'PAY-' . $purchase->reference_no,
                'payment_method' => $purchase->payment_method,
                'note' => 'Initial payment for purchase ' . $purchase->reference_no
            ]);
            */
            
            // Hanya catat di log
            Log::info("Purchase Payment Info (not saved to DB)", [
                'purchase_id' => $purchase->id,
                'amount' => $purchase->paid_amount,
                'payment_method' => $purchase->payment_method
            ]);
        }

        DB::commit();

        return response()->json([
            'message' => 'Purchase created successfully',
            'data' => $purchase
        ], 201);
    } catch (\Exception $e) {
        DB::rollBack();
        Log::error("Error Creating Purchase", [
            'error' => $e->getMessage(),
            'line' => $e->getLine(),
            'file' => $e->getFile()
        ]);

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
                'qty'     => $purchase_detail->qty,
                'unit_price'   => $purchase_detail->unit_price,
                'weight'  => 1,
                'options' => [
                    'product_discount' => $purchase_detail->product_discount_amount,
                    'product_discount_type' => $purchase_detail->product_discount_type,
                    'subtotal'   => $purchase_detail->subtotal,
                    'code'        => $purchase_detail->product_code,
                    'stock'       => Product::findOrFail($purchase_detail->product_id)->product_qty,
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
                        'product_qty' => $product->product_qty - $purchase_detail->qty
                    ]);
                }
                $purchase_detail->delete();
            }

            $purchase->update([
                'date' => $request->date,
                'reference_no' => $request->reference,
                'supplier_id' => $request->supplier_id,
                'discount_percentage' => $request->discount_percentage,
                'discount' => $request->shipping_amount * 100,
                'paid_amount' => $request->paid_amount * 100,
                'total' => $request->total_amount * 100,
                'due_amount' => $due_amount * 100,
                'payment_status' => $payment_status,
                'payment_method' => $request->payment_method,
                'note' => $request->note
            ]);

            foreach (Cart::instance('purchase')->content() as $cart_item) {
                PurchaseDetail::create([
                    'purchase_id' => $purchase->id,
                    'product_id' => $cart_item->id,
                    'product_name' => $cart_item->name,
                    'product_code' => $cart_item->options->code,
                    'qty' => $cart_item->qty,
                    //'price' => $cart_item->price * 100,
                    'unit_price' => $cart_item->price * 100,
                    'subtotal' => $cart_item->options->subtotal * 100,
                    'product_discount_amount' => $cart_item->options->product_discount * 100,
                    'product_discount_type' => $cart_item->options->product_discount_type,
                    'product_tax_amount' => $cart_item->options->product_tax * 100,
                ]);

                if ($request->status == 'Completed') {
                    $product = Product::findOrFail($cart_item->id);
                    $product->update([
                        'product_qty' => $product->product_qty + $cart_item->qty
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

        // Menggunakan cara alternatif untuk membuat PDF
        $pdf = app('dompdf.wrapper')
            ->loadView('purchase::print', [
                'purchase' => $purchase,
                'supplier' => $supplier,
            ])
            ->setPaper('a4');

        return $pdf->stream('purchase-'. $purchase->reference_no .'.pdf');
    }
}
