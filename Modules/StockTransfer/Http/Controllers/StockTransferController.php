<?php

namespace Modules\StockTransfer\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\StockTransfer\Entities\StockTransfer;
use Modules\StockTransfer\Entities\StockTransferItem;
use Modules\Branch\Entities\Branch;
use Modules\Product\Entities\Product;
use App\Models\ProductBatch;

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

    public function store(Request $request)
    {
        $data = $request->validate([
            'from_branch_id' => 'required|exists:branches,id',
            'to_branch_id' => 'required|exists:branches,id|different:from_branch_id',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.product_batch_id' => 'required|exists:product_batches,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        try {
            DB::beginTransaction();

            $transfer = StockTransfer::create([
                'source_branch_id' => $data['from_branch_id'],
                'destination_branch_id' => $data['to_branch_id'],
                'transfer_date' => now(),
                'status' => 'completed',
                'created_by' => auth()->id(),
            ]);

            foreach ($data['items'] as $item) {
                $sourceBatch = ProductBatch::where('id', $item['product_batch_id'])
                    ->where('branch_id', $data['from_branch_id'])
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($sourceBatch->qty < $item['quantity']) {
                    throw new \Exception("Stok tidak mencukupi untuk batch {$sourceBatch->batch_code}");
                }

                // Kurangi stok dari batch asal
                $sourceBatch->qty -= $item['quantity'];
                $sourceBatch->save();

                // Tambahkan ke batch cabang tujuan (dengan batch_code sama)
                $targetBatch = ProductBatch::firstOrCreate(
                    [
                        'product_id' => $item['product_id'],
                        'branch_id' => $data['to_branch_id'],
                        'batch_code' => $sourceBatch->batch_code,
                    ],
                    [
                        'qty' => 0,
                        'unit_price' => $sourceBatch->unit_price,
                        'price' => $sourceBatch->price,
                        'exp_date' => $sourceBatch->exp_date,
                        'created_by' => auth()->id(),
                    ]
                );

                $targetBatch->qty += $item['quantity'];
                $targetBatch->updated_by = auth()->id();
                $targetBatch->save();

                // Simpan ke tabel stock_transfer_items
                $transfer->items()->create([
                    'product_id' => $item['product_id'],
                    'batch_id' => $sourceBatch->id,
                    'qty' => $item['quantity'],
                    'unit_price' => $sourceBatch->unit_price,
                    'price' => $sourceBatch->price,
                ]);
            }

            DB::commit();
            return redirect()->route('stock-transfers.index')->with('success', 'Transfer stok berhasil.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal transfer stok: ' . $e->getMessage())->withInput();
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
            ->where('qty', '>', 0)
            ->get(['id', 'batch_code', 'qty']);

        return response()->json($batches);
    }
}
