<?php

namespace Modules\StockTransfer\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\StockTransfer\Entities\StockTransfer;
use Modules\StockTransfer\Entities\StockTransferItem;
use Modules\Branch\Entities\Branch;
use Modules\Product\Entities\Product;
use App\Models\ProductBatch;
use Modules\StockTransfer\Http\Requests\StockTransferRequest;

class StockTransferController extends Controller
{
    public function index()
    {
        $stockTransfers = StockTransfer::with(['sourceBranch', 'destinationBranch', 'creator'])
            ->latest()
            ->paginate(10);

        return view('stocktransfer::index', [
            'transfers' => $stockTransfers,
            'statuses' => ['pending', 'completed', 'cancelled']
        ]);
    }

    public function create()
    {
        return view('stocktransfer::create', [
            'branches' => Branch::all(), // Semua branch tanpa filter active
            'products' => Product::whereHas('batches', function($q) {
                $q->where('qty', '>', 0); // Hanya produk dengan stok tersedia
            })->with(['batches' => function($q) {
                $q->where('qty', '>', 0); // Hanya ambil batch dengan qty > 0
            }])->get()
        ]);
    }

    public function store(Request $request)
    {
        Log::info('Stock Transfer Request Data:', $request->all());

        $validated = $request->validate([
            'source_branch_id' => 'required|exists:branches,id',
            'destination_branch_id' => 'required|exists:branches,id|different:source_branch_id',
            'product_ids' => 'required|array|min:1',
            'product_batch_ids' => 'required|array|min:1',
            'quantities' => 'required|array|min:1',
            'product_ids.*' => 'required|exists:products,id',
            'product_batch_ids.*' => 'required|exists:product_batches,id',
            'quantities.*' => 'required|integer|min:1',
        ]);

        try {
            DB::beginTransaction();

            $transfer = StockTransfer::create([
                'source_branch_id' => $validated['source_branch_id'],
                'destination_branch_id' => $validated['destination_branch_id'],
                'transfer_date' => $request->transfer_date ?? now(),
                'status' => $request->status ?? 'completed',
                'note' => $request->note,
                'created_by' => auth()->id(),
            ]);

            foreach ($request->product_ids as $index => $productId) {
                $sourceBatch = ProductBatch::where('id', $request->product_batch_ids[$index])
                    ->where('branch_id', $validated['source_branch_id'])
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($sourceBatch->qty < $request->quantities[$index]) {
                    throw new \Exception("Stok tidak mencukupi untuk batch {$sourceBatch->batch_code}");
                }

                // Kurangi stok di batch sumber
                $sourceBatch->decrement('qty', $request->quantities[$index]);

                // Cari atau buat batch baru di cabang tujuan
                $targetBatch = ProductBatch::firstOrNew([
                    'product_id' => $productId,
                    'branch_id' => $validated['destination_branch_id'],
                    'batch_code' => $sourceBatch->batch_code,
                ]);

                // Update atau set nilai batch tujuan
                $targetBatch->fill([
                    'qty' => $targetBatch->exists ? $targetBatch->qty + $request->quantities[$index] : $request->quantities[$index],
                    'unit_price' => $sourceBatch->unit_price,
                    'price' => $sourceBatch->price,
                    'exp_date' => $sourceBatch->exp_date,
                    'purchase_id' => $sourceBatch->purchase_id,
                    'created_by' => auth()->id(),
                    'updated_by' => auth()->id(),
                ])->save();

                // Simpan detail transfer
                StockTransferItem::create([
                    'stock_transfer_id' => $transfer->id,
                    'product_id' => $productId,
                    'product_batch_id' => $sourceBatch->id,
                    'destination_batch_id' => $targetBatch->id,
                    'qty' => $request->quantities[$index],
                    'unit_price' => $sourceBatch->unit_price,
                    'price' => $sourceBatch->price,
                ]);
            }

            DB::commit();
            return redirect()->route('stocktransfers.index')->with('success', 'Transfer stok berhasil.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Stock transfer failed:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return back()->with('error', 'Gagal transfer stok: ' . $e->getMessage())->withInput();
        }
    }

    public function show(StockTransfer $stockTransfer)
    {
        $stockTransfer->load([
            'sourceBranch',
            'destinationBranch',
            'creator',
            'approver',
            'items.product',
            'items.productBatch',
            'items.destinationBatch'
        ]);

        return view('stocktransfer::show', [
            'transfer' => $stockTransfer,
            'canApprove' => $this->canApprove($stockTransfer),
            'canCancel' => $this->canCancel($stockTransfer)
        ]);
    }

    public function approve(StockTransfer $stockTransfer)
    {
        if (!$this->canApprove($stockTransfer)) {
            return back()->with('error', 'This transfer cannot be approved');
        }

        try {
            DB::beginTransaction();

            $stockTransfer->update([
                'status' => 'completed',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ]);

            DB::commit();

            return back()->with('success', 'Stock transfer approved successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            logger()->error('Transfer approval failed: ' . $e->getMessage());
            
            return back()->with('error', 'Failed to approve transfer');
        }
    }

    public function cancel(StockTransfer $stockTransfer)
    {
        if (!$this->canCancel($stockTransfer)) {
            return back()->with('error', 'This transfer cannot be cancelled');
        }

        try {
            DB::beginTransaction();

            $this->reverseTransfer($stockTransfer);

            $stockTransfer->update([
                'status' => 'cancelled',
                'cancelled_by' => auth()->id(),
                'cancelled_at' => now(),
            ]);

            DB::commit();

            return back()->with('success', 'Stock transfer cancelled successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            logger()->error('Transfer cancellation failed: ' . $e->getMessage());
            
            return back()->with('error', 'Failed to cancel transfer');
        }
    }

    public function getBatches($productId, $branchId)
    {
        $batches = ProductBatch::where('product_id', $productId)
            ->where('branch_id', $branchId)
            ->where('qty', '>', 0)
            ->get(['id', 'batch_code', 'qty', 'exp_date'])
            ->map(function ($batch) {
                return [
                    'id' => $batch->id,
                    'text' => sprintf(
                        "%s (Stock: %d, Exp: %s)",
                        $batch->batch_code,
                        $batch->qty,
                        $batch->exp_date ? $batch->exp_date->format('d/m/Y') : 'N/A'
                    )
                ];
            });

        return response()->json($batches);
    }

    protected function processTransferItem(StockTransfer $transfer, array $item)
    {
        $sourceBatch = ProductBatch::where('id', $item['product_batch_id'])
            ->where('product_id', $item['product_id'])
            ->where('branch_id', $transfer->source_branch_id)
            ->lockForUpdate()
            ->firstOrFail();

        // Deduct from source
        $sourceBatch->decrement('qty', $item['qty']);

        // Create or update destination batch
        $destinationBatch = ProductBatch::firstOrNew([
            'product_id' => $item['product_id'],
            'branch_id' => $transfer->destination_branch_id,
            'batch_code' => $sourceBatch->batch_code,
            'unit_price' => $sourceBatch->unit_price,
            'exp_date' => $sourceBatch->exp_date,
        ]);

        if ($destinationBatch->exists) {
            $destinationBatch->increment('qty', $item['qty']);
        } else {
            $destinationBatch->fill([
                'qty' => $item['qty'],
                'price' => $sourceBatch->price,
                'purchase_id' => $sourceBatch->purchase_id,
                'stock_transfer_id' => $transfer->id,
                'created_by' => auth()->id(),
            ])->save();
        }

        // Create transfer item
        StockTransferItem::create([
            'stock_transfer_id' => $transfer->id,
            'product_id' => $item['product_id'],
            'product_batch_id' => $sourceBatch->id,
            'destination_batch_id' => $destinationBatch->id,
            'qty' => $item['qty'],
            'unit_price' => $sourceBatch->unit_price,
            'price' => $sourceBatch->price,
        ]);
    }

    protected function reverseTransfer(StockTransfer $transfer)
    {
        foreach ($transfer->items as $item) {
            // Return stock to source
            ProductBatch::where('id', $item->product_batch_id)
                ->increment('qty', $item->qty);

            // Remove from destination
            $destinationBatch = ProductBatch::find($item->destination_batch_id);
            if ($destinationBatch) {
                if ($destinationBatch->qty > $item->qty) {
                    $destinationBatch->decrement('qty', $item->qty);
                } else {
                    $destinationBatch->delete();
                }
            }
        }
    }

    protected function canApprove(StockTransfer $transfer): bool
    {
        return $transfer->status === 'pending' 
            && auth()->user()->can('approve', $transfer);
    }

    protected function canCancel(StockTransfer $transfer): bool
    {
        return $transfer->status === 'pending' 
            && auth()->user()->can('cancel', $transfer);
    }
}