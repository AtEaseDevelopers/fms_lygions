<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use \App\Models\Consignment;
use \App\Models\Truck;
use \App\Models\Unit;

class TruckSummaryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Get all trucks
        $selectedGroup = $request->get('group'); // <-- from filter
        $selectedDate = $request->get('date', now()->format('Y-m-d'));

        $trucks = Truck::select('id', 'number', 'group', 'floor_space')
            ->where('is_outsider', 0)
            ->when($selectedGroup, function ($query, $selectedGroup) {
                $query->where('group', $selectedGroup);
            })
            ->get();

        $trucks_grp = Truck::where('is_outsider', 0)->pluck('group')->unique()->values();
        // Get unit spaces for conversion
        $unitSpaces = Unit::pluck('space', 'unit')->toArray();

        // Fetch consignments
        $consignments = Consignment::where('load_date', $selectedDate)->select(
            'load_date',
            'consignment_no',
            'consignor',
            'pick_point',
            'pick_address',
            'consignee',
            'drop_point',
            'drop_address',
            'pick_truck_type',
            'drop_truck_type',
            'pick_time',
            'quantity',
            'unit',
            'pre_pick',
            'remarks',
            'drop_truck_size',
            'pick_truck_size',
            'truck_number',
            'status'
        )->get();

        $dash = fn ($v) => ($v === null || $v === '' || $v === []) ? '-' : $v;

        // Group consignments by truck number
        $csns = $consignments->groupBy('truck_number')->map(function ($group) use ($dash) {
            return $group->map(function ($item) use ($dash) {
                $quantity = json_decode($item->quantity, true);
                $unit = json_decode($item->unit, true);

                return [
                    'number' => $dash($item->consignment_no),
                    'load_date' => $dash($item->load_date),
                    'consignor' => $dash($item->consignor),
                    'pick_point' => $dash($item->pick_point),
                    'pick_address' => $dash($item->pick_address),
                    'consignee' => $dash($item->consignee),
                    'drop_point' => $dash($item->drop_point),
                    'drop_address' => $dash($item->drop_address),
                    'pick_truck_type' => $dash($item->pick_truck_type),
                    'drop_truck_type' => $dash($item->drop_truck_type),
                    'pick_time' => $dash($item->pick_time),
                    'quantity' => $dash(is_array($quantity) ? implode(', ', $quantity) : $item->quantity),
                    'unit' => $dash(is_array($unit) ? implode(', ', $unit) : $item->unit),
                    'pre_pick' => $item->pre_pick ? 'Yes' : 'No',
                    'remarks' => $dash($item->remarks),
                    'pick_truck_size' => $dash($item->pick_truck_size),
                    'drop_truck_size' => $dash($item->drop_truck_size),
                    'truck_number' => $dash($item->truck_number),
                    'status' => $dash($item->status),
                    'space_usage' => '-',
                ];
            });
        });

        // Compute usage for each truck (MATCHES CONSIGNMENT LOGIC)
        $trucks = $trucks->map(function ($truck) use ($unitSpaces, $consignments) {
            $floorSpace = (float) ($truck->floor_space ?? 0);
            $truckConsignments = $consignments->where('truck_number', $truck->number);

            // Calculate total USED space exactly like in consignment
            $used = $truckConsignments->sum(function ($c) use ($unitSpaces) {
                $parseToArray = function ($v) {
                    if (is_string($v)) {
                        $decoded = json_decode($v, true);
                        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                            return array_map('trim', $decoded);
                        }
                    }
                    return is_array($v) ? array_map('trim', $v) : array_map('trim', explode(',', (string) $v));
                };

                $qtys = $parseToArray($c->quantity);
                $unitStrings = $parseToArray($c->unit);

                $units = array_map(function ($u) use ($unitSpaces) {
                    $key = trim($u);
                    return isset($unitSpaces[$key]) ? (float) $unitSpaces[$key] : 0;
                }, $unitStrings);

                $max = max(count($qtys), count($units), 1);
                $balance = 0.0;

                for ($i = 0; $i < $max; $i++) {
                    $q = (float) ($qtys[$i] ?? ($qtys[0] ?? 0));
                    $u = (float) ($units[$i] ?? ($units[0] ?? 1));
                    $balance += $q * $u;
                }

                return $balance;
            });

            // Match consignment math exactly
            $remaining = $floorSpace - $used;
            $utilization = $floorSpace > 0 ? ($used / $floorSpace) * 100 : 0;

            return [
                'id' => $truck->id,
                'number' => $truck->number,
                'group' => $truck->group,
                'space' => $floorSpace,
                'used' => $used,
                'balance_capacity' => $remaining,
                'utilization' => $utilization,

            ];
        });

        return view('truck-summary.index', compact('trucks', 'csns','trucks_grp', 'selectedGroup', 'selectedDate'));
    }


    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
