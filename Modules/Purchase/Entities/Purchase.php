<?php

namespace Modules\Purchase\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Log;

class Purchase extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_id',
        'user_id',
        'date',
        'reference_no',
        'supplier_id',
        'discount_percentage',
        'discount',
        'total',
        'paid_amount',
        'due_amount',
        'payment_status',
        'payment_method',
        'note',
    ];

    protected $casts = [
        'date' => 'date',
        'discount_percentage' => 'float',
        'discount' => 'decimal:2',
        'total' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'due_amount' => 'decimal:2'
    ];

    public function purchaseDetails(): HasMany
    {
        return $this->hasMany(PurchaseDetail::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo('Modules\People\Entities\Supplier', 'supplier_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo('App\Models\User');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo('App\Models\Branch');
    }

    public function purchasePayments() {
        return $this->hasMany(PurchasePayment::class, 'purchase_id', 'id');
    }

    public static function boot() {
        parent::boot();

        static::creating(function ($model) {
            $number = Purchase::max('id') + 1;
            $model->reference_no = make_reference_id('PR', $number);
        });
        
        // Log when model is retrieved
        static::retrieved(function ($model) {
            Log::info('Purchase Model Retrieved', [
                'id' => $model->id,
                'reference_no' => $model->reference_no,
                'supplier_id' => $model->supplier_id,
                'payment_status' => $model->payment_status
            ]);
        });
    }

    public function scopeCompleted($query) {
        return $query->where('payment_status', 'Paid');
    }

    public function getDiscountAttribute($value) {
        return $value / 100;
    }

    public function getTotalAttribute($value) {
        return $value / 100;
    }

    public function getPaidAmountAttribute($value) {
        return $value / 100;
    }

    public function getDueAmountAttribute($value) {
        return $value / 100;
    }

    public function setDiscountAttribute($value) {
        $this->attributes['discount'] = $value * 100;
    }

    public function setTotalAttribute($value) {
        $this->attributes['total'] = $value * 100;
    }

    public function setPaidAmountAttribute($value) {
        $this->attributes['paid_amount'] = $value * 100;
    }

    public function setDueAmountAttribute($value) {
        $this->attributes['due_amount'] = $value * 100;
    }
}
