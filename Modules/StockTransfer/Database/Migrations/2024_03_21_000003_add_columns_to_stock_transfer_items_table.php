<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddColumnsToStockTransferItemsTable extends Migration
{
    public function up()
    {
        Schema::table('stock_transfer_items', function (Blueprint $table) {
            $table->unsignedBigInteger('destination_batch_id')->nullable()->after('product_batch_id');
            $table->decimal('unit_price', 10, 2)->nullable()->after('qty');
            $table->decimal('price', 10, 2)->nullable()->after('unit_price');
            
            $table->foreign('destination_batch_id')
                ->references('id')
                ->on('product_batches')
                ->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::table('stock_transfer_items', function (Blueprint $table) {
            $table->dropForeign(['destination_batch_id']);
            $table->dropColumn(['destination_batch_id', 'unit_price', 'price']);
        });
    }
} 