<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('adjusted_products', function (Blueprint $table) {
            $table->unsignedBigInteger('product_batch_id')->after('product_id');
            $table->foreign('product_batch_id')->references('id')->on('product_batches')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('adjusted_products', function (Blueprint $table) {
            $table->dropForeign(['product_batch_id']);
            $table->dropColumn('product_batch_id');
        });
    }
}; 