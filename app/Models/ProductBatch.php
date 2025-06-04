<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Modules\Product\Entities\Product;
use App\Models\Branch;

class ProductBatch extends Model
{
    protected $fillable = [
        'product_id',
        'branch_id',
        'batch_code',
        'qty',
        'unit_price',
        'price',
        'exp_date',
        'purchase_id',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'exp_date' => 'date',
        'unit_price' => 'decimal:2',
        'price' => 'decimal:2',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Generate a unique batch code
     */
    public static function generateBatchCode(): string
    {
        return 'BATCH-' . strtoupper(Str::random(8));
    }

    /**
     * Get available stock for a product in a specific branch using FEFO/FIFO
     */
    public static function getAvailableStock(int $productId, int $branchId)
    {
        return self::where('product_id', $productId)
            ->where('branch_id', $branchId)
            ->where('qty', '>', 0)
            ->orderBy('exp_date', 'asc')
            ->orderBy('created_at', 'asc')
            ->get();
    }

    /**
     * Deduct stock from batches using FEFO/FIFO
     */
    public static function deductStock(int $productId, int $branchId, int $quantity)
    {
        $remainingQuantity = $quantity;
        $batches = self::getAvailableStock($productId, $branchId);
        $usedBatches = [];

        foreach ($batches as $batch) {
            if ($remainingQuantity <= 0) break;

            $deductAmount = min($remainingQuantity, $batch->qty);
            $batch->qty -= $deductAmount;
            $batch->save();

            $usedBatches[] = [
                'batch_id' => $batch->id,
                'qty' => $deductAmount,
                'unit_price' => $batch->unit_price,
                'price' => $batch->price,
                'exp_date' => $batch->exp_date,
            ];

            $remainingQuantity -= $deductAmount;
        }

        if ($remainingQuantity > 0) {
            throw new \Exception('Insufficient stock available');
        }

        return $usedBatches;
    }

    /**
     * Add new stock to product batches
     */
    public static function addStock(array $data)
    {
        // Rename expired_date to exp_date if it exists
        if (isset($data['expired_date'])) {
            $data['exp_date'] = $data['expired_date'];
            unset($data['expired_date']);
        }

        // Rename purchase_price to unit_price if it exists
        if (isset($data['purchase_price'])) {
            $data['unit_price'] = $data['purchase_price'];
            unset($data['purchase_price']);
        }

        // Generate batch code if not provided
        if (empty($data['batch_code'])) {
            $data['batch_code'] = self::generateBatchCode();
        }

        return self::create($data);
    }

    /**
     * Get FIFO batch unit price (average from earliest batches)
     */
    public static function getFifoBatchPrice(int $productId, int $branchId, int $qty = 1): float
    {
        $batches = self::where('product_id', $productId)
            ->where('branch_id', $branchId)
            ->where('qty', '>', 0)
            ->orderBy('exp_date', 'asc')
            ->orderBy('created_at', 'asc')
            ->get();

        $remaining = $qty;
        $totalPrice = 0;

        foreach ($batches as $batch) {
            if ($remaining <= 0) break;

            $take = min($remaining, $batch->qty);
            $totalPrice += $take * $batch->unit_price;
            $remaining -= $take;
        }

        if ($remaining > 0) {
            throw new \Exception('Stok tidak cukup untuk menghitung harga FIFO');
        }

        return round($totalPrice / $qty, 2);
    }

    public static function transferToBranch(int $sourceBatchId, int $destinationBranchId, int $qty, int $userId): ProductBatch
    {
        $sourceBatch = self::lockForUpdate()->findOrFail($sourceBatchId);

        if ($sourceBatch->quantity < $qty) {
            throw new \Exception("Stok tidak cukup pada batch {$sourceBatch->batch_code}");
        }

        // Kurangi stok dari batch asal
        $sourceBatch->quantity -= $qty;
        $sourceBatch->save();

        // Buat batch baru di cabang tujuan
        return self::create([
            'product_id' => $sourceBatch->product_id,
            'batch_code' => $sourceBatch->batch_code,
            'unit_price' => $sourceBatch->unit_price,
            'price' => $sourceBatch->price,
            'expired_date' => $sourceBatch->expired_date,
            'purchase_id' => $sourceBatch->purchase_id,
            'branch_id' => $destinationBranchId,
            'quantity' => $qty,
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);
    }
}
