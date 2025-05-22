<?php

namespace Modules\StockTransfer\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Carbon;
use App\Models\Branch;
use App\Models\ProductBatch;

class StockTransfer extends Model
{
    use HasFactory;

    protected $fillable = [
        'reference_no',
        'source_branch_id',
        'destination_branch_id',
        'transfer_date',
        'note',
        'status',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'transfer_date' => 'date'
    ];

    public function sourceBranch()
    {
        return $this->belongsTo(Branch::class, 'source_branch_id');
    }

    public function destinationBranch()
    {
        return $this->belongsTo(Branch::class, 'destination_branch_id');
    }

    public function items()
    {
        return $this->hasMany(StockTransferItem::class);
    }

    public function getTransferDateAttribute($value)
    {
        return Carbon::parse($value)->format('d M, Y');
    }

    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $number = StockTransfer::max('id') + 1;
            $model->reference_no = 'TRF-' . date('Ymd') . '-' . str_pad($number, 4, '0', STR_PAD_LEFT);
        });
    }
} 