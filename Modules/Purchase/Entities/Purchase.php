<?php

namespace Modules\Purchase\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Purchase extends Model
{
    use HasFactory;

    protected $fillable = [
        'reference_no',
        'supplier_id',
        'date',
        'discount_amount',
        'payment_method',
        'paid_amount',
        'total_amount',
        'due_amount',
        'payment_status',
        'user_id',
        'branch_id',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'date' => 'date',
        'paid_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'due_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2'
    ];

    public function purchaseDetails(): HasMany
    {
        return $this->hasMany(PurchaseDetail::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo('Modules\People\Entities\Supplier');
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
    }

    public function scopeCompleted($query) {
        return $query->where('payment_status', 'Paid');
    }

    public function getPaidAmountAttribute($value) {
        return $value / 100;
    }

    public function getTotalAmountAttribute($value) {
        return $value / 100;
    }

    public function getDueAmountAttribute($value) {
        return $value / 100;
    }

    public function getDiscountAmountAttribute($value) {
        return $value / 100;
    }
}
