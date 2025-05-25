<?php

namespace Modules\Sale\Http\Controllers;

use Gloudemans\Shoppingcart\Facades\Cart;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
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
            $total_amount = 0;
            foreach (Cart::instance('sale')->content() as $item) {
                $total_amount += $item->options->sub_total;
            }
    
            $paid_amount = $request->paid_amount ?? 0;
            $due_amount = $total_amount - $paid_amount;
    
            $payment_status = $due_amount == $total_amount ? 'Unpaid' :
                              ($due_amount > 0 ? 'Partial' : 'Paid');
    
            $branch_id = session('branch_id');
            if (!$branch_id) {
                throw new \Exception("Branch belum dipilih.");
            }
    
            $customer_id = $request->customer_id ?? 1;
    
            $sale = Sale::create([
                'branch_id'           => $branch_id,
                'date'                => $request->date,
                'reference'           => $request->reference,
                'customer_id'         => $customer_id,
                'customer_name'       => optional(Customer::find($customer_id))->customer_name,
                'tax_percentage'      => $request->tax_percentage ?? 0,
                'discount_percentage' => $request->discount_percentage ?? 0,
                'paid_amount'         => $paid_amount * 100,
                'total_amount'        => $total_amount * 100,
                'due_amount'          => $due_amount * 100,
                'payment_status'      => $payment_status,
                'payment_method'      => $request->payment_method,
                'note'                => $request->note,
                'tax_amount'          => Cart::instance('sale')->tax() * 100,
                'discount_amount'     => Cart::instance('sale')->discount() * 100,
            ]);
    
            foreach (Cart::instance('sale')->content() as $item) {
                SaleDetails::create([
                    'sale_id'                 => $sale->id,
                    'product_id'              => $item->id,
                    'product_name'            => $item->name,
                    'product_code'            => $item->options->code,
                    'quantity'                => $item->qty,
                    'price'                   => $item->price * 100,
                    'unit_price'              => $item->options->unit_price * 100,
                    'sub_total'               => $item->options->sub_total * 100,
                    'product_discount_amount' => $item->options->product_discount * 100,
                    'product_discount_type'   => $item->options->product_discount_type,
                    'product_tax_amount'      => $item->options->product_tax * 100,
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
    
            if ($sale->paid_amount > 0) {
                SalePayment::create([
                    'date'           => $request->date,
                    'reference'      => 'INV/' . $sale->reference,
                    'amount'         => $sale->paid_amount,
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
                'price' => $detail->price / 100,
                'weight'=> 1,
                'options' => [
                    'code'                  => $detail->product_code,
                    'unit_price'            => $detail->unit_price / 100,
                    'sub_total'             => $detail->sub_total / 100,
                    'product_discount'      => $detail->product_discount_amount / 100,
                    'product_discount_type' => $detail->product_discount_type,
                    'product_tax'           => $detail->product_tax_amount / 100,
                    'stock'                 => optional(Product::find($detail->product_id))->product_quantity,
                ]
            ]);
        }

        return view('sale::edit', compact('sale'));
    }

    public function update(UpdateSaleRequest $request, Sale $sale)
    {
        DB::transaction(function () use ($request, $sale) {
            $due_amount = $request->total_amount - $request->paid_amount;
            $payment_status = $due_amount == $request->total_amount ? 'Unpaid' :
                              ($due_amount > 0 ? 'Partial' : 'Paid');

            foreach ($sale->saleDetails as $detail) {
                if (in_array($sale->status, ['Shipped', 'Completed'])) {
                    $product = Product::find($detail->product_id);
                    if ($product) {
                        $product->increment('product_quantity', $detail->quantity);
                    }
                }
                $detail->delete();
            }

            $sale->update([
                'date'                => $request->date,
                'reference'           => $request->reference,
                'customer_id'         => $request->customer_id,
                'customer_name'       => optional(Customer::find($request->customer_id))->customer_name,
                'tax_percentage'      => $request->tax_percentage ?? 0,
                'discount_percentage' => $request->discount_percentage ?? 0,
                'shipping_amount'     => ($request->shipping_amount ?? 0) * 100,
                'paid_amount'         => ($request->paid_amount ?? 0) * 100,
                'total_amount'        => ($request->total_amount ?? 0) * 100,
                'due_amount'          => ($due_amount ?? 0) * 100,
                'payment_status'      => $payment_status,
                'payment_method'      => $request->payment_method,
                'note'                => $request->note,
                'tax_amount'          => Cart::instance('sale')->tax() * 100,
                'discount_amount'     => Cart::instance('sale')->discount() * 100,
            ]);

            foreach (Cart::instance('sale')->content() as $item) {
                SaleDetails::create([
                    'sale_id'                 => $sale->id,
                    'product_id'              => $item->id,
                    'product_name'            => $item->name,
                    'product_code'            => $item->options->code,
                    'quantity'                => $item->qty,
                    'price'                   => $item->price * 100,
                    'unit_price'              => $item->options->unit_price * 100,
                    'sub_total'               => $item->options->sub_total * 100,
                    'product_discount_amount' => $item->options->product_discount * 100,
                    'product_discount_type'   => $item->options->product_discount_type,
                    'product_tax_amount'      => $item->options->product_tax * 100,
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
        });

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
            ->orderBy('created_at') // FIFO
            ->get();

        $allocation = [];
        $remaining = $required_qty;

        foreach ($batches as $batch) {
            if ($remaining <= 0) break;

            $used_qty = min($batch->qty, $remaining);
            $allocation[] = [
                'batch_id'   => $batch->id,
                'qty'        => $used_qty,
                'unit_price' => $batch->price,
            ];

            DB::table('product_batches')
                ->where('id', $batch->id)
                ->decrement('qty', $used_qty);

            $remaining -= $used_qty;
        }

        if ($remaining > 0) {
            throw new \Exception("Stok produk tidak cukup di cabang saat ini.");
        }

        return $allocation;
    }
}
