<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Unit;
class ParamController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $units = Unit::all();
        return view('unit-param.index', compact('units'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'unit' => 'required|string|max:255',
            'qty'  => 'required|integer|min:0',
        ]);

        Unit::create($request->all());

        return redirect()->route('unit-param.index')->with('swal', [
            'icon'  => 'success',
            'title' => 'Created!',
            'text'  => 'Parameter created successfully.'
        ]);
    }

    public function update(Request $request, Unit $unit)
    {
        $request->validate([
            'unit' => 'required|string|max:255',
            'qty'  => 'required|integer|min:0',
        ]);

        $unit->update($request->all());

        return redirect()->route('unit-param.index')->with('swal', [
            'icon'  => 'success',
            'title' => 'Updated!',
            'text'  => 'Parameter updated successfully.'
        ]);
    }

    public function destroy(Unit $unit)
    {
        $unit->delete();

        return redirect()->route('unit-param.index')->with('swal', [
            'icon'  => 'success',
            'title' => 'Deleted!',
            'text'  => 'Parameter deleted successfully.'
        ]);
    }
}
