<?php

namespace Modules\Purchase\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class ProductBatch extends Model
{
    protected $fillable = [
        'purchase_id',
        'product_id',
        'branch_id',
        'batch_code',
        'quantity',
        'purchase_price',
        'expired_date',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'quantity' => 'integer',
        'purchase_price' => 'decimal:2',
        'expired_date' => 'date'
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo('Modules\Product\Entities\Product');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo('App\Models\Branch');
    }

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    public static function addStock(array $data)
    {
        return DB::transaction(function () use ($data) {
            // Check if batch with same product, branch, and expired_date exists
            $batch = self::where('product_id', $data['product_id'])
                ->where('branch_id', $data['branch_id'])
                ->where('expired_date', $data['expired_date'])
                ->first();

            if ($batch) {
                // Update existing batch
                $batch->update([
                    'quantity' => $batch->quantity + $data['quantity'],
                    'purchase_price' => $data['purchase_price'], // Update price to latest
                    'updated_by' => auth()->user()->name
                ]);
                return $batch;
            }

            // Create new batch
            return self::create([
                'product_id' => $data['product_id'],
                'branch_id' => $data['branch_id'],
                'batch_code' => $data['batch_code'] ?? null,
                'quantity' => $data['quantity'],
                'purchase_price' => $data['purchase_price'],
                'expired_date' => $data['expired_date'],
                'purchase_id' => $data['purchase_id'],
                'created_by' => auth()->user()->name,
                'updated_by' => auth()->user()->name
            ]);
        });
    }

    public static function getAvailableStock($productId, $branchId)
    {
        return self::where('product_id', $productId)
            ->where('branch_id', $branchId)
            ->where(function ($query) {
                $query->whereNull('expired_date')
                    ->orWhere('expired_date', '>', now());
            })
            ->sum('quantity');
    }
} 