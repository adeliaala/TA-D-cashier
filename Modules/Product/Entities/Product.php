<?php

namespace Modules\Product\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use App\Models\ProductBatch;

class Product extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    protected $guarded = [];

    protected $with = ['media'];

    // Append product_quantity ke output model
    protected $appends = ['product_quantity'];

    /**
     * Relasi ke kategori
     */
    public function category() {
        return $this->belongsTo(Category::class, 'category_id');
    }

    /**
     * Relasi ke batches (stok per cabang)
     */
    public function batches() {
        return $this->hasMany(ProductBatch::class, 'product_id');
    }

    /**
     * Getter jumlah stok produk total dari semua batch
     */
    public function getProductQuantityAttribute() {
        return $this->batches()->sum('qty') ?? 0;
    }

    /**
     * Media fallback & koleksi media
     */
    public function registerMediaCollections(): void {
        $this->addMediaCollection('images')
            ->useFallbackUrl('/images/fallback_product_image.png');
    }

    public function registerMediaConversions(Media $media = null): void {
        $this->addMediaConversion('thumb')
            ->width(50)
            ->height(50);
    }

    /**
     * Setter & Getter harga modal (dikonversi ke cent jika ingin akurasi tinggi)
     */
    public function setProductCostAttribute($value) {
        $this->attributes['product_cost'] = $value !== null ? ($value * 100) : null;
    }

    public function getProductCostAttribute($value) {
        return $value !== null ? ($value / 100) : null;
    }

    /**
     * Setter & Getter harga jual
     */

     public function getFifoPriceAttribute(): ?float
{
    try {
        return \App\Models\ProductBatch::getFifoBatchPrice(
            $this->id,
            session('branch_id', 1), // pastikan session branch_id aktif
            1 // ambil harga per unit
        );
    } catch (\Exception $e) {
        return null;
    }
}

    public function setProductPriceAttribute($value) {
        $this->attributes['product_price'] = $value !== null ? ($value * 100) : null;
    }

    public function getProductPriceAttribute($value) {
        return $value !== null ? ($value / 100) : null;
    }

    /**
     * Hitung harga grosir jika kuantitas mencukupi
     */
    // public function getWholesalePrice($quantity) {
    //     if ($this->min_quantity_for_wholesale && $quantity >= $this->min_quantity_for_wholesale) {
    //         $discount = $this->wholesale_discount_percentage / 100;
    //         return $this->product_price * (1 - $discount);
    //     }
    //     return $this->product_price;
    // }

    /**
     * Cek apakah kuantitas memenuhi harga grosir
     */
    // public function isWholesalePrice($quantity) {
    //     return $this->min_quantity_for_wholesale && $quantity >= $this->min_quantity_for_wholesale;
    // }
}
