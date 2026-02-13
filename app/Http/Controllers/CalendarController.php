<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Truck;
use App\Models\Availability;
use App\Models\Consignment;
use Carbon\Carbon;
use App\Models\Unit;
use App\Models\Driver;
use App\Models\Subcon;
class CalendarController extends Controller
{

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $days = $this->getDays($request);
        $start = $this->getStartDate($request);
        $dates = $this->generateDateRange($start, $days);

        [$dates, $consignmentsByDate, $truckMap] = $this->calculateCapacities($dates, $start, $days);


        $startDateStr = $start->format('Y-m-d');
        $endDateStr = $start->copy()->addDays($days - 1)->format('Y-m-d');

        // preload and group
        $availabilities = Availability::whereBetween('date', [$startDateStr, $endDateStr])->get();
        $availabilityMap = $availabilities->groupBy(fn($a) => $a->truck_id . '-' . (new Carbon($a->date))->format('Y-m-d'));

        $allConsignments = Consignment::whereBetween('load_date', [$startDateStr, $endDateStr])
            ->with('driverInfo')
            ->get();
        $consignmentMap = $allConsignments->groupBy(fn($c) => $c->truck_number . '-' . (new Carbon($c->load_date))->format('Y-m-d'));

        // cache units (space by unit)
        $unitsMap = Unit::all()->pluck('space', 'unit')->mapWithKeys(fn($v, $k) => [trim($k) => (float) $v]);

        // now call buildCalendarMatrix with maps
        [$calendarMatrix, $trucks] = $this->buildCalendarMatrix($dates, $truckMap, $availabilityMap, $consignmentMap, $unitsMap);


        $filteredMatrix = $this->filterCalendarMatrix($calendarMatrix);
        $trucks = $trucks->filter(fn($truck) => isset($filteredMatrix[$truck->number]));

        // Recalculate date header capacities from the full calendar matrix
        $dates = $dates->map(function ($entry) use ($calendarMatrix) {
            $dateKey = $entry['date']->format('Y-m-d');
            $myOrigin = 0;
            $myBalance = 0;
            $sgOrigin = 0;
            $sgBalance = 0;

            foreach ($calendarMatrix as $truckNumber => $truckDates) {
                if (!isset($truckDates[$dateKey])) continue;
                $day = $truckDates[$dateKey];

                foreach (['MY', 'SG'] as $loc) {
                    $status = $day[$loc]['status'] ?? 'empty';
                    if (in_array($status, ['available', 'occupied'])) {
                        if ($loc === 'MY') {
                            $myOrigin += $day[$loc]['total_capacity'] ?? 0;
                            $myBalance += $day[$loc]['used_capacity'] ?? 0;
                        } else {
                            $sgOrigin += $day[$loc]['total_capacity'] ?? 0;
                            $sgBalance += $day[$loc]['used_capacity'] ?? 0;
                        }
                    }
                }
            }

            $entry['MY'] = ['origin' => $myOrigin, 'balance' => $myBalance];
            $entry['SG'] = ['origin' => $sgOrigin, 'balance' => $sgBalance];
            return $entry;
        });

        $summary = $this->calculateUtilization($dates);
        $drivers = Driver::orderBy('name')->get();

        $prevStart = $start->copy()->subDays($days)->format('Y-m-d');
        $nextStart = $start->copy()->addDays($days)->format('Y-m-d');

        $truck_select = Truck::select('id', 'number', 'team')
            ->get()
            ->map(fn($t) => [
                'id' => $t->id,
                'number' => $t->number,
                'team' => $t->team,
                'source' => 'truck',
            ]);

        $subcons = Subcon::select('id', 'truck_no as number', 'team')
            ->get()
            ->map(fn($s) => [
                'id' => $s->id,
                'number' => $s->number,
                'team' => $s->team,
                'source' => 'subcon',
            ]);

        $trucks_select = $truck_select->merge($subcons);

        return view('calendar.index', array_merge([
            'trucks' => $trucks,
            'dates' => $dates,
            'calendarMatrix' => $filteredMatrix,
            'days' => $days,
            'start' => $start,
            'prevStart' => $prevStart,
            'nextStart' => $nextStart,
            'totalVisibleTrucks' => $trucks->count(),
            'trucks_select' => $trucks_select,
            'drivers' => $drivers,
            'getColor' => fn($util) => $this->getColor($util),
        ], $summary));
    }
    private function getDays(Request $request): int
    {
        $days = (int) $request->query('days', 14);
        return in_array($days, [7, 14]) ? $days : 14;
    }

    private function getStartDate(Request $request): Carbon
    {
        $startDate = $request->query('start_date');
        return $startDate
            ? Carbon::parse($startDate)->startOfWeek(Carbon::MONDAY)
            : now()->startOfWeek(Carbon::MONDAY);
    }

    private function generateDateRange($start, $days)
    {
        return collect(range(0, $days - 1))->map(fn($i) => [
            'date' => $start->copy()->addDays($i),
            'MY' => ['origin' => 0, 'balance' => 0],
            'SG' => ['origin' => 0, 'balance' => 0],
        ]);
    }

    private function calculateCapacities($dates, $start, $days)
    {
        $truckMap = Truck::all()->keyBy('number');
        $startDate = $start->format('Y-m-d');
        $endDate = $start->copy()->addDays($days - 1)->format('Y-m-d');

        $consignmentsByDate = Consignment::whereBetween('load_date', [$startDate, $endDate])
            ->get()
            ->groupBy(fn($c) => (new \Carbon\Carbon($c->load_date))->format('Y-m-d'));

        $parseToArray = fn($v) => $this->parseToArray($v);

        $dates = $dates->map(function ($entry) use ($consignmentsByDate, $truckMap, $parseToArray) {
            $dateKey = $entry['date']->format('Y-m-d');
            $dayConsignments = $consignmentsByDate->get($dateKey, collect());

            foreach (['MY' => '!=', 'SG' => '='] as $loc => $condition) {
                $consignments = $dayConsignments->where('pick_point', $condition, 'Singapore');
                $truckNumbers = $consignments->pluck('truck_number')->unique()->filter()->values();

                $origin = $truckNumbers->map(fn($n) => optional($truckMap->get($n))->floor_space ?? 0)->sum();

                $balance = 0;
                foreach ($consignments as $c) {
                    $qtys = $parseToArray($c->quantity);
                    $units = $this->parseUnitSpaces($c->unit);
                    $max = max(count($qtys), count($units), 1);
                    for ($i = 0; $i < $max; $i++) {
                        $balance += ($qtys[$i] ?? $qtys[0] ?? 0) * ($units[$i] ?? $units[0] ?? 1);
                    }
                }

                $entry[$loc] = ['origin' => $origin, 'balance' => $balance];
            }

            return $entry;
        });

        return [$dates, $consignmentsByDate, $truckMap];
    }
    private function parseToArray($value)
    {
        if (is_array($value))
            return array_map('floatval', $value);
        if (is_numeric($value))
            return [(float) $value];

        $decoded = json_decode($value, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded))
            return array_map('floatval', $decoded);

        $trimmed = trim($value, "[] \t\n\r\0\x0B");
        if ($trimmed === '')
            return [];
        $parts = array_filter(array_map('trim', explode(',', $trimmed)));
        return array_map('floatval', $parts);
    }

    private function parseUnitSpaces($unitValue)
    {
        $unitStrings = json_decode($unitValue, true);
        if (!is_array($unitStrings))
            $unitStrings = $this->parseToArray($unitValue);

        return array_map(function ($u) {
            $model = Unit::where('unit', trim($u))->first();
            return $model ? (float) $model->space : 0;
        }, $unitStrings);
    }
    private function buildCalendarMatrix($dates, $truckMap, $availabilityMap, $consignmentMap, $unitsMap)
    {
        $trucks = Truck::all();
        $calendarMatrix = [];

        $parseToArray = fn($v) => $this->parseToArray($v);

        foreach ($trucks as $truck) {
            foreach ($dates as $date) {
                $formattedDate = $date['date']->format('Y-m-d');
                $key = $truck->number . '-' . $formattedDate;
                $availKey = $truck->id . '-' . $formattedDate;

                // Lookup availabilities from map (may be a collection or null)
                $availEntries = $availabilityMap->get($availKey) ?? collect();
                $myAvailability = $availEntries->firstWhere('location', 'MY');
                $sgAvailability = $availEntries->firstWhere('location', 'SG');

                // Lookup consignments from map (collection)
                $dayCons = $consignmentMap->get($key) ?? collect();

                $myConsignments = $dayCons->where('pick_point', '!=', 'Singapore');
                $sgConsignments = $dayCons->where('pick_point', 'Singapore');

                $calcUsed = function ($consignments) use ($parseToArray, $unitsMap) {
                    $used = 0;
                    foreach ($consignments as $c) {
                        $qtys = $parseToArray($c->quantity);
                        $unitStrings = json_decode($c->unit, true);
                        if (!is_array($unitStrings))
                            $unitStrings = $parseToArray($c->unit);
                        $units = array_map(fn($u) => $unitsMap[trim((string) $u)] ?? 0, $unitStrings);
                        $max = max(count($qtys), count($units), 1);
                        for ($i = 0; $i < $max; $i++) {
                            $used += ($qtys[$i] ?? $qtys[0] ?? 0) * ($units[$i] ?? $units[0] ?? 1);
                        }
                    }
                    return $used;
                };

                $usedMy = $calcUsed($myConsignments);
                $usedSg = $calcUsed($sgConsignments);

                $calendarMatrix[$truck->number][$formattedDate] = [
                    'MY' => [
                        'status' => $myAvailability->status ?? ($myConsignments->isNotEmpty() ? 'occupied' : 'empty'),
                        'consignors' => $myConsignments->pluck('consignor'),
                        'total_capacity' => optional($truck)->floor_space ?? 0,
                        'used_capacity' => $usedMy,
                        'driver_name' => optional($myConsignments->first()?->driverInfo)->name,
                        'driver_id' => optional($myConsignments->first()?->driverInfo)->id,
                    ],
                    'SG' => [
                        'status' => $sgAvailability->status ?? ($sgConsignments->isNotEmpty() ? 'occupied' : 'empty'),
                        'consignors' => $sgConsignments->pluck('consignor'),
                        'total_capacity' => optional($truck)->floor_space ?? 0,
                        'used_capacity' => $usedSg,
                        'driver_name' => optional($sgConsignments->first()?->driverInfo)->name,
                        'driver_id' => optional($sgConsignments->first()?->driverInfo)->id,
                    ],
                ];
            }
        }

        return [$calendarMatrix, $trucks];
    }
    public function getCellDetails(Request $request)
    {
        $truckNumber = $request->query('truck');
        $location = $request->query('location');
        $date = $request->query('date');

        logger('CellDetails called', compact('truckNumber', 'location', 'date'));

        $truck = Truck::where('number', $truckNumber)->first();

        if (!$truck) {
            return response('<div class="p-3 text-danger">❌ Truck not found</div>', 404);
        }

        $availability = Availability::where('truck_id', $truck->id)
            ->where('location', $location)
            ->whereDate('date', $date)
            ->first();

        $consignments = Consignment::with('driverInfo')
            ->where('truck_number', $truckNumber)
            ->whereDate('load_date', $date)
            ->get();


        $totalCapacity = $truck->floor_space ?? 0;
        $usedCapacity = 0;
        $consignors = [];


        foreach ($consignments as $c) {

            // Parse quantity → array
            $qtys = json_decode($c->quantity, true);
            if (!is_array($qtys)) {
                $qtys = [$c->quantity];
            }

            // Parse unit → array (same logic as calculateCapacities)
            $units = $this->parseUnitSpaces($c->unit);

            $max = max(count($qtys), count($units), 1);
            $capacityUsedByConsignment = 0;

            for ($i = 0; $i < $max; $i++) {
                $q = $qtys[$i] ?? $qtys[0] ?? 0;
                $u = $units[$i] ?? $units[0] ?? 1;
                $capacityUsedByConsignment += $q * $u;
            }

            $usedCapacity += $capacityUsedByConsignment;

            $consignors[] = [
                'name' => $c->consignor,
                'capacity' => $capacityUsedByConsignment,
            ];
        }



        return view('calendar.calendar-cell-details', compact(
            'truckNumber',
            'date',
            'location',
            'availability',
            'consignors',
            'usedCapacity',
            'totalCapacity',
            'consignments'
        ));
    }

    private function calculateUsedCapacity($consignments)
    {
        $total = 0;
        foreach ($consignments as $c) {
            $qtys = $this->parseToArray($c->quantity);
            $units = $this->parseUnitSpaces($c->unit);
            $max = max(count($qtys), count($units), 1);
            for ($i = 0; $i < $max; $i++) {
                $total += ($qtys[$i] ?? $qtys[0] ?? 0) * ($units[$i] ?? $units[0] ?? 1);
            }
        }
        return $total;
    }
    private function filterCalendarMatrix($matrix)
    {
        return array_filter($matrix, function ($dates) {
            foreach ($dates as $day) {
                if (
                    ($day['MY']['status'] !== 'empty' || $day['MY']['consignors']->isNotEmpty()) ||
                    ($day['SG']['status'] !== 'empty' || $day['SG']['consignors']->isNotEmpty())
                )
                    return true;
            }
            return false;
        });
    }

    private function calculateUtilization($dates)
    {
        $totMyCap = $dates->sum(fn($d) => $d['MY']['origin']);
        $totMyUsed = $dates->sum(fn($d) => $d['MY']['balance']);
        $totSgCap = $dates->sum(fn($d) => $d['SG']['origin']);
        $totSgUsed = $dates->sum(fn($d) => $d['SG']['balance']);

        return [
            'totalMyCapacity' => $totMyCap,
            'totalMyUsed' => $totMyUsed,
            'totalSgCapacity' => $totSgCap,
            'totalSgUsed' => $totSgUsed,
            'myUtilization' => $totMyCap ? (($totMyCap - $totMyUsed) / $totMyCap) * 100 : 0,
            'sgUtilization' => $totSgCap ? ($totSgUsed / $totSgCap) * 100 : 0,
        ];
    }

    private function getColor($util)
    {
        if ($util >= 70)
            return 'text-danger';
        if ($util >= 30)
            return 'text-warning';
        return 'text-success';
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
        // Validate input
        $validated = $request->validate([
            'truck_numbers' => 'required|array',
            'truck_numbers.*' => 'required|string',
            'status' => 'required|string',
            'location' => 'required|string',
            'date' => 'nullable|date',
            'date_range' => 'nullable|string',
        ]);

        \Log::info('Truck numbers received: ', $validated['truck_numbers']);

        $status = strtolower($validated['status']);
        $firstLocation = strtoupper($validated['location']);
        $secondLocation = $firstLocation === 'MY' ? 'SG' : 'MY';

        // Fetch all trucks and subcons
        $truck_select = Truck::select('id', 'number', 'team')
            ->get()
            ->map(fn($t) => [
                'id' => $t->id,
                'number' => $t->number,
                'team' => $t->team,
                'source' => 'truck',
            ]);

        $subcons = Subcon::select('id', 'truck_no as number', 'team')
            ->get()
            ->map(fn($s) => [
                'id' => $s->id,
                'number' => $s->number,
                'team' => $s->team,
                'source' => 'subcon',
            ]);

        $trucks_select = $truck_select->merge($subcons);
        \Log::info('Merged trucks_select:', $trucks_select->toArray());

        foreach ($validated['truck_numbers'] as $truckNumber) {

            // Find the selected truck/subcon in the merged list
            $truckData = $trucks_select->firstWhere('number', $truckNumber);

            if (!$truckData) {
                \Log::warning("Truck/Subcon not found: $truckNumber");
                continue;
            }

            $truckId = $truckData['source'] === 'truck' ? $truckData['id'] : null;
            $subconId = $truckData['source'] === 'subcon' ? $truckData['id'] : null;

            if ($status === 'available') {
                if (empty($validated['date_range'])) {
                    \Log::warning("Date range missing for available status for $truckNumber");
                    continue;
                }

                $rangeParts = explode(' to ', $validated['date_range']);
                $start = Carbon::parse(trim($rangeParts[0]));
                $end = Carbon::parse(trim($rangeParts[1]));
                $current = $start->copy();
                $toggle = true;

                while ($current->lte($end)) {
                    $location = $toggle ? $firstLocation : $secondLocation;

                    // Check if record exists
                    $availabilityQuery = Availability::query()->where('date', $current->format('Y-m-d'));

                    if ($truckId) {
                        $availabilityQuery->where('truck_id', $truckId);
                    } elseif ($subconId) {
                        $availabilityQuery->where('subcon_id', $subconId);
                    }

                    $availability = $availabilityQuery->first();

                    if ($availability) {
                        $availability->update(['status' => $status, 'location' => $location]);
                    } else {
                        Availability::create([
                            'truck_id' => $truckId,
                            'subcon_id' => $subconId,
                            'date' => $current->format('Y-m-d'),
                            'location' => $location,
                            'status' => $status,
                        ]);
                    }

                    $toggle = !$toggle;
                    $current->addDay();
                }
            } else {
                // For single-date statuses (like off-day)
                $request->validate(['date' => 'required|date']);
                $availabilityQuery = Availability::query()->where('date', $validated['date']);

                if ($truckId) {
                    $availabilityQuery->where('truck_id', $truckId);
                } elseif ($subconId) {
                    $availabilityQuery->where('subcon_id', $subconId);
                }

                $availability = $availabilityQuery->first();

                if ($availability) {
                    $availability->update(['status' => $status, 'location' => $firstLocation]);
                } else {
                    Availability::create([
                        'truck_id' => $truckId,
                        'subcon_id' => $subconId,
                        'date' => $validated['date'],
                        'location' => $firstLocation,
                        'status' => $status,
                    ]);
                }
            }

            \Log::info("Processed $truckNumber", ['truck_id' => $truckId, 'subcon_id' => $subconId, 'status' => $status]);
        }

        return redirect()->back()->with('swal', [
            'icon' => 'success',
            'title' => 'Created!',
            'text' => 'Availability records created successfully.'
        ]);
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
        // Validate incoming form
        $validated = $request->validate([
            'driver' => 'nullable|string',
            'availability' => 'nullable|string',
        ]);

        // Find the availability row by ID
        $availability = Availability::find($id);

        if (!$availability) {
            return response()->json(['success' => false, 'message' => 'Availability not found']);
        }

        // Update availability status (if exists in form)
        if ($request->has('availability')) {
            $availability->status = $request->availability;
        }

        // Update driver inside the consignment for that day (if exists)
        if ($request->has('driver') && $request->driver != null) {

            // Update all consignments for that truck/subcon on that date
            $query = Consignment::whereDate('load_date', $availability->date);

            if ($availability->truck_id) {
                $truck = Truck::find($availability->truck_id);
                if ($truck) {
                    $query->where('truck_number', $truck->number);
                }
            }

            if ($availability->subcon_id) {
                $subcon = Subcon::find($availability->subcon_id);
                if ($subcon) {
                    $query->where('pick_truck', $subcon->truck_no);
                }
            }

            $query->update([
                'driver' => $request->driver
            ]);
        }

        // Save changes on availability row
        $availability->save();

        return response()->json(['success' => true]);
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy($truck, $location, $date)
    {
        $truckModel = Truck::where('number', $truck)->first();
        if (!$truckModel) {
            return response()->json(['message' => 'Truck not found.'], 404);
        }

        $deleted = Availability::where('truck_id', $truckModel->id)
            ->where('location', strtoupper($location))
            ->whereDate('date', $date)
            ->delete();

        if ($deleted) {
            return response()->json(['message' => 'Availability deleted successfully.']);
        }

        return response()->json(['message' => 'No record found.'], 404);
    }
    public function updateStatus(Request $request)
    {
        // DEBUG: Log incoming data
        \Log::info('Update Status Request', [
            'all_data' => $request->all(),
            'truck' => $request->input('truck'),
            'date' => $request->input('date'),
            'status' => $request->input('status'),
        ]);

        $validated = $request->validate([
            'truck' => 'required|string',
            'date' => 'required|date',
            'status' => 'nullable|string',
        ]);

        // DEBUG: Log validated data
        \Log::info('Validated Data', $validated);

        // Find the related availability record
        $availability = Availability::where('truck_id', function ($q) use ($validated) {
            $q->select('id')->from('trucks')->where('number', $validated['truck']);
        })
            ->whereDate('date', $validated['date'])
            ->first();

        // DEBUG: Log what we found
        \Log::info('Availability Found', [
            'found' => $availability ? 'yes' : 'no',
            'id' => $availability?->id,
            'old_status' => $availability?->status,
            'new_status' => $validated['status'] ?? 'null',
        ]);

        if ($availability && isset($validated['status'])) {
            $availability->status = $validated['status'];
            $saved = $availability->save();

            // DEBUG: Log save result
            \Log::info('Save Result', [
                'saved' => $saved,
                'current_status' => $availability->fresh()->status,
            ]);
        }

        return response()->json([
            'success' => true,
            'debug' => [
                'availability_id' => $availability?->id,
                'status_updated' => $availability?->status,
            ]
        ]);
    }

    public function deleteAvailability(Request $request)
    {
        $validated = $request->validate([
            'truck' => 'required|string',
            'date' => 'required|date',
        ]);

        // Find and delete the availability record
        $deleted = Availability::where('truck_id', function ($q) use ($validated) {
            $q->select('id')->from('trucks')->where('number', $validated['truck']);
        })
            ->whereDate('date', $validated['date'])
            ->delete();

        if ($deleted) {
            return response()->json(['success' => true, 'message' => 'Availability deleted successfully']);
        }

        return response()->json(['success' => false, 'message' => 'Availability not found'], 404);
    }

}
