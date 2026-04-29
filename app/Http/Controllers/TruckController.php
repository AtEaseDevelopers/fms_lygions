<?php
namespace App\Http\Controllers;

use App\Models\Truck;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class TruckController extends Controller
{
    public function index(Request $request)
    {
        $sortableColumns = ['number', 'group', 'tonnage', 'floor_space', 'chassis_type'];
        $sortBy = $request->query('sort_by', 'number');
        if (!in_array($sortBy, $sortableColumns, true)) {
            $sortBy = 'number';
        }

        $sortOrder = strtolower((string) $request->query('sort_order', 'asc'));
        if (!in_array($sortOrder, ['asc', 'desc'], true)) {
            $sortOrder = 'asc';
        }

        $search = $request->query('search');

        $query = Truck::where('is_outsider', 0);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('number', 'like', "%$search%")
                  ->orWhere('group', 'like', "%$search%")
                  ->orWhere('tonnage', 'like', "%$search%")
                  ->orWhere('floor_space', 'like', "%$search%");
            });
        }

        if ($sortBy === 'tonnage') {
            $query->orderByRaw("CAST(tonnage AS DECIMAL(10,2)) {$sortOrder}");
        } elseif ($sortBy === 'chassis_type') {
            $pdo = DB::connection((new Truck)->getConnectionName())->getPdo();
            $cases = collect(Truck::chassisTypeMap())
                ->map(fn ($label, $id) => "WHEN {$id} THEN " . $pdo->quote($label))
                ->implode(' ');
            $query->orderByRaw("CASE chassis_type {$cases} ELSE chassis_type END {$sortOrder}");
        } else {
            $query->orderBy($sortBy, $sortOrder);
        }

        $trucks = $query->get();

        return view('master-data.truck.truck', compact('trucks', 'sortBy', 'sortOrder'));
    }

    public function create()
    {
        return view('master-data.truck.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'number' => 'required',
            'group' => 'required',
            'tonnage' => 'required|numeric',
            'floor_space' => 'required|numeric',
            'chassis_type' => 'required',
            'next_inspection' => 'nullable|date',
            'next_tyre' => 'nullable|date',
            'next_permit' => 'nullable|date',
            'next_extinguisher' => 'nullable|date',
            'next_roadtax' => 'nullable|date',
            'next_insurance' => 'nullable|date',
            'next_others' => 'nullable|date',
        ]);

        Truck::create($request->only([
            'number', 'group', 'tonnage', 'floor_space', 'chassis_type',
            'next_inspection', 'next_tyre', 'next_permit', 'next_extinguisher',
            'next_roadtax', 'next_insurance', 'next_others', 'team', 'size',
        ]));

        return redirect()->route('truck.index')->with('swal', [
            'icon' => 'success',
            'title' => 'Created!',
            'text' => 'Truck added successfully.'
        ]);
    }

    public function update(Request $request, Truck $truck)
    {
        $request->validate([
            'number' => 'required',
            'group' => 'required',
            'tonnage' => 'required',
            'floor_space' => 'required',
            'chassis_type' => 'required',
        ]);

        $truck->update($request->only([
            'number', 'group', 'tonnage', 'floor_space', 'chassis_type',
            'next_inspection', 'next_tyre', 'next_permit', 'next_extinguisher',
            'next_roadtax', 'next_insurance', 'next_others', 'team', 'size',
        ]));

        return redirect()->route('truck.index')->with('swal', [
            'icon' => 'success',
            'title' => 'Updated!',
            'text' => 'Truck updated successfully.'
        ]);
    }

    public function destroy(Truck $truck)
    {
        $truck->delete();

        return redirect()->route('truck.index')->with('swal', [
            'icon' => 'success',
            'title' => 'Deleted!',
            'text' => 'Truck deleted successfully.'
        ]);
    }
}
