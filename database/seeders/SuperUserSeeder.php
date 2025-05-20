<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Branch;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class SuperUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Get default branch
        $branch = Branch::where('name', 'Toko Al Fatih Pusat')->first();

        // Create super admin user
        $user = User::create([
            'name' => 'Administrator',
            'email' => 'super.admin@test.com',
            'password' => Hash::make(12345678),
            'is_active' => 1
        ]);

        // Create and assign Super Admin role
        $superAdmin = Role::create([
            'name' => 'Super Admin'
        ]);

        $user->assignRole($superAdmin);

        // Attach user to branch
        $user->branches()->attach($branch->id);
    }
}
