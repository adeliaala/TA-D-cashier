<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('product_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->foreignId('branch_id')->constrained('branches')->onDelete('cascade');
            $table->string('batch_code')->nullable();
            $table->integer('quantity');
            $table->decimal('unit_price', 10, 2);
            $table->date('expired_date')->nullable();
            $table->timestamps();

            // Add index for faster queries
            $table->index(['product_id', 'branch_id', 'expired_date']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('product_batches');
    }
}; 