<?php

namespace Modules\Sale\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Sale extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_id', // ← TAMBAHKAN INI
        'date',
        'reference',
        'customer_id',
        'customer_name',
        'discount_percentage',
        'discount_amount',
        'total_amount',
        'paid_amount',
        'due_amount',
        'payment_status',
        'payment_method',
        'note'
    ];
    

    public function saleDetails() {
        return $this->hasMany(SaleDetails::class, 'sale_id', 'id');
    }

    public function salePayments() {
        return $this->hasMany(SalePayment::class, 'sale_id', 'id');
    }

    public static function boot() {
        parent::boot();

        static::creating(function ($model) {
            $number = Sale::max('id') + 1;
            $model->reference = make_reference_id('SL', $number);
        });
    }

    public function scopeCompleted($query) {
        return $query->where('status', 'Completed');
    }

    public function getPaidAmountAttribute($value) {
        return $value ;
    }

    public function getTotalAmountAttribute($value) {
        return $value ;
    }

    public function getDueAmountAttribute($value) {
        return $value ;
    }

    public function getDiscountAmountAttribute($value) {
        return $value ;
    }
    public function branch()
    {
        return $this->belongsTo(\Modules\Branch\Entities\Branch::class);
    }
    
    public function customer()
    {
        return $this->belongsTo(\Modules\People\Entities\Customer::class);
    }
}
