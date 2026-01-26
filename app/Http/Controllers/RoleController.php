<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Role;
class RoleController extends Controller
{

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $roles = Role::withCount('users')->get();
        $modules = [
            'dashboard' => 'Dashboard',
            'truck-planning' => 'Truck Planning',
            'calendar' => 'Truck Capacity',
            'truck-summary' => 'Truck Summary',
            'driver-leave' => 'Driver Leave Plan',
            'activity-log' => 'Activity Log',
            'master' => [
                'label' => 'Master Data',
                'items' => [
                    'master.customer' => 'Customer',
                    'master.draft-customer' => 'Draft Customer',
                    'master.subcon' => 'Subcon',
                    'master.truck' => 'Truck',
                    'master.driver' => 'Driver',
                    'master.unit-param' => 'Std Unit Param',
                    'master.location' => 'Location',
                ]
            ],

            'management' => [
                'label' => 'User & Role',
                'items' => [
                    'user-management' => 'User Management',
                    'role-management' => 'Role Management',
                ]
            ],
        ];

        return view('role.index', compact('roles', 'modules'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|unique:roles,name',
            'permissions' => 'nullable|array',
        ]);

        Role::create([
            'name' => $request->name,
            'permissions' => $request->permissions ?? [],
        ]);

        return redirect()->route('role.index')->with('swal', [
            'icon' => 'success',
            'title' => 'Success',
            'text' => 'Role created successfully!'
        ]);
    }

    public function update(Request $request, Role $role)
    {
        $request->validate([
            'name' => 'required|unique:roles,name,' . $role->id,
            'permissions' => 'nullable|array',
        ]);

        $role->update([
            'name' => $request->name,
            'permissions' => $request->permissions ?? [],
        ]);

        return redirect()->route('role.index')->with('swal', [
            'icon' => 'success',
            'title' => 'Updated!',
            'text' => 'Role updated successfully.'
        ]);
    }

    public function destroy(Role $role)
    {
        $role->delete();

        return redirect()->route('role.index')->with('swal', [
            'icon' => 'success',
            'title' => 'Deleted!',
            'text' => 'Role removed successfully.'
        ]);
    }
}
