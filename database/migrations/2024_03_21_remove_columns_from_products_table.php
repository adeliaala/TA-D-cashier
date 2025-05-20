<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'product_quantity',
                'product_cost',
                'product_price',
                'min_quantity_for_wholesale',
                'wholesale_discount_percentage',
                'product_order_tax',
                'product_tax_type'
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->integer('product_quantity')->default(0);
            $table->integer('product_cost')->default(0);
            $table->integer('product_price')->default(0);
            $table->integer('min_quantity_for_wholesale')->nullable();
            $table->integer('wholesale_discount_percentage')->nullable();
            $table->integer('product_order_tax')->default(0);
            $table->tinyInteger('product_tax_type')->default(0);
        });
    }
}; 