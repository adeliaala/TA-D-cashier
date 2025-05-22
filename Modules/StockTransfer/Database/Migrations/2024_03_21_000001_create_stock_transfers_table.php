<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('stock_transfers', function (Blueprint $table) {
            $table->id();
            $table->string('reference_no')->unique();
            $table->foreignId('source_branch_id')->constrained('branches')->onDelete('restrict');
            $table->foreignId('destination_branch_id')->constrained('branches')->onDelete('restrict');
            $table->date('transfer_date');
            $table->text('note')->nullable();
            $table->string('status')->default('pending'); // pending, completed, cancelled
            $table->string('created_by');
            $table->string('updated_by')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('stock_transfers');
    }
}; 