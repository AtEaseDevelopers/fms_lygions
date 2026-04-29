<?php

namespace App\Http\Controllers;

use App\Models\DriverHoliday;
use App\Models\Driver;
use Illuminate\Http\Request;

class DriverHolidayController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $allowedSorts = ['start_date', 'end_date', 'created_at'];
        $sortBy = in_array($request->query('sort_by'), $allowedSorts, true)
            ? $request->query('sort_by')
            : 'start_date';
        $sortOrder = $request->query('sort_order') === 'asc' ? 'asc' : 'desc';
        $search = $request->query('search');
        $perPage = (int) $request->input('per_page', 10);

        $holidays = DriverHoliday::with('driver')
            ->when($search, function ($query, $search) {
                $driverIds = Driver::where('name', 'like', "%{$search}%")->pluck('id');
                $query->where(function ($q) use ($search, $driverIds) {
                    $q->where('remarks', 'like', "%{$search}%")
                        ->orWhereIn('driver_id', $driverIds);
                });
            })
            ->orderBy($sortBy, $sortOrder)
            ->paginate($perPage)
            ->appends($request->query());

        $drivers = Driver::all();

        return view('master-data.truck.driver-holiday', compact('holidays', 'drivers', 'search'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {

    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'driver_id' => 'required|exists:drivers,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'remarks' => 'nullable|string|max:255',
        ]);

        DriverHoliday::create($request->all());

        return redirect()->route('driver-holidays.index')
            ->with('swal', [
                'icon' => 'success',
                'title' => 'Created!',
                'text' => 'Holiday added successfully.'
            ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(DriverHoliday $driverHoliday)
    {
        return view('master-data.truck.driver-holiday.show', compact('driverHoliday'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(DriverHoliday $driverHoliday)
    {
        $drivers = Driver::all();
        return view('master-data.truck.driver-holiday.edit', compact('driverHoliday', 'drivers'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, DriverHoliday $driverHoliday)
    {
        $request->validate([
            'driver_id' => 'required|exists:drivers,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'remarks' => 'nullable|string|max:255',
        ]);

        $driverHoliday->update($request->all());

        return redirect()->route('driver-holidays.index')
            ->with('swal', [
                'icon' => 'success',
                'title' => 'Updated!',
                'text' => 'Holiday updated successfully.'
            ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(DriverHoliday $driverHoliday)
    {
        $driverHoliday->delete();

        return redirect()->route('driver-holidays.index')
            ->with('swal', [
                'icon' => 'success',
                'title' => 'Deleted!',
                'text' => 'Holiday deleted successfully.'
            ]);
    }
}
