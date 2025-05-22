<?php

namespace Modules\StockTransfer\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\StockTransfer\Entities\StockTransfer;
use Modules\StockTransfer\Entities\StockTransferItem;
use Modules\StockTransfer\Http\Requests\StockTransferRequest;
use App\Models\ProductBatch;
use Modules\Branch\Entities\Branch;
use Modules\Product\Entities\Product;

class StockTransferController extends Controller
{
    public function index()
    {
        $stockTransfers = StockTransfer::with(['sourceBranch', 'destinationBranch'])
            ->latest()
            ->paginate(10);

        return view('stocktransfer::index', compact('stockTransfers'));
    }

    public function create()
    {
        $branches = Branch::all();
        $products = Product::all();

        return view('stocktransfer::create', compact('branches', 'products'));
    }

    public function store(StockTransferRequest $request)
    {
        try {
            DB::beginTransaction();

            // Create stock transfer record
            $transfer = StockTransfer::create([
                'source_branch_id' => $request->source_branch_id,
                'destination_branch_id' => $request->destination_branch_id,
                'transfer_date' => $request->transfer_date,
                'note' => $request->note,
                'status' => 'pending',
                'created_by' => auth()->id(),
            ]);

            // Process each transfer item
            foreach ($request->items as $item) {
                // Get source batch
                $sourceBatch = ProductBatch::findOrFail($item['product_batch_id']);

                // Step 1: Deduct quantity from source branch
                $sourceBatch->quantity -= $item['quantity'];
                $sourceBatch->save();

                // Step 2: Create new batch in destination branch
                ProductBatch::create([
                    'product_id' => $sourceBatch->product_id,
                    'branch_id' => $request->destination_branch_id,
                    'batch_code' => $sourceBatch->batch_code,
                    'quantity' => $item['quantity'],
                    'purchase_price' => $sourceBatch->purchase_price,
                    'expired_date' => $sourceBatch->expired_date,
                    'created_by' => auth()->id(),
                    'updated_by' => auth()->id()
                ]);

                // Step 3: Create transfer item record
                StockTransferItem::create([
                    'stock_transfer_id' => $transfer->id,
                    'product_id' => $sourceBatch->product_id,
                    'product_batch_id' => $sourceBatch->id,
                    'quantity' => $item['quantity']
                ]);
            }

            // Update transfer status to completed
            $transfer->update([
                'status' => 'completed',
                'updated_by' => auth()->id()
            ]);

            DB::commit();

            return redirect()
                ->route('stock-transfers.show', $transfer)
                ->with('success', 'Stock transfer created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()
                ->back()
                ->with('error', 'Error creating stock transfer: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function show(StockTransfer $stockTransfer)
    {
        $stockTransfer->load(['sourceBranch', 'destinationBranch', 'items.product', 'items.productBatch']);
        return view('stocktransfer::show', compact('stockTransfer'));
    }

    public function getBatches($productId, $branchId)
    {
        $batches = ProductBatch::where('product_id', $productId)
            ->where('branch_id', $branchId)
            ->where('quantity', '>', 0)
            ->get(['id', 'batch_code', 'quantity']);

        return response()->json($batches);
    }
} 