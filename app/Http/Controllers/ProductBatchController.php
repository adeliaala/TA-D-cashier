<?php

namespace App\Http\Controllers;

use App\Models\ProductBatch;
use App\Http\Requests\ProductBatchRequest;
use Illuminate\Http\Request;

class ProductBatchController extends Controller
{
    public function store(ProductBatchRequest $request)
    {
        try {
            $batch = ProductBatch::addStock($request->validated());
            return response()->json([
                'message' => 'Stock added successfully',
                'data' => $batch
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to add stock',
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

    public function deductStock(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'branch_id' => 'required|exists:branches,id',
            'quantity' => 'required|integer|min:1'
        ]);

        try {
            ProductBatch::deductStock(
                $request->product_id,
                $request->branch_id,
                $request->quantity
            );

            return response()->json([
                'message' => 'Stock deducted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to deduct stock',
                'error' => $e->getMessage()
            ], 500);
        }
    }
} 