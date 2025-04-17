<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddWholesalePriceToProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('products', function (Blueprint $table) {
            $table->integer('min_quantity_for_wholesale')->nullable()->after('product_price');
            $table->integer('wholesale_discount_percentage')->nullable()->after('min_quantity_for_wholesale');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('min_quantity_for_wholesale');
            $table->dropColumn('wholesale_discount_percentage');
        });
    }
} 