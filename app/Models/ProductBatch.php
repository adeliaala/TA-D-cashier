<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ProductBatch extends Model
{
    protected $fillable = [
        'product_id',
        'branch_id',
        'batch_code',
        'quantity',
        'purchase_price',
        'expired_date',
    ];

    protected $casts = [
        'expired_date' => 'date',
        'purchase_price' => 'decimal:2',
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
            ->where('quantity', '>', 0)
            ->orderBy('expired_date', 'asc')
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

            $deductAmount = min($remainingQuantity, $batch->quantity);
            $batch->quantity -= $deductAmount;
            $batch->save();

            $usedBatches[] = [
                'batch_id' => $batch->id,
                'quantity' => $deductAmount,
                'purchase_price' => $batch->purchase_price,
                'expired_date' => $batch->expired_date
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
        // Generate batch code if not provided
        if (!isset($data['batch_code'])) {
            $data['batch_code'] = self::generateBatchCode();
        }

        return self::create($data);
    }
} 