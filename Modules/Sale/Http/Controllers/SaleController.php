<?php

namespace Modules\Sale\Http\Controllers;

use Gloudemans\Shoppingcart\Facades\Cart;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Modules\People\Entities\Customer;
use Modules\Product\Entities\Product;
use Modules\Sale\DataTables\SalesDataTable;
use Modules\Sale\Entities\Sale;
use Modules\Sale\Entities\SaleDetails;
use Modules\Sale\Entities\SalePayment;
use Modules\Sale\Http\Requests\StoreSaleRequest;
use Modules\Sale\Http\Requests\UpdateSaleRequest;

class SaleController extends Controller
{
    public function index(SalesDataTable $dataTable)
    {
        abort_if(Gate::denies('access_sales'), 403);
        return $dataTable->render('sale::index');
    }

    public function create()
    {
        abort_if(Gate::denies('create_sales'), 403);
        Cart::instance('sale')->destroy();
        return view('sale::create');
    }

    public function store(StoreSaleRequest $request)
    {
        DB::transaction(function () use ($request) {
            $branch_id = session('branch_id');
            if (!$branch_id) {
                throw new \Exception("Branch belum dipilih.");
            }
            $tax = Cart::instance('sale')->tax();
            $discount = Cart::instance('sale')->discount();

            $total_amount = 0 - $discount ;
            foreach (Cart::instance('sale')->content() as $item) {
                $total_amount +=  $item->options->sub_total; // sub_total sudah diskon
            }

            // Total amount hanya total bersih + pajak
            //$total_amount = $total + $tax + $discount;

            // Pastikan paid_amount tidak ada format mata uang
            $paid_amount = preg_replace('/[^\d]/', '', $request->paid_amount);

            $due_amount = $total_amount - $paid_amount;

            $payment_status = $due_amount == $total_amount ? 'Unpaid' :
                            ($due_amount > 0 ? 'Partial' : 'Paid');


            $customer_id = $request->customer_id ?? 1;

            $sale = Sale::create([
                'branch_id'           => $branch_id,
                'date'                => $request->date,
                'reference'           => $request->reference,
                'customer_id'         => $customer_id,
                'customer_name'       => optional(Customer::find($customer_id))->customer_name,
                'tax_percentage'      => (float) $request->tax_percentage ?? 0,
                'discount_percentage' => (float) $request->discount_percentage ?? 0,
                'paid_amount'         => $paid_amount,
                'total_amount'        => $total_amount,
                'due_amount'          => $due_amount,
                'payment_status'      => $payment_status,
                'payment_method'      => $request->payment_method,
                'note'                => $request->note,
                'tax_amount'          => $tax,
                'discount_amount'     => $discount,
            ]);

            Log::info('SALE CREATED', [
                'sale_id' => $sale->id,
                'total_amount' => $sale->total_amount,
                'paid_amount' => $sale->paid_amount,
                'due_amount' => $sale->due_amount
            ]);

            foreach (Cart::instance('sale')->content() as $item) {
                Log::info('SALE DETAIL ITEM:', [
                    'product_id' => $item->id,
                    'unit_price' => $item->options->unit_price,
                    'price' => $item->price,
                    'qty' => $item->qty,
                    'sub_total' => $item->options->sub_total,
                ]);

                SaleDetails::create([
                    'sale_id'                 => $sale->id,
                    'product_id'              => $item->id,
                    'product_name'            => $item->name,
                    'product_code'            => $item->options->code,
                    'quantity'                => $item->qty,
                    'unit_price'              => (float) $item->options->unit_price,
                    'price'                   => (float) $item->price,
                    'sub_total'               => (float) $item->options->sub_total,
                    'product_discount_amount' => (float) $item->options->product_discount,
                    'product_discount_type'   => $item->options->product_discount_type,
                    'product_tax_amount'      => (float) $item->options->product_tax,

                
                ]);

                
                

                if (in_array($request->status, ['Shipped', 'Completed'])) {
                    $product = Product::find($item->id);
                    if ($product) {
                        $product->decrement('product_quantity', $item->qty);
                        $this->allocateProductFromBatches($item->id, $item->qty);
                    }
                }
            }

            Cart::instance('sale')->destroy();

            if ($paid_amount > 0) {
                SalePayment::create([
                    'date'           => $request->date,
                    'reference'      => 'INV/' . $sale->reference,
                    'amount'         => $paid_amount,
                    'sale_id'        => $sale->id,
                    'payment_method' => $request->payment_method
                ]);
            }
        });

        toast('Sale Created!', 'success');
        return redirect()->route('sales.index');
    }

    public function edit(Sale $sale)
    {
        abort_if(Gate::denies('edit_sales'), 403);
        Cart::instance('sale')->destroy();

        foreach ($sale->saleDetails as $detail) {
            Cart::instance('sale')->add([
                'id'    => $detail->product_id,
                'name'  => $detail->product_name,
                'qty'   => $detail->quantity,
                'price' => (float) $detail->price,
                'weight'=> 1,
                'options' => [
                    'code'                  => $detail->product_code,
                    'unit_price'            => (float) $detail->unit_price,
                    'sub_total'             => (float) $detail->sub_total,
                    'product_discount'      => (float) $detail->product_discount_amount,
                    'product_discount_type' => $detail->product_discount_type,
                    'product_tax'           => (float) $detail->product_tax_amount,
                    'stock'                 => optional(Product::find($detail->product_id))->product_quantity,
                ]
            ]);
        }

        return view('sale::edit', compact('sale'));
    }

    public function update(UpdateSaleRequest $request, Sale $sale)
    {
        // DB::transaction(function () use ($request, $sale) {
        //     $total = 0;
        //     foreach (Cart::instance('sale')->content() as $item) {
        //         $total += (float) $item->options->sub_total;
        //     }

        //     $discount = (float) Cart::instance('sale')->discount();
        //     $tax = (float) Cart::instance('sale')->tax();

        //     // DISINI BENAR
        //     $total_amount = $total + $tax + $discount;


        //     $paid_amount = (float) $request->paid_amount ?? 0;
        //     $due_amount = $total_amount - $paid_amount;

        //     $payment_status = $due_amount == $total_amount ? 'Unpaid' :
        //                       ($due_amount > 0 ? 'Partial' : 'Paid');

        //     // Kembalikan stok jika status sebelumnya Completed atau Shipped
        //     foreach ($sale->saleDetails as $detail) {
        //         if (in_array($sale->status, ['Shipped', 'Completed'])) {
        //             $product = Product::find($detail->product_id);
        //             if ($product) {
        //                 $product->increment('product_quantity', $detail->quantity);
        //             }
        //         }
        //         $detail->delete();
        //     }

        //     $sale->update([
        //         'date'                => $request->date,
        //         'reference'           => $request->reference,
        //         'customer_id'         => $request->customer_id,
        //         'customer_name'       => optional(Customer::find($request->customer_id))->customer_name,
        //         'tax_percentage'      => (float) $request->tax_percentage ?? 0,
        //         'discount_percentage' => (float) $request->discount_percentage ?? 0,
        //         'paid_amount'         => $paid_amount,
        //         'total_amount'        => $total_amount,
        //         'due_amount'          => $due_amount,
        //         'payment_status'      => $payment_status,
        //         'payment_method'      => $request->payment_method,
        //         'note'                => $request->note,
        //         'tax_amount'          => $tax,
        //         'discount_amount'     => $discount,
        //     ]);

        //     foreach (Cart::instance('sale')->content() as $item) {
        //         SaleDetails::create([
        //             'sale_id'                 => $sale->id,
        //             'product_id'              => $item->id,
        //             'product_name'            => $item->name,
        //             'product_code'            => $item->options->code,
        //             'quantity'                => $item->qty,
        //             'unit_price'              => (float) $item->options->unit_price,
        //             'price'                   => (float) $item->price,
        //             'sub_total'               => (float) $item->options->sub_total,
        //             'product_discount_amount' => (float) $item->options->product_discount,
        //             'product_discount_type'   => $item->options->product_discount_type,
        //             'product_tax_amount'      => (float) $item->options->product_tax,
        //         ]);

        //         if (in_array($request->status, ['Shipped', 'Completed'])) {
        //             $product = Product::find($item->id);
        //             if ($product) {
        //                 $product->decrement('product_quantity', $item->qty);
        //                 $this->allocateProductFromBatches($item->id, $item->qty);
        //             }
        //         }
        //     }

        //     $sale->payments()->delete();
        //     if ($paid_amount > 0) {
        //         SalePayment::create([
        //             'date'           => $request->date,
        //             'reference'      => 'INV/' . $sale->reference,
        //             'amount'         => $paid_amount,
        //             'sale_id'        => $sale->id,
        //             'payment_method' => $request->payment_method
        //         ]);
        //     }

        //     Cart::instance('sale')->destroy();
        // });

        toast('Sale Updated!', 'info');
        return redirect()->route('sales.index');
    }

    public function show(Sale $sale)
    {
        abort_if(Gate::denies('show_sales'), 403);
        $customer = Customer::find($sale->customer_id);
        return view('sale::show', compact('sale', 'customer'));
    }

    public function destroy(Sale $sale)
    {
        abort_if(Gate::denies('delete_sales'), 403);

        $sale->delete();

        toast('Sale Deleted!', 'warning');
        return redirect()->route('sales.index');
    }

    private function allocateProductFromBatches($product_id, $required_qty)
    {
        $branch_id = session('branch_id');

        $batches = DB::table('product_batches')
            ->where('product_id', $product_id)
            ->where('branch_id', $branch_id)
            ->where('qty', '>', 0)
            ->orderBy('created_at')
            ->get();

        $remaining = $required_qty;

        foreach ($batches as $batch) {
            if ($remaining <= 0) break;

            $used_qty = min($batch->qty, $remaining);

            DB::table('product_batches')
                ->where('id', $batch->id)
                ->decrement('qty', $used_qty);

            $remaining -= $used_qty;
        }

        if ($remaining > 0) {
            throw new \Exception("Stok produk tidak cukup di cabang saat ini.");
        }
    }
}
