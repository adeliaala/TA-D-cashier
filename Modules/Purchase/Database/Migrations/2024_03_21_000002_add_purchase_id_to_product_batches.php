<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('product_batches', function (Blueprint $table) {
            // Add purchase_id column if not exists
            if (!Schema::hasColumn('product_batches', 'purchase_id')) {
                $table->foreignId('purchase_id')->nullable()->after('expired_date')
                    ->constrained('purchases')->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_batches', function (Blueprint $table) {
            // Drop purchase_id column if exists
            if (Schema::hasColumn('product_batches', 'purchase_id')) {
                $table->dropForeign(['purchase_id']);
                $table->dropColumn('purchase_id');
            }
        });
    }
}; 