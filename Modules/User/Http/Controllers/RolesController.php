<?php

namespace Modules\User\Http\Controllers;

use Modules\User\DataTables\RolesDataTable;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Role;

class RolesController extends Controller
{
    public function index(RolesDataTable $dataTable) {
        abort_if(Gate::denies('access_user_management'), 403);

        return $dataTable->render('user::roles.index');
    }


    public function create() {
        abort_if(Gate::denies('access_user_management'), 403);

        return view('user::roles.create');
    }


    public function store(Request $request) {
        abort_if(Gate::denies('access_user_management'), 403);

        $request->validate([
            'name' => 'required|string|max:255',
            'permissions' => 'required|array'
        ]);

        $role = Role::create([
            'name' => $request->name
        ]);

        $role->givePermissionTo($request->permissions);

        toast('Role Created With Selected Permissions!', 'success');

        return redirect()->route('roles.index');
    }


    public function edit(Role $role) {
        abort_if(Gate::denies('access_user_management'), 403);

        return view('user::roles.edit', compact('role'));
    }


    public function update(Request $request, Role $role) {
        abort_if(Gate::denies('access_user_management'), 403);

        $request->validate([
            'name' => 'required|string|max:255',
            'permissions' => 'required|array'
        ]);

        $role->update([
            'name' => $request->name
        ]);

        $role->syncPermissions($request->permissions);

        toast('Role Updated With Selected Permissions!', 'success');

        return redirect()->route('roles.index');
    }


    public function destroy(Role $role) {
        abort_if(Gate::denies('access_user_management'), 403);

        $role->delete();

        toast('Role Deleted!', 'success');

        return redirect()->route('roles.index');
    }

    public function createOwnerRole() {
        abort_if(Gate::denies('access_user_management'), 403);

        // Cek apakah role Owner sudah ada
        $ownerRole = Role::where('name', 'Owner')->first();
        
        if (!$ownerRole) {
            // Buat role Owner baru
            $ownerRole = Role::create([
                'name' => 'Owner'
            ]);

            // Berikan permission yang diperlukan
            $permissions = [
                // Dashboard permissions
                'show_total_stats',
                'show_month_overview',
                'show_weekly_sales_purchases',
                'show_monthly_cashflow',
                'show_notifications',
                
                // Sales permissions
                'access_sales',
                'show_sales',
                
                // Purchases permissions
                'access_purchases',
                'show_purchases',
                
                // Reports permissions
                'access_reports',
                
                // Currencies permissions
                'access_currencies',
                
                // Settings permissions
                'access_settings'
            ];

            $ownerRole->givePermissionTo($permissions);

            toast('Owner Role Created Successfully!', 'success');
        } else {
            toast('Owner Role Already Exists!', 'warning');
        }

        return redirect()->route('roles.index');
    }
}
