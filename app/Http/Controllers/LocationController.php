<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Location;

class LocationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
     public function index(Request $request)
{
    $sort = $request->get('sort', 'id');
    $direction = $request->get('direction', 'asc');

    $locations = Location::orderBy($sort, $direction)->paginate(100);

    return view('location.index', compact('locations', 'sort', 'direction'));
}

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:locations,name',
        ]);

        Location::create(['name' => $request->name]);

        return redirect()->route('location.index')->with('swal', [
            'icon'  => 'success',
            'title' => 'Created!',
            'text'  => 'Location created successfully.'
        ]);
    }

    public function update(Request $request, Location $location)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:locations,name,' . $location->id,
        ]);

        $location->update(['name' => $request->name]);

        return redirect()->route('location.index')->with('swal', [
            'icon'  => 'success',
            'title' => 'Updated!',
            'text'  => 'Location updated successfully.'
        ]);
    }

    public function destroy(Location $location)
    {
        $location->delete();

        return redirect()->route('location.index')->with('swal', [
            'icon'  => 'success',
            'title' => 'Deleted!',
            'text'  => 'Location deleted successfully.'
        ]);
    }
}
