<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('purchases', function (Blueprint $table) {
            // Drop unused columns
            $table->dropColumn([
                'tax_percentage',
                'tax_amount',
                'shipping_amount',
                'status'
            ]);

            // Make columns nullable
            $table->decimal('discount_amount', 10, 2)->nullable()->change();
            $table->decimal('due_amount', 10, 2)->nullable()->change();

            // Add user_id column
            $table->foreignId('user_id')->nullable()->after('branch_id')
                ->constrained('users')->nullOnDelete();
        });
    }

    public function down()
    {
        Schema::table('purchases', function (Blueprint $table) {
            // Revert nullable columns
            $table->decimal('discount_amount', 10, 2)->nullable(false)->change();
            $table->decimal('due_amount', 10, 2)->nullable(false)->change();

            // Drop user_id column
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');

            // Add back dropped columns
            $table->decimal('tax_percentage', 10, 2)->default(0);
            $table->decimal('tax_amount', 10, 2)->default(0);
            $table->decimal('shipping_amount', 10, 2)->default(0);
            $table->string('status')->default('Pending');
        });
    }
}; 