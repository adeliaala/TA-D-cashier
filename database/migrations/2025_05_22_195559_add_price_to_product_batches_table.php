<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
{
    Schema::table('product_batches', function (Blueprint $table) {
        $table->decimal('price', 15, 2)->nullable()->after('unit_price');
    });
}

public function down()
{
    Schema::table('product_batches', function (Blueprint $table) {
        $table->dropColumn('price');
    });
}

};
