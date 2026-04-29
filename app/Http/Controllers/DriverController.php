<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Driver;
use App\Models\Truck;

class DriverController extends Controller
{
    public function index(Request $request)
    {
        $sortBy = $request->query('sort_by', 'name');
        $sortOrder = $request->query('sort_order', default: 'asc');
        $search = $request->query('search');
        $perPage = $request->input('per_page', 10);

        $drivers = Driver::query()
            ->where('resigned', 0)
            ->where('is_outsider', 0)
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('id_number', 'like', "%{$search}%")
                        ->orWhere('phone_number_mas', 'like', "%{$search}%")
                        ->orWhere('phone_number_sg', 'like', "%{$search}%")
                        ->orWhere('group', 'like', "%{$search}%");
                });
            })
            ->orderBy($sortBy, $sortOrder)
            ->paginate($perPage)
            ->appends($request->query());

        return view('master-data.driver.index', compact('drivers', 'search'));
    }

    public function create()
    {
        return view('master-data.driver.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'group' => 'required|string|max:255',
            'id_number' => 'required|string|max:20',
            'phone_my' => 'required|string|max:20',
            'phone_sg' => 'nullable|string|max:20',
            'truck' => 'nullable|string|max:50',
            'note' => 'nullable|string',
            'outsider' => 'boolean',
        ]);

        Driver::create($validated);

        return redirect()->route('driver.index')->with('swal', [
            'icon' => 'success',
            'title' => 'Created!',
            'text' => 'Driver created successfully.'
        ]);
    }

    public function edit(Driver $driver)
    {
        return view('master-data.driver.edit', compact('driver'));
    }

    public function update(Request $request, Driver $driver)
    {
        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'group' => 'nullable|string|max:255',
            'id_number' => 'nullable|string|max:20',
            'phone_my' => 'nullable|string|max:20',
            'phone_sg' => 'nullable|string|max:20',
            'truck' => 'nullable|string|max:50',
            'note' => 'nullable|string',
            'outsider' => 'boolean',
        ]);

        $driver->update($validated);

        return redirect()->route('driver.index')->with('swal', [
            'icon' => 'success',
            'title' => 'Updated!',
            'text' => 'Driver updated successfully.'
        ]);
    }

    public function destroy(Driver $driver)
    {
        $driver->delete();

        return redirect()->route('driver.index')->with('swal', [
            'icon' => 'success',
            'title' => 'Deleted!',
            'text' => 'Driver deleted successfully.'
        ]);
    }
}
