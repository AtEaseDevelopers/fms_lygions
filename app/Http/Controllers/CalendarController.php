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
use App\Models\TemporaryTruck;
use Illuminate\Support\Facades\DB;
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
        $drivers = Driver::where('resigned', 0)->orderBy('name')->get();

        $prevStart = $start->copy()->subDays($days)->format('Y-m-d');
        $nextStart = $start->copy()->addDays($days)->format('Y-m-d');

        $truck_select = Truck::select('id', 'number', 'team')
            ->where('is_outsider', 0)
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

        $tempMatrix = $this->buildTempTruckMatrix($dates, $startDateStr, $endDateStr, $consignmentMap, $unitsMap);

        // Temp subcon capacity sums (MY only, mirroring the existing truck cards)
        $tempTotalMy = 0.0;
        $tempUsedMy = 0.0;
        $tempDateMatrix = [];
        foreach ($dates as $d) {
            $tempDateMatrix[$d['date']->format('Y-m-d')] = ['total_capacity' => 0.0, 'used_capacity' => 0.0];
        }
        foreach ($tempMatrix as $label => $byLocation) {
            foreach ($byLocation as $loc => $entry) {
                if ($loc !== 'MY') continue;
                foreach ($entry['cells'] as $dateKey => $cell) {
                    $cellCapacity = (float) ($cell['subcon_capacity'] ?? 0);
                    $cellUsed = (float) ($cell['used_capacity'] ?? 0);
                    $tempTotalMy += $cellCapacity;
                    $tempUsedMy += $cellUsed;
                    if (isset($tempDateMatrix[$dateKey])) {
                        $tempDateMatrix[$dateKey]['total_capacity'] += $cellCapacity;
                        $tempDateMatrix[$dateKey]['used_capacity'] += $cellUsed;
                    }
                }
            }
        }
        $tempUtilizationMy = $tempTotalMy > 0 ? ($tempUsedMy / $tempTotalMy) * 100 : 0;

        // Combined totals across truck calendar (MY) + temp subcon calendar (MY)
        $combinedTotal = ($summary['totalMyCapacity'] ?? 0) + $tempTotalMy;
        $combinedUsed = ($summary['totalMyUsed'] ?? 0) + $tempUsedMy;
        $combinedUtilization = $combinedTotal > 0 ? ($combinedUsed / $combinedTotal) * 100 : 0;

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
            'tempMatrix' => $tempMatrix,
            'tempTotalMy' => $tempTotalMy,
            'tempUsedMy' => $tempUsedMy,
            'tempUtilizationMy' => $tempUtilizationMy,
            'tempDateMatrix' => $tempDateMatrix,
            'combinedTotal' => $combinedTotal,
            'combinedUsed' => $combinedUsed,
            'combinedUtilization' => $combinedUtilization,
            'getColor' => fn($util) => $this->getColor($util),
        ], $summary));
    }

    private function buildTempTruckMatrix($dates, string $startDateStr, string $endDateStr, $consignmentMap, $unitsMap = null): array
    {
        $temps = TemporaryTruck::whereBetween('date', [$startDateStr, $endDateStr])
            ->orderBy('label')
            ->orderBy('date')
            ->get();

        $subconIds = $temps->pluck('subcon_id')->filter()->unique()->values();
        $subconMap = $subconIds->isNotEmpty()
            ? Subcon::whereIn('id', $subconIds)->get()->keyBy('id')
            : collect();

        $parseToArray = fn($v) => $this->parseToArray($v);
        $calcUsed = function ($consignments) use ($parseToArray, $unitsMap) {
            if (!$unitsMap) return 0.0;
            $used = 0.0;
            foreach ($consignments as $c) {
                $qtys = $parseToArray($c->quantity);
                $unitStrings = json_decode($c->unit, true);
                if (!is_array($unitStrings)) {
                    $unitStrings = $parseToArray($c->unit);
                }
                $units = array_map(fn($u) => $unitsMap[trim((string) $u)] ?? 0, $unitStrings);
                $max = max(count($qtys), count($units), 1);
                for ($i = 0; $i < $max; $i++) {
                    $used += ($qtys[$i] ?? $qtys[0] ?? 0) * ($units[$i] ?? $units[0] ?? 1);
                }
            }
            return $used;
        };

        // Shape: [label][location] => ['meta' => [...], 'cells' => [dateKey => cellData]]
        $matrix = [];
        foreach ($temps as $t) {
            $dateKey = (new Carbon($t->date))->format('Y-m-d');
            $consKey = $t->label . '-' . $dateKey;
            $dayCons = $consignmentMap->get($consKey) ?? collect();

            // Filter by location: MY = pick_point != Singapore, SG = pick_point == Singapore
            $matchingCons = $t->location === 'SG'
                ? $dayCons->where('pick_point', 'Singapore')
                : $dayCons->where('pick_point', '!=', 'Singapore');

            if (!isset($matrix[$t->label][$t->location])) {
                $matrix[$t->label][$t->location] = [
                    'meta' => [
                        'chassis_type' => $t->chassis_type,
                        'size' => $t->size,
                    ],
                    'cells' => [],
                ];
            }

            $subcon = $t->subcon_id ? $subconMap->get($t->subcon_id) : null;

            $matrix[$t->label][$t->location]['cells'][$dateKey] = [
                'id' => $t->id,
                'consignors' => $matchingCons->pluck('consignor')->values(),
                'consignment_count' => $matchingCons->count(),
                'subcon_id' => $t->subcon_id,
                'subcon_name' => $subcon?->subcon_name,
                'subcon_capacity' => (float) ($subcon?->floor_space ?? 0),
                'used_capacity' => $calcUsed($matchingCons),
            ];
        }

        return $matrix;
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
        $truckMap = Truck::where('is_outsider', 0)->get()->keyBy('number');
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
        $trucks = Truck::where('is_outsider', 0)->get();
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
        $tempId = $request->query('temp_truck_id');
        if ($tempId) {
            return $this->getTempCellDetails($tempId);
        }

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

    private function getTempCellDetails($tempId)
    {
        $temp = TemporaryTruck::find($tempId);
        if (!$temp) {
            return response('<div class="p-3 text-danger">❌ Temporary truck not found</div>', 404);
        }

        $date = $temp->date->format('Y-m-d');
        $location = $temp->location;
        $truckNumber = $temp->label;

        $consignments = Consignment::with('driverInfo')
            ->where('truck_number', $temp->label)
            ->whereDate('load_date', $date)
            ->get();

        $consignments = $location === 'SG'
            ? $consignments->where('pick_point', 'Singapore')
            : $consignments->where('pick_point', '!=', 'Singapore');

        $consignors = $consignments->map(fn($c) => [
            'name' => $c->consignor,
            'capacity' => null,
        ])->values()->all();

        $availability = null;
        $totalCapacity = 0;
        $usedCapacity = 0;
        $isTemp = true;
        $tempTruckId = $temp->id;
        $tempMeta = [
            'chassis_type' => $temp->chassis_type,
            'size' => $temp->size,
        ];

        // Strict filter on size; chassis_type 'any' acts as a wildcard.
        // No exclusion of subcons already assigned elsewhere — the same subcon can back multiple cells.
        $candidateSubcons = Subcon::query()
            ->when($temp->chassis_type !== 'any', fn($q) => $q->where('chassis_type', $temp->chassis_type))
            ->where('size', $temp->size)
            ->orderBy('truck_no')
            ->get();

        $assignedSubcon = $temp->subcon_id ? Subcon::find($temp->subcon_id) : null;

        return view('calendar.calendar-cell-details', compact(
            'truckNumber',
            'date',
            'location',
            'availability',
            'consignors',
            'usedCapacity',
            'totalCapacity',
            'consignments',
            'isTemp',
            'tempTruckId',
            'tempMeta',
            'candidateSubcons',
            'assignedSubcon'
        ));
    }

    public function assignSubconToTemp(Request $request, $id)
    {
        $validated = $request->validate([
            'subcon_id' => 'nullable|integer|exists:subcons,id',
        ]);

        $temp = TemporaryTruck::find($id);
        if (!$temp) {
            return response()->json(['ok' => false, 'message' => 'Temporary truck not found'], 404);
        }

        if (empty($validated['subcon_id'])) {
            $temp->subcon_id = null;
            $temp->save();
            return response()->json(['ok' => true, 'message' => 'Subcon unassigned']);
        }

        $subcon = Subcon::find($validated['subcon_id']);
        if (!$subcon) {
            return response()->json(['ok' => false, 'message' => 'Subcon not found'], 404);
        }

        $temp->subcon_id = $subcon->id;
        $temp->save();

        return response()->json([
            'ok' => true,
            'message' => "Assigned to {$subcon->subcon_name} ({$subcon->truck_no})",
        ]);
    }

    public function destroyTempTruck($id)
    {
        $temp = TemporaryTruck::find($id);
        if (!$temp) {
            return response()->json(['ok' => false, 'message' => 'Not found'], 404);
        }
        $temp->delete();
        return response()->json(['ok' => true]);
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
            'truck_numbers' => 'nullable|array',
            'truck_numbers.*' => 'string',
            'status' => 'required|string',
            'location' => 'required|string',
            'date' => 'nullable|date',
            'date_range' => 'nullable|string',
            'temp_chassis_type' => 'nullable|string|max:50',
            'temp_size' => 'nullable|string|max:50',
            'temp_qty' => 'nullable|integer|min:0|max:50',
            'labels' => 'nullable|array|max:50',
            'labels.*' => 'nullable|string|max:50',
        ]);

        $truckNumbers = $validated['truck_numbers'] ?? [];
        $tempLabels = array_values(array_filter(
            array_map(fn($l) => trim((string) $l), $validated['labels'] ?? []),
            fn($l) => $l !== ''
        ));
        $tempType = $validated['temp_chassis_type'] ?? null;
        $tempSize = $validated['temp_size'] ?? null;

        if (empty($truckNumbers) && empty($tempLabels)) {
            return back()->with('swal', [
                'icon' => 'error',
                'title' => 'Nothing to save',
                'text' => 'Pick at least one truck/subcon or add temporary subcon labels.',
            ]);
        }

        \Log::info('Truck numbers received: ', $truckNumbers);

        $status = strtolower($validated['status']);
        $firstLocation = strtoupper($validated['location']);
        $secondLocation = $firstLocation === 'MY' ? 'SG' : 'MY';

        // Fetch all trucks and subcons
        $truck_select = Truck::select('id', 'number', 'team')
            ->where('is_outsider', 0)
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

        foreach ($truckNumbers as $truckNumber) {

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

        // Temporary subcons: only created when status=available with a date range
        $tempCreated = 0;
        if (!empty($tempLabels)) {
            if ($status !== 'available') {
                return back()->with('swal', [
                    'icon' => 'error',
                    'title' => 'Temp subcons need status = available',
                    'text' => 'Set status to Available (with a date range) to add temporary subcons.',
                ]);
            }
            if (empty($tempType) || empty($tempSize)) {
                return back()->with('swal', [
                    'icon' => 'error',
                    'title' => 'Missing type or size',
                    'text' => 'Select truck type and size for the temporary subcons.',
                ]);
            }
            if (empty($validated['date_range'])) {
                return back()->with('swal', [
                    'icon' => 'error',
                    'title' => 'Missing date range',
                    'text' => 'Pick a date range for temporary subcons.',
                ]);
            }

            $duplicateLabels = array_diff_assoc($tempLabels, array_unique($tempLabels));
            if (!empty($duplicateLabels)) {
                return back()->with('swal', [
                    'icon' => 'error',
                    'title' => 'Duplicate labels',
                    'text' => 'Labels must be unique within the form: ' . implode(', ', array_unique($duplicateLabels)),
                ]);
            }

            $rangeParts = explode(' to ', $validated['date_range']);
            if (count($rangeParts) !== 2) {
                return back()->with('swal', [
                    'icon' => 'error',
                    'title' => 'Invalid date range',
                    'text' => 'Could not parse the date range.',
                ]);
            }
            $tempStart = Carbon::parse(trim($rangeParts[0]));
            $tempEnd = Carbon::parse(trim($rangeParts[1]));

            $tempDates = [];
            for ($d = $tempStart->copy(); $d->lte($tempEnd); $d->addDay()) {
                $tempDates[] = $d->format('Y-m-d');
            }

            $existing = TemporaryTruck::whereIn('date', $tempDates)
                ->where('location', $firstLocation)
                ->whereIn('label', $tempLabels)
                ->get();
            if ($existing->isNotEmpty()) {
                $conflicts = $existing->map(fn($e) => $e->label . ' on ' . $e->date->format('Y-m-d'))->all();
                return back()->with('swal', [
                    'icon' => 'error',
                    'title' => 'Label already exists',
                    'text' => 'These (label, date) pairs already exist for ' . $firstLocation . ': ' . implode('; ', $conflicts),
                ]);
            }

            try {
                DB::transaction(function () use ($tempDates, $firstLocation, $tempLabels, $tempType, $tempSize, &$tempCreated) {
                    foreach ($tempDates as $date) {
                        foreach ($tempLabels as $label) {
                            TemporaryTruck::create([
                                'date' => $date,
                                'location' => $firstLocation,
                                'chassis_type' => $tempType,
                                'size' => $tempSize,
                                'label' => $label,
                                'floor_space' => null,
                            ]);
                            $tempCreated++;
                        }
                    }
                });
            } catch (\Throwable $e) {
                \Log::error('Failed to create temporary trucks', ['error' => $e->getMessage()]);
                return back()->with('swal', [
                    'icon' => 'error',
                    'title' => 'Failed to save temp subcons',
                    'text' => $e->getMessage(),
                ]);
            }
        }

        $msg = 'Availability records created successfully.';
        if ($tempCreated > 0) {
            $msg .= " Added {$tempCreated} temporary subcon record(s).";
        }
        return redirect()->back()->with('swal', [
            'icon' => 'success',
            'title' => 'Created!',
            'text' => $msg,
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
        $truckId = Truck::where('number', $validated['truck'])->value('id');

        $availability = Availability::where('truck_id', $truckId)
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
        $truckId = Truck::where('number', $validated['truck'])->value('id');

        $deleted = Availability::where('truck_id', $truckId)
            ->whereDate('date', $validated['date'])
            ->delete();

        if ($deleted) {
            return response()->json(['success' => true, 'message' => 'Availability deleted successfully']);
        }

        return response()->json(['success' => false, 'message' => 'Availability not found'], 404);
    }

}
