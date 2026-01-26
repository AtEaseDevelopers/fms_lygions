<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\TruckSize;
class TruckTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $trucks = TruckSize::all();
        return view('truck-type.index', compact('trucks'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'size' => 'required|string|max:255|unique:truck_sizes,size',
            'sqft' => 'required|numeric',
        ]);

        TruckSize::create($request->only(['size', 'sqft']));

        return back()->with('swal', [
            'icon' => 'success',
            'title' => 'Created!',
            'text' => 'Truck size created successfully.'
        ]);
    }

    public function update(Request $request, TruckSize $trucksize)
    {
        $request->validate([
            'size' => 'required|string|max:255|unique:truck_sizes,size,' . $trucksize->id,
            'sqft' => 'required|numeric',
        ]);

        $trucksize->update($request->only(['size', 'sqft']));

        return back()->with('swal', [
            'icon' => 'success',
            'title' => 'Updated!',
            'text' => 'Truck size updated successfully.'
        ]);
    }

    public function destroy(TruckSize $trucksize)
    {
        $trucksize->delete();

        return back()->with('swal', [
            'icon' => 'success',
            'title' => 'Deleted!',
            'text' => 'Truck size deleted successfully.'
        ]);
    }
}
