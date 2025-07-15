<?php

namespace Modules\Adjustment\Http\Controllers;

use Modules\Adjustment\DataTables\AdjustmentsDataTable;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Modules\Adjustment\Entities\AdjustedProduct;
use Modules\Adjustment\Entities\Adjustment;
use Modules\Product\Entities\Product;

class AdjustmentController extends Controller
{

    public function index(AdjustmentsDataTable $dataTable) {
        abort_if(Gate::denies('access_adjustments'), 403);

        return $dataTable->render('adjustment::index');
    }


    public function create() {
        abort_if(Gate::denies('create_adjustments'), 403);

        return view('adjustment::create');
    }


    public function store(Request $request) {
        abort_if(Gate::denies('create_adjustments'), 403);

        $request->validate([
            'reference'   => 'required|string|max:255',
            'date'        => 'required|date',
            'note'        => 'nullable|string|max:1000',
            'product_batch_ids' => 'required',
            'quantities'  => 'required',
            'types'       => 'required'
        ]);

        DB::transaction(function () use ($request) {
            $adjustment = Adjustment::create([
                'branches_id' => session('branch_id'),
                'date' => $request->date,
                'note' => $request->note
            ]);

            foreach ($request->product_ids as $key => $id) {
                AdjustedProduct::create([
                    'adjustment_id' => $adjustment->id,
                    'product_id'    => $id,
                    'product_batch_id' => $request->product_batch_ids[$key],
                    'quantity'      => $request->quantities[$key],
                    'type'          => $request->types[$key]
                ]);
            
                $batchId = $request->product_batch_ids[$key]; // batch yang dipilih oleh user
                $batch = DB::table('product_batches')->where('id', $batchId)->lockForUpdate()->first();
            
                if ($batch) {
                    if ($request->types[$key] == 'add') {
                        DB::table('product_batches')->where('id', $batchId)->update([
                            'qty' => $batch->qty + $request->quantities[$key]
                        ]);
                    } elseif ($request->types[$key] == 'sub') {
                        $newQty = $batch->qty - $request->quantities[$key];
                        if ($newQty < 0) {
                            throw new \Exception("Stock untuk batch {$batch->batch_code} tidak mencukupi.");
                        }
            
                        DB::table('product_batches')->where('id', $batchId)->update([
                            'qty' => $newQty
                        ]);
                    }
                } else {
                    throw new \Exception("Batch tidak ditemukan.");
                }
            }
            
        });

        toast('Adjustment Created!', 'success');

        return redirect()->route('adjustments.index');
    }


    public function show(Adjustment $adjustment) {
        abort_if(Gate::denies('show_adjustments'), 403);

        return view('adjustment::show', compact('adjustment'));
    }


    public function edit(Adjustment $adjustment) {
        abort_if(Gate::denies('edit_adjustments'), 403);

        return view('adjustment::edit', compact('adjustment'));
    }


    public function update(Request $request, Adjustment $adjustment) {
        abort_if(Gate::denies('edit_adjustments'), 403);

        $request->validate([
            'reference'   => 'required|string|max:255',
            'date'        => 'required|date',
            'note'        => 'nullable|string|max:1000',
            'product_ids' => 'required',
            'quantities'  => 'required',
            'types'       => 'required'
        ]);

        DB::transaction(function () use ($request, $adjustment) {
            $adjustment->update([
                'reference' => $request->reference,
                'date'      => $request->date,
                'note'      => $request->note
            ]);

            foreach ($adjustment->adjustedProducts as $adjustedProduct) {
                $product = Product::findOrFail($adjustedProduct->product->id);

                if ($adjustedProduct->type == 'add') {
                    $product->update([
                        'product_quantity' => $product->product_quantity - $adjustedProduct->quantity
                    ]);
                } elseif ($adjustedProduct->type == 'sub') {
                    $product->update([
                        'product_quantity' => $product->product_quantity + $adjustedProduct->quantity
                    ]);
                }

                $adjustedProduct->delete();
            }

            foreach ($request->product_ids as $key => $id) {
                AdjustedProduct::create([
                    'adjustment_id' => $adjustment->id,
                    'product_id'    => $id,
                    'product_batch_id' => $request->batch_ids[$key],
                    'quantity'      => $request->quantities[$key],
                    'type'          => $request->types[$key]
                ]);

                $product = Product::findOrFail($id);

                if ($request->types[$key] == 'add') {
                    $product->update([
                        'product_quantity' => $product->product_quantity + $request->quantities[$key]
                    ]);
                } elseif ($request->types[$key] == 'sub') {
                    $product->update([
                        'product_quantity' => $product->product_quantity - $request->quantities[$key]
                    ]);
                }
            }
        });

        toast('Adjustment Updated!', 'info');

        return redirect()->route('adjustments.index');
    }


    public function destroy(Adjustment $adjustment) {
        abort_if(Gate::denies('delete_adjustments'), 403);

        $adjustment->delete();

        toast('Adjustment Deleted!', 'warning');

        return redirect()->route('adjustments.index');
    }

    public function quickAdjustment(Request $request)
{
    abort_if(Gate::denies('create_adjustments'), 403);

    $request->validate([
        'product_id' => 'required|exists:products,id',
        'product_batch_id' => 'required|exists:product_batches,id',
        'quantity' => 'required|numeric|min:1',
        'type' => 'required|in:add,sub',
        'note' => 'nullable|string|max:1000',
    ]);

    DB::transaction(function () use ($request) {
        $adjustment = Adjustment::create([
            'branches_id' => session('branch_id'),
            'date' => now(),
            'note' => $request->note ?? 'Auto adjustment dari dashboard'
        ]);

        AdjustedProduct::create([
            'adjustment_id' => $adjustment->id,
            'product_id' => $request->product_id,
            'product_batch_id' => $request->product_batch_id,
            'quantity' => $request->quantity,
            'type' => $request->type
        ]);

        $batch = DB::table('product_batches')->where('id', $request->product_batch_id)->lockForUpdate()->first();

        if ($batch) {
            $newQty = $batch->qty;

            if ($request->type == 'add') {
                $newQty += $request->quantity;
            } elseif ($request->type == 'sub') {
                $newQty -= $request->quantity;
                if ($newQty < 0) {
                    throw new \Exception("Stok tidak cukup untuk batch {$batch->batch_code}.");
                }
            }

            DB::table('product_batches')->where('id', $request->product_batch_id)->update([
                'qty' => $newQty
            ]);
        }
    });

    toast('Stok berhasil dikurangi melalui dashboard.', 'success');
    return redirect()->back();
}

}
