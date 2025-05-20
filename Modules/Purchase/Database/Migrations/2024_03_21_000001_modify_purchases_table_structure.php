<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Drop unused columns
        Schema::table('purchases', function (Blueprint $table) {
            $columns = ['tax_percentage', 'tax_amount', 'shipping_amount', 'status'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('purchases', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        // Add user_id column if not exists
        if (!Schema::hasColumn('purchases', 'user_id')) {
            Schema::table('purchases', function (Blueprint $table) {
                $table->foreignId('user_id')->nullable()->after('branch_id')
                    ->constrained('users')->nullOnDelete();
            });
        }

        // Modify columns to be nullable
        DB::statement('ALTER TABLE purchases MODIFY discount_amount DECIMAL(10,2) NULL');
        DB::statement('ALTER TABLE purchases MODIFY due_amount DECIMAL(10,2) NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Make columns non-nullable again
        DB::statement('ALTER TABLE purchases MODIFY discount_amount DECIMAL(10,2) NOT NULL');
        DB::statement('ALTER TABLE purchases MODIFY due_amount DECIMAL(10,2) NOT NULL');

        // Drop user_id column if exists
        if (Schema::hasColumn('purchases', 'user_id')) {
            Schema::table('purchases', function (Blueprint $table) {
                $table->dropForeign(['user_id']);
                $table->dropColumn('user_id');
            });
        }

        // Add back dropped columns
        Schema::table('purchases', function (Blueprint $table) {
            if (!Schema::hasColumn('purchases', 'tax_percentage')) {
                $table->decimal('tax_percentage', 10, 2)->default(0);
            }
            if (!Schema::hasColumn('purchases', 'tax_amount')) {
                $table->decimal('tax_amount', 10, 2)->default(0);
            }
            if (!Schema::hasColumn('purchases', 'shipping_amount')) {
                $table->decimal('shipping_amount', 10, 2)->default(0);
            }
            if (!Schema::hasColumn('purchases', 'status')) {
                $table->string('status')->default('Pending');
            }
        });
    }
}; 