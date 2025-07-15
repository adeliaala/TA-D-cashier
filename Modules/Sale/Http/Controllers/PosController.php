<?php

namespace Modules\Sale\Http\Controllers;

use Gloudemans\Shoppingcart\Facades\Cart;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\People\Entities\Customer;
use Modules\Product\Entities\Category;
use Modules\Product\Entities\Product;
use Modules\Sale\Entities\Sale;
use Modules\Sale\Entities\SaleDetails;
use Modules\Sale\Entities\SalePayment;
use Modules\Sale\Http\Requests\StorePosSaleRequest;

class PosController extends Controller
{
    public function index()
    {
        Cart::instance('sale')->destroy();

        $customers = Customer::all();
        $product_categories = Category::all();

        return view('sale::pos.index', compact('product_categories', 'customers'));
    }

    private function allocateProductFromBatches($product_id, $required_qty)
    {
        $branch_id = session('branch_id');

        $batches = DB::table('product_batches')
            ->where('product_id', $product_id)
            ->where('branch_id', $branch_id)
            ->where('qty', '>', 0)
            ->orderBy('created_at') // FIFO
            ->get();

        $allocation = [];
        $remaining = $required_qty;
        $first_price = null;
        $first_unit_price = null;
        $total_allocated_price = 0;

        foreach ($batches as $batch) {
            if ($remaining <= 0) break;

            $used_qty = min($batch->qty, $remaining);

            if ($first_price === null) {
                $first_price = $batch->price;
                $first_unit_price = $batch->unit_price;
            }

            $allocation[] = [
                'batch_id' => $batch->id,
                'qty'      => $used_qty,
                'price'    => $batch->price
            ];

            DB::table('product_batches')
                ->where('id', $batch->id)
                ->decrement('qty', $used_qty);

            $total_allocated_price += $used_qty * $batch->price;
            $remaining -= $used_qty;
        }

        if ($remaining > 0) {
            throw new \Exception("Stok produk tidak cukup di cabang saat ini.");
        }

        return [
            'allocation'    => $allocation,
            'price'         => $first_price,
            'unit_price'    => $first_unit_price,
            'total_price'   => $total_allocated_price
        ];
    }

    public function store(StorePosSaleRequest $request)
    {
        DB::transaction(function () use ($request) {
            $branch_id = session('branch_id');

            if (!$branch_id) {
                throw new \Exception("Cabang belum dipilih. Silakan pilih cabang terlebih dahulu.");
            }

            // Convert to float for safety
            $total_amount        = (float) str_replace(',', '', $request->total_amount);
            $paid_amount         = (float) str_replace(',', '', $request->paid_amount);
            $tax_percentage      = (float) ($request->tax_percentage ?? 0);
            $discount_percentage = (float) ($request->discount_percentage ?? 0);
            $due_amount          = $total_amount - $paid_amount;

            $payment_status = match (true) {
                $due_amount == $total_amount => 'Unpaid',
                $due_amount > 0              => 'Partial',
                default                      => 'Paid',
            };

            // Set tax and discount globally to reflect on Cart
            Cart::instance('sale')->setGlobalTax($tax_percentage);
            Cart::instance('sale')->setGlobalDiscount($discount_percentage);

            $sale = Sale::create([
                'date'                => now()->format('Y-m-d'),
                'branch_id'           => $branch_id,
                'reference'           => 'PSL',
                'customer_id'         => $request->customer_id,
                'customer_name'       => $request->customer_id
                    ? Customer::findOrFail($request->customer_id)->customer_name
                    : 'Walk-in Customer',
                'tax_percentage'      => $tax_percentage,
                'discount_percentage' => $discount_percentage,
                'paid_amount'         => $paid_amount,
                'total_amount'        => $total_amount,
                'due_amount'          => $due_amount,
                'payment_status'      => $payment_status,
                'payment_method'      => $request->payment_method,
                'note'                => $request->note,
                'tax_amount'          => (float) Cart::instance('sale')->tax(),
                'discount_amount'     => (float) Cart::instance('sale')->discount(),
                'user_id'             => auth()->id(),
            ]);

            Log::info('POS SALE CREATED', [
                'sale_id' => $sale->id,
                'total' => Cart::instance('sale')->total(),
                'total_amount' => $sale->total_amount,
                'paid_amount' => $sale->paid_amount
            ]);

            foreach (Cart::instance('sale')->content() as $cart_item) {
                $allocation = $this->allocateProductFromBatches($cart_item->id, $cart_item->qty);

                SaleDetails::create([
                    'sale_id'                 => $sale->id,
                    'product_id'              => $cart_item->id,
                    'product_name'            => $cart_item->name,
                    'product_code'            => $cart_item->options->code,
                    'quantity'                => $cart_item->qty,
                    'unit_price'              => (float) $allocation['unit_price'],  // harga beli
                    'price'                   => (float) $allocation['price'],       // harga jual
                    'sub_total'               => (float) $allocation['price'] * $cart_item->qty,
                    'product_discount_amount' => (float) $cart_item->options->product_discount,
                    'product_discount_type'   => $cart_item->options->product_discount_type,
                    'product_tax_amount'      => (float) $cart_item->options->product_tax,
                ]);
            }

            Cart::instance('sale')->destroy();

            if ($paid_amount > 0) {
                SalePayment::create([
                    'date'           => now()->format('Y-m-d'),
                    'reference'      => 'INV/' . $sale->reference,
                    'amount'         => $paid_amount,
                    'sale_id'        => $sale->id,
                    'payment_method' => $request->payment_method
                ]);
            }
        });

        toast('POS Sale Created!', 'success');
        return redirect()->route('sales.index');
    }
}
