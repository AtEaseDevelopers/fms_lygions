<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Driver;
use App\Models\Truck;
use Illuminate\Support\Facades\Http;

class DriverController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $sortBy = $request->query('sort_by', 'name');
        $sortOrder = $request->query('sort_order', default: 'asc');
        $search = $request->query('search');
        $perPage = $request->input('per_page', 10);

        $drivers = Driver::query()
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('id_number', 'like', "%{$search}%")
                        ->orWhere('phone_my', 'like', "%{$search}%")
                        ->orWhere('phone_sg', 'like', "%{$search}%")
                        ->orWhere('group', 'like', "%{$search}%");
                });
            })
            ->orderBy($sortBy, $sortOrder)
            ->paginate($perPage)
            ->appends($request->query());

        return view('master-data.driver.index', compact('drivers', 'search'));
    }


    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('master-data.driver.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'group' => 'required|string|max:255',
            'id_number' => 'required|string|max:20|unique:drivers,id_number',
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

    public function syncFromLygions()
    {
        try {
            $response = Http::get('https://lygions.com/api/v1/driver/get-all');

            if ($response->failed()) {
                return response()->json([
                    'swal' => [
                        'icon' => 'error',
                        'title' => 'Sync Failed',
                        'text' => 'Unable to fetch drivers from Lygion API.'
                    ]
                ], 500);
            }

            $drivers = $response->json('drivers', []);
            $processed = 0;

            foreach ($drivers as $item) {
                $lygionId = 'lygion_' . $item['id'];

                // Try to find linked truck if default_lorry_id exists
                $linkedTruck = null;
                if (!empty($item['default_lorry_id'])) {
                    $linkedTruck = Truck::where('lygions_id', 'lygion_' . $item['default_lorry_id'])->first();
                }

                Driver::updateOrCreate(
                    ['lygion_id' => $lygionId],
                    [
                        'name' => $item['name'] ?? null,
                        'group' => $item['group'] ?? null,
                        'id_number' => $item['id_number'] ?? null,
                        'phone_my' => $item['phone_number_mas'] ?? null,
                        'phone_sg' => $item['phone_number_sg'] ?? null,
                        'truck' => $linkedTruck?->number,
                        'note' => $item['note'] ?? null,
                        'outsider' => $item['is_outsider'] ?? false,
                    ]
                );

                $processed++;
            }

            return response()->json([
                'swal' => [
                    'icon' => 'success',
                    'title' => 'Sync Complete!',
                    'text' => "Successfully synced {$processed} drivers from Lygion."
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'swal' => [
                    'icon' => 'error',
                    'title' => 'Error',
                    'text' => $e->getMessage()
                ]
            ], 500);
        }
    }


    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Driver $driver)
    {
        return view('master-data.driver.edit', compact('driver'));
    }

    /**
     * Update the specified resource in storage.
     */
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

    /**
     * Remove the specified resource from storage.
     */
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
