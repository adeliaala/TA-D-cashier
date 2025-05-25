<?php

namespace Modules\Sale\Http\Controllers;

use Gloudemans\Shoppingcart\Facades\Cart;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\People\Entities\Customer;
use Modules\Product\Entities\Category;
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
        $total_allocated_price = 0;

        foreach ($batches as $batch) {
            if ($remaining <= 0) break;

            $used_qty = min($batch->qty, $remaining);

            if ($first_price === null) {
                $first_price = $batch->price;
            }

            $allocation[] = [
                'batch_id' => $batch->id,
                'qty' => $used_qty,
                'unit_price' => $batch->price
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
            'allocation' => $allocation,
            'unit_price' => $first_price,
            'total_price' => $total_allocated_price
        ];
    }

    public function store(StorePosSaleRequest $request)
    {
        DB::transaction(function () use ($request) {
            $branch_id = session('branch_id');

            if (!$branch_id) {
                throw new \Exception("Cabang belum dipilih. Silakan pilih cabang terlebih dahulu.");
            }

            $due_amount = $request->total_amount - $request->paid_amount;

            $payment_status = match (true) {
                $due_amount == $request->total_amount => 'Unpaid',
                $due_amount > 0 => 'Partial',
                default => 'Paid',
            };

            $discount_percentage = $request->discount_percentage ?? 0;
            $tax_percentage = $request->tax_percentage ?? 0;

            $sale = Sale::create([
                'date' => now()->format('Y-m-d'),
                'branch_id' => $branch_id,
                'reference' => 'PSL',
                'customer_id' => $request->customer_id,
                'customer_name' => $request->customer_id
                    ? Customer::findOrFail($request->customer_id)->customer_name
                    : 'Walk-in Customer',
                'tax_percentage' => $tax_percentage,
                'discount_percentage' => $discount_percentage,
                'paid_amount' => $request->paid_amount * 100,
                'total_amount' => $request->total_amount * 100,
                'due_amount' => $due_amount * 100,
                'payment_status' => $payment_status,
                'payment_method' => $request->payment_method,
                'note' => $request->note,
                'tax_amount' => Cart::instance('sale')->tax() * 100,
                'discount_amount' => Cart::instance('sale')->discount() * 100,
                'user_id' => auth()->id(),
            ]);

            foreach (Cart::instance('sale')->content() as $cart_item) {
                $allocation = $this->allocateProductFromBatches($cart_item->id, $cart_item->qty);

                SaleDetails::create([
                    'sale_id' => $sale->id,
                    'product_id' => $cart_item->id,
                    'product_name' => $cart_item->name,
                    'product_code' => $cart_item->options->code,
                    'quantity' => $cart_item->qty,
                    'price' => $allocation['total_price'] * 100,
                    'unit_price' => $allocation['unit_price'] * 100,
                    'sub_total' => $cart_item->options->sub_total * 100,
                    'product_discount_amount' => $cart_item->options->product_discount * 100,
                    'product_discount_type' => $cart_item->options->product_discount_type,
                    'product_tax_amount' => $cart_item->options->product_tax * 100,
                ]);
            }

            Cart::instance('sale')->destroy();

            if ($sale->paid_amount > 0) {
                SalePayment::create([
                    'date' => now()->format('Y-m-d'),
                    'reference' => 'INV/' . $sale->reference,
                    'amount' => $sale->paid_amount,
                    'sale_id' => $sale->id,
                    'payment_method' => $request->payment_method
                ]);
            }
        });

        toast('POS Sale Created!', 'success');
        return redirect()->route('sales.index');
    }
}
