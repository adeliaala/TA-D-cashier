<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class CreateBranchTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Create branches table if it doesn't exist
        if (!Schema::hasTable('branches')) {
            Schema::create('branches', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('city')->nullable();
                $table->string('address')->nullable();
                $table->string('phone')->nullable();
                $table->string('email')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });

            // Insert default branch
            DB::table('branches')->insert([
                'name' => 'Toko Al Fatih Pusar',
                'city' => 'Lumajang',
                'address' => 'Toko Pusat',
                'phone' => '1234567890',
                'email' => 'alfatih@test.com',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }

        // Add branch_id to all necessary tables
        $tables = [
            'sales',
            'purchases',
            'purchase_returns',
            'expenses',
            'sale_payments',
            'purchase_payments',
            'purchase_return_payments'
        ];

        foreach ($tables as $tableName) {
            if (Schema::hasTable($tableName)) {
                Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                    if (!Schema::hasColumn($tableName, 'branch_id')) {
                        $table->unsignedBigInteger('branch_id')->default(1)->after('id');
                        $table->foreign('branch_id')->references('id')->on('branches');
                    }
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Remove branch_id from all tables
        $tables = [
            'sales',
            'purchases',
            'purchase_returns',
            'expenses',
            'sale_payments',
            'purchase_payments',
            'purchase_return_payments'
        ];

        foreach ($tables as $tableName) {
            if (Schema::hasTable($tableName)) {
                Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                    if (Schema::hasColumn($tableName, 'branch_id')) {
                        $table->dropForeign(['branch_id']);
                        $table->dropColumn('branch_id');
                    }
                });
            }
        }

        // Drop branches table
        Schema::dropIfExists('branches');
    }
} 