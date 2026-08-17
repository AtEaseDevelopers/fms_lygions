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
use App\Models\DriverHoliday;
use App\Models\Subcon;
use App\Models\TemporaryTruck;
use App\Support\AvailabilityMonth;
use App\Support\DateRange;
use Illuminate\Support\Facades\DB;
class CalendarController extends Controller
{

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $days = $this->getDays($request);
        $layout = $this->getLayout($request, $days);
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

        // Build driver-leave lookup: [driver_id][Y-m-d] => true for any DriverHoliday
        // whose [start_date, end_date] overlaps the visible window.
        $driverLeaveMap = [];
        $leaves = DriverHoliday::where('start_date', '<=', $endDateStr)
            ->where('end_date', '>=', $startDateStr)
            ->get(['driver_id', 'start_date', 'end_date']);
        $windowStart = Carbon::parse($startDateStr);
        $windowEnd = Carbon::parse($endDateStr);
        foreach ($leaves as $leave) {
            $from = Carbon::parse($leave->start_date)->max($windowStart);
            $to = Carbon::parse($leave->end_date)->min($windowEnd);
            for ($d = $from->copy(); $d->lte($to); $d->addDay()) {
                $driverLeaveMap[$leave->driver_id][$d->format('Y-m-d')] = true;
            }
        }

        // Driver name → id lookup, for cases where a consignment has an explicit driver name.
        $driverIdByName = Driver::all(['id', 'name'])
            ->mapWithKeys(fn ($d) => [strtolower(trim((string) $d->name)) => $d->id])
            ->all();

        // Each truck has a default driver via Driver.default_lorry_id. When a consignment
        // has no explicit driver, this default driver is the effective driver for the cell
        // and is what the on-leave check should match against.
        $defaultDriverByTruckId = Driver::whereNotNull('default_lorry_id')
            ->get(['id', 'name', 'default_lorry_id'])
            ->keyBy('default_lorry_id');

        // now call buildCalendarMatrix with maps
        [$calendarMatrix, $trucks] = $this->buildCalendarMatrix($dates, $truckMap, $availabilityMap, $consignmentMap, $unitsMap, $driverLeaveMap, $driverIdByName, $defaultDriverByTruckId);


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
                    // Skip cells where the truck is committed on the opposite side today
                    // (flow_off) — it isn't available here, so don't count its capacity.
                    $flowOff = $day[$loc]['flow_off'] ?? false;
                    if (!$flowOff && in_array($status, ['available', 'occupied'])) {
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

        // Prev/Next step by one week (7 days), so a 14-day view slides a week at
        // a time and overlaps the previous window instead of jumping a full 2 weeks.
        $navStep = 7;
        $prevStart = $start->copy()->subDays($navStep)->format('Y-m-d');
        $nextStart = $start->copy()->addDays($navStep)->format('Y-m-d');

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

        // Truck calendar header used capacity (MY), mirroring temp subcon top-row calc:
        // sum used_capacity across every truck-date cell without filtering by status.
        $truckDateMatrix = [];
        foreach ($dates as $d) {
            $truckDateMatrix[$d['date']->format('Y-m-d')] = ['used_capacity' => 0.0];
        }
        foreach ($calendarMatrix as $truckNumber => $truckDates) {
            foreach ($truckDates as $dateKey => $day) {
                if (!isset($truckDateMatrix[$dateKey])) continue;
                $truckDateMatrix[$dateKey]['used_capacity'] += (float) ($day['MY']['used_capacity'] ?? 0);
            }
        }

        // Number of lorries available per date for the header top row. Owned trucks only
        // (subcons/temp trucks excluded), counted with the same rule as getAvailableTrucks.
        $ownedTruckIds = array_flip($truckMap->pluck('id')->all());
        $availableTruckCounts = [];
        foreach ($dates as $d) {
            $availableTruckCounts[$d['date']->format('Y-m-d')] = 0;
        }
        foreach ($availabilities->groupBy(fn($a) => (new Carbon($a->date))->format('Y-m-d')) as $dateKey => $recs) {
            if (!array_key_exists($dateKey, $availableTruckCounts)) continue;
            $ownedRecs = $recs
                ->filter(fn($r) => $r->truck_id !== null && isset($ownedTruckIds[$r->truck_id]))
                ->map(fn($r) => ['truck_id' => $r->truck_id, 'status' => $r->status])
                ->all();
            $availableTruckCounts[$dateKey] = self::availableTruckCount($ownedRecs);
        }

        // Temp subcon capacity sums (MY only, mirroring the existing truck cards)
        $tempTotalMy = 0.0;
        $tempUsedMy = 0.0;
        $tempDateMatrix = [];
        foreach ($dates as $d) {
            $tempDateMatrix[$d['date']->format('Y-m-d')] = ['total_capacity' => 0.0, 'used_capacity' => 0.0];
        }
        foreach ($tempMatrix as $label => $byRow) {
            foreach ($byRow as $rowKey => $entry) {
                if (($entry['location'] ?? null) !== 'MY') continue;
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
            'layout' => $layout,
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
            'truckDateMatrix' => $truckDateMatrix,
            'availableTruckCounts' => $availableTruckCounts,
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

        $calcUsed = fn($consignments) => $this->calcUsedCapacity($consignments, $unitsMap);

        // Shape: [label][rowKey] => ['location' => ..., 'meta' => [...], 'cells' => [dateKey => cellData]]
        // rowKey = "location|chassis_type|size" so the same label with different
        // type/size on different dates renders as separate rows (the unique DB
        // constraint is per date+location+label only).
        $matrix = [];
        foreach ($temps as $t) {
            $dateKey = (new Carbon($t->date))->format('Y-m-d');
            $consKey = $t->label . '-' . $dateKey;
            $dayCons = $consignmentMap->get($consKey) ?? collect();

            $matchingCons = $t->location === 'SG'
                ? $dayCons->where('pick_point', 'Singapore')
                : $dayCons->where('pick_point', '!=', 'Singapore');

            $rowKey = $t->location . '|' . $t->chassis_type . '|' . $t->size;

            if (!isset($matrix[$t->label][$rowKey])) {
                $matrix[$t->label][$rowKey] = [
                    'location' => $t->location,
                    'meta' => [
                        'chassis_type' => $t->chassis_type,
                        'size' => $t->size,
                    ],
                    'cells' => [],
                ];
            }

            $subcon = $t->subcon_id ? $subconMap->get($t->subcon_id) : null;

            $matrix[$t->label][$rowKey]['cells'][$dateKey] = [
                'id' => $t->id,
                'consignors' => $matchingCons->pluck('consignor')->values(),
                'consignment_count' => $matchingCons->count(),
                'subcon_id' => $t->subcon_id,
                'subcon_name' => $subcon?->subcon_name,
                'subcon_capacity' => (float) ($t->floor_space ?? $subcon?->floor_space ?? 0),
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

    private function getLayout(Request $request, int $days): string
    {
        if ($days !== 7) {
            return 'horizontal';
        }
        return $request->query('layout') === 'vertical' ? 'vertical' : 'horizontal';
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

    private function calcUsedCapacity(iterable $consignments, $unitsMap): float
    {
        if (!$unitsMap) return 0.0;
        $used = 0.0;
        foreach ($consignments as $c) {
            $qtys = $this->parseToArray($c->quantity);
            $unitStrings = json_decode($c->unit, true);
            if (!is_array($unitStrings)) {
                $unitStrings = $this->parseToArray($c->unit);
            }
            $units = array_map(fn($u) => $unitsMap[trim((string) $u)] ?? 0, $unitStrings);
            $max = max(count($qtys), count($units), 1);
            for ($i = 0; $i < $max; $i++) {
                $used += ($qtys[$i] ?? $qtys[0] ?? 0) * ($units[$i] ?? $units[0] ?? 1);
            }
        }
        return $used;
    }

    private function buildCalendarMatrix($dates, $truckMap, $availabilityMap, $consignmentMap, $unitsMap, $driverLeaveMap = [], $driverIdByName = [], $defaultDriverByTruckId = null)
    {
        $trucks = Truck::where('is_outsider', 0)->get();
        $calendarMatrix = [];

        foreach ($trucks as $truck) {
            $defaultDriver = $defaultDriverByTruckId ? $defaultDriverByTruckId->get($truck->id) : null;
            $originalDriverName = $defaultDriver?->name;

            foreach ($dates as $date) {
                $formattedDate = $date['date']->format('Y-m-d');
                // Trucks are NOT available by default. A truck only shows on a day when it
                // has an explicit availability record (created per-month for weekdays) or a
                // consignment. Everything else is empty (not available).
                $defaultStatus = 'empty';
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

                $calcUsed = fn($consignments) => $this->calcUsedCapacity($consignments, $unitsMap);

                $usedMy = $calcUsed($myConsignments);
                $usedSg = $calcUsed($sgConsignments);

                // Effective driver applies to the whole truck/date — updateStatus writes the
                // driver to every consignment for the truck/date regardless of MY/SG, and the
                // cell-details modal reads them unfiltered. The on-leave indicator must match,
                // otherwise a side with no consignments falls back to the truck's default
                // driver even when the other side has been reassigned.
                $consDriverRaw = (string) (
                    $dayCons->pluck('driver')->filter(fn ($v) => trim((string) $v) !== '')->first() ?? ''
                );
                $consKey = strtolower(trim($consDriverRaw));
                $consDriverId = $consKey !== '' ? ($driverIdByName[$consKey] ?? null) : null;

                if ($consDriverId !== null) {
                    $effDriverId = $consDriverId;
                    $effDriverName = $consDriverRaw;
                } elseif ($defaultDriver) {
                    $effDriverId = $defaultDriver->id;
                    $effDriverName = $defaultDriver->name;
                } else {
                    $effDriverId = null;
                    $effDriverName = null;
                }
                $onLeave = $effDriverId !== null && !empty($driverLeaveMap[$effDriverId][$formattedDate]);

                $isOverridden = $originalDriverName !== null
                    && $effDriverName !== null
                    && strtolower(trim((string) $effDriverName)) !== strtolower(trim((string) $originalDriverName));

                $myDriverId = $sgDriverId = $effDriverId;
                $myDriverName = $sgDriverName = $effDriverName;
                $myOnLeave = $sgOnLeave = $onLeave;

                $calendarMatrix[$truck->number][$formattedDate] = [
                    'MY' => [
                        'status' => $myAvailability->status ?? ($myConsignments->isNotEmpty() ? 'occupied' : $defaultStatus),
                        // Distinguishes an explicit availability record from a default-available
                        // weekday cell (which has no row to drag/relocate).
                        'has_record' => $myAvailability !== null,
                        'consignors' => $myConsignments->pluck('consignor'),
                        'total_capacity' => optional($truck)->floor_space ?? 0,
                        'used_capacity' => $usedMy,
                        'driver_name' => $myDriverName,
                        'driver_id' => $myDriverId,
                        'driver_on_leave' => $myOnLeave,
                        'original_driver_name' => $originalDriverName,
                        'driver_overridden' => $isOverridden,
                        'remarks' => $myAvailability->remarks ?? null,
                    ],
                    'SG' => [
                        'status' => $sgAvailability->status ?? ($sgConsignments->isNotEmpty() ? 'occupied' : $defaultStatus),
                        'has_record' => $sgAvailability !== null,
                        'consignors' => $sgConsignments->pluck('consignor'),
                        'total_capacity' => optional($truck)->floor_space ?? 0,
                        'used_capacity' => $usedSg,
                        'driver_name' => $sgDriverName,
                        'driver_id' => $sgDriverId,
                        'driver_on_leave' => $sgOnLeave,
                        'original_driver_name' => $originalDriverName,
                        'driver_overridden' => $isOverridden,
                        'remarks' => $sgAvailability->remarks ?? null,
                    ],
                ];
            }

            // Layer the MY<->SG "flow" greying on top of this truck's cells.
            if (isset($calendarMatrix[$truck->number])) {
                $this->applyAvailabilityFlow($calendarMatrix[$truck->number], $dates);
                $this->applyIsolatedDayFlags($calendarMatrix[$truck->number], $dates);
            }
        }

        return [$calendarMatrix, $trucks];
    }

    /**
     * When a truck is assigned (has consignments) on a day, it is committed to that
     * location and unavailable on the opposite one. As it moves MY<->SG on following
     * days the unavailable side alternates, tracing a zig-zag of grey cells until the
     * work-week ends (weekends reset it). Marks those cells with 'flow_off' => true.
     *
     * On the assignment day (k=0) the opposite side is greyed; each following weekday
     * the greyed side flips. A real assignment always keeps its solid colour and is
     * never overwritten by the flow.
     */
    private function applyAvailabilityFlow(array &$truckDates, $dates): void
    {
        $ordered = $dates->map(fn($d) => $d['date']->format('Y-m-d'))->values()->all();
        $opposite = ['MY' => 'SG', 'SG' => 'MY'];
        $grey = [];
        $n = count($ordered);

        for ($i = 0; $i < $n; $i++) {
            $dk = $ordered[$i];
            if (!isset($truckDates[$dk])) continue;

            foreach (['MY', 'SG'] as $loc) {
                $assigned = ($truckDates[$dk][$loc]['consignors'] ?? collect())->isNotEmpty();
                if (!$assigned) continue;

                // Propagate the alternating grey forward until the weekend.
                for ($j = $i; $j < $n; $j++) {
                    $djk = $ordered[$j];
                    if (Carbon::parse($djk)->dayOfWeekIso >= 6) break; // Sat/Sun stop the flow
                    $greySide = (($j - $i) % 2 === 0) ? $opposite[$loc] : $loc;
                    $grey[$djk][$greySide] = true;
                }
            }
        }

        foreach ($grey as $djk => $sides) {
            foreach (array_keys($sides) as $side) {
                if (!isset($truckDates[$djk][$side])) continue;
                // A real assignment always wins over the flow-grey.
                if (($truckDates[$djk][$side]['consignors'] ?? collect())->isNotEmpty()) continue;
                $truckDates[$djk][$side]['flow_off'] = true;
            }
        }
    }
    /**
     * Availability statuses that mark a truck as NOT operating that day. Mirrors
     * ConsignmentController::UNAVAILABLE_STATUSES.
     */
    public const BLOCKING_STATUSES = ['off-day', 'maintenance', 'holiday', 'breakdown', 'inspection'];

    /**
     * A truck side is "available" (usable) on a day only for positive statuses.
     * Everything else — no record ('empty') or a blocking arrangement — means the
     * truck cannot work that side.
     */
    public static function sideStatusAvailable(?string $status): bool
    {
        if ($status === null || $status === 'empty') {
            return false;
        }
        return !in_array($status, self::BLOCKING_STATUSES, true);
    }

    /**
     * Count how many distinct lorries are available for a single date, given that
     * date's availability records. A lorry is available when it has a positive record
     * (available, express, saturday-loading/unloading) and no blocking record; a
     * blocking record on either side wins. Each record is ['truck_id' => int, 'status'
     * => string]. Callers pass owned-truck records only. Mirrors the definition used by
     * ConsignmentController::getAvailableTrucks.
     */
    public static function availableTruckCount(array $records): int
    {
        $off = [];
        $on = [];
        foreach ($records as $r) {
            $truckId = $r['truck_id'] ?? null;
            if ($truckId === null) {
                continue;
            }
            if (in_array($r['status'] ?? null, self::BLOCKING_STATUSES, true)) {
                $off[$truckId] = true;
            } else {
                $on[$truckId] = true;
            }
        }

        $count = 0;
        foreach (array_keys($on) as $truckId) {
            if (!isset($off[$truckId])) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Dates that should show the "isolated day" cross icon: a day where the truck is
     * available on BOTH MY and SG, yet stranded — fully unavailable (neither MY nor SG
     * usable) on BOTH the previous and next calendar day, e.g. a both-sides-available
     * 17 Aug flanked by fully-off 16 Aug and 18 Aug. Requiring the middle day to be
     * available on both sides keeps a run of off/empty days from flagging every inner
     * day. Edge days (no neighbour in the window) are never flagged.
     *
     * @param  array  $orderedDates    ordered 'Y-m-d' strings for the visible window
     * @param  array  $statusesByDate  ['Y-m-d' => ['MY' => status, 'SG' => status]]
     * @return array  flagged 'Y-m-d' strings
     */
    public static function isolatedDayDates(array $orderedDates, array $statusesByDate): array
    {
        $bothAvailable = function ($dateKey) use ($statusesByDate) {
            if (!isset($statusesByDate[$dateKey])) {
                return false;
            }
            return self::sideStatusAvailable($statusesByDate[$dateKey]['MY'] ?? null)
                && self::sideStatusAvailable($statusesByDate[$dateKey]['SG'] ?? null);
        };
        // Fully off = present in the window but neither side usable.
        $fullyOff = function ($dateKey) use ($statusesByDate) {
            if (!isset($statusesByDate[$dateKey])) {
                return false;
            }
            return !self::sideStatusAvailable($statusesByDate[$dateKey]['MY'] ?? null)
                && !self::sideStatusAvailable($statusesByDate[$dateKey]['SG'] ?? null);
        };

        $flagged = [];
        $n = count($orderedDates);
        for ($i = 1; $i < $n - 1; $i++) {
            $curr = $orderedDates[$i];
            if ($bothAvailable($curr) && $fullyOff($orderedDates[$i - 1]) && $fullyOff($orderedDates[$i + 1])) {
                $flagged[] = $curr;
            }
        }

        return $flagged;
    }

    /**
     * Mark both the MY and SG cells of each isolated day (see isolatedDayDates) so the
     * cell view can render a warning cross for the whole day.
     */
    private function applyIsolatedDayFlags(array &$truckDates, $dates): void
    {
        $ordered = $dates->map(fn($d) => $d['date']->format('Y-m-d'))->values()->all();

        $statusesByDate = [];
        foreach ($ordered as $dateKey) {
            if (!isset($truckDates[$dateKey])) {
                continue;
            }
            $statusesByDate[$dateKey] = [
                'MY' => $truckDates[$dateKey]['MY']['status'] ?? 'empty',
                'SG' => $truckDates[$dateKey]['SG']['status'] ?? 'empty',
            ];
        }

        foreach (self::isolatedDayDates($ordered, $statusesByDate) as $dateKey) {
            $truckDates[$dateKey]['MY']['isolated'] = true;
            $truckDates[$dateKey]['SG']['isolated'] = true;
        }
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

        // "any"/"Any" acts as a wildcard on both chassis_type and size.
        // No exclusion of subcons already assigned elsewhere — the same subcon can back multiple cells.
        $isAny = fn($v) => $v !== null && strcasecmp((string) $v, 'any') === 0;
        $candidateSubcons = Subcon::query()
            ->when(!$isAny($temp->chassis_type), fn($q) => $q->where('chassis_type', $temp->chassis_type))
            ->when(!$isAny($temp->size), fn($q) => $q->where('size', $temp->size))
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
                    ($day['SG']['status'] !== 'empty' || $day['SG']['consignors']->isNotEmpty()) ||
                    !empty($day['MY']['driver_on_leave']) ||
                    !empty($day['SG']['driver_on_leave'])
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
            'myUtilization' => $totMyCap ? ($totMyUsed / $totMyCap) * 100 : 0,
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
            'arr_date_range' => 'nullable|string',
            'month' => 'nullable|string',
            'date_range' => 'nullable|string',
            'temp_chassis_type' => 'nullable|array',
            'temp_chassis_type.*' => 'nullable|string|max:50',
            'temp_size' => 'nullable|array',
            'temp_size.*' => 'nullable|string|max:50',
            'temp_qty' => 'nullable|array',
            'temp_qty.*' => 'nullable|integer|min:0|max:50',
            'labels' => 'nullable|array',
            'labels.*' => 'nullable|array|max:50',
            'labels.*.*' => 'nullable|string|max:50',
            'floor_space' => 'nullable|array',
            'floor_space.*' => 'nullable|array',
            'floor_space.*.*' => 'nullable|numeric|min:0',
        ]);

        $truckNumbers = $validated['truck_numbers'] ?? [];

        // Build $tempRows from parallel arrays. Each row: [type, size, labels[]].
        // Skip rows with no non-empty labels (qty 0).
        $tempTypes = $validated['temp_chassis_type'] ?? [];
        $tempSizes = $validated['temp_size'] ?? [];
        $tempLabelsByRow = $validated['labels'] ?? [];
        $tempFsByRow = $validated['floor_space'] ?? [];

        $tempRows = [];
        foreach ($tempTypes as $i => $type) {
            $rowLabels = $tempLabelsByRow[$i] ?? [];
            $rowFs = $tempFsByRow[$i] ?? [];
            $items = [];
            foreach ($rowLabels as $j => $rawLabel) {
                $label = trim((string) $rawLabel);
                if ($label === '') {
                    continue;
                }
                $fsRaw = $rowFs[$j] ?? null;
                $fs = ($fsRaw === null || $fsRaw === '') ? null : (float) $fsRaw;
                $items[] = ['label' => $label, 'floor_space' => $fs];
            }
            if (empty($items)) {
                continue;
            }
            $tempRows[] = [
                'type'  => $type,
                'size'  => $tempSizes[$i] ?? null,
                'items' => $items,
            ];
        }

        if (empty($truckNumbers) && empty($tempRows)) {
            return back()->with('swal', [
                'icon' => 'error',
                'title' => 'Nothing to save',
                'text' => 'Pick at least one truck/subcon or add temporary subcon labels.',
            ]);
        }

        \Log::info('Truck numbers received: ', $truckNumbers);

        $status = strtolower($validated['status']);
        $firstLocation = strtoupper($validated['location']);

        // Single-date statuses (off-day, maintenance, driver-leave and the other
        // special arrangements) now accept a date range so an arrangement can span
        // several days at once. Falls back to the legacy single `date` field.
        $arrangementDates = DateRange::expand($validated['arr_date_range'] ?? null);
        if (empty($arrangementDates) && !empty($validated['date'])) {
            $arrangementDates = [$validated['date']];
        }

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

            // Driver leave ties into the existing Driver Holiday system rather than
            // creating an availability row, so it stays consistent with the on-leave logic.
            if ($status === 'driver-leave') {
                if (empty($arrangementDates)) {
                    return back()->with('swal', [
                        'icon' => 'error',
                        'title' => 'Missing date',
                        'text' => 'Pick a date (or date range) for the driver leave.',
                    ]);
                }
                if (!$truckId) {
                    \Log::warning("Driver leave needs a truck (not subcon): $truckNumber");
                    continue;
                }
                $driver = Driver::where('default_lorry_id', $truckId)->first();
                if (!$driver) {
                    \Log::warning("No default driver for truck $truckNumber; driver-leave skipped");
                    continue;
                }
                // A range maps to a single holiday spanning the first to last day.
                DriverHoliday::firstOrCreate(
                    [
                        'driver_id'  => $driver->id,
                        'start_date' => $arrangementDates[0],
                        'end_date'   => $arrangementDates[count($arrangementDates) - 1],
                    ],
                    ['remarks' => 'Driver leave (calendar)']
                );
                continue;
            }

            if ($status === 'available') {
                if (empty($validated['month'])) {
                    \Log::warning("Month missing for available status for $truckNumber");
                    continue;
                }

                // Availability is created per-month for weekdays only, alternating MY/SG
                // from the chosen first location. See App\Support\AvailabilityMonth.
                $schedule = AvailabilityMonth::weekdaySchedule($validated['month'], $firstLocation);

                foreach ($schedule as $slot) {
                    // Check if record exists
                    $availabilityQuery = Availability::query()->where('date', $slot['date']);

                    if ($truckId) {
                        $availabilityQuery->where('truck_id', $truckId);
                    } elseif ($subconId) {
                        $availabilityQuery->where('subcon_id', $subconId);
                    }

                    $availability = $availabilityQuery->first();

                    if ($availability) {
                        $availability->update(['status' => $status, 'location' => $slot['location']]);
                    } else {
                        Availability::create([
                            'truck_id' => $truckId,
                            'subcon_id' => $subconId,
                            'date' => $slot['date'],
                            'location' => $slot['location'],
                            'status' => $status,
                        ]);
                    }
                }
            } else {
                // Single-date statuses (off-day, maintenance and the other special
                // arrangements). Each day in the range gets its own availability row.
                if (empty($arrangementDates)) {
                    return back()->with('swal', [
                        'icon' => 'error',
                        'title' => 'Missing date',
                        'text' => 'Pick a date (or date range) for this arrangement.',
                    ]);
                }

                foreach ($arrangementDates as $arrDate) {
                    $availabilityQuery = Availability::query()->where('date', $arrDate);

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
                            'date' => $arrDate,
                            'location' => $firstLocation,
                            'status' => $status,
                        ]);
                    }
                }
            }

            \Log::info("Processed $truckNumber", ['truck_id' => $truckId, 'subcon_id' => $subconId, 'status' => $status]);
        }

        // Temporary subcons: only created when status=available with a date range
        $tempCreated = 0;
        if (!empty($tempRows)) {
            if ($status !== 'available') {
                return back()->with('swal', [
                    'icon' => 'error',
                    'title' => 'Temp subcons need status = available',
                    'text' => 'Set status to Available (with a date range) to add temporary subcons.',
                ]);
            }

            foreach ($tempRows as $idx => $row) {
                if (empty($row['type']) || empty($row['size'])) {
                    return back()->with('swal', [
                        'icon' => 'error',
                        'title' => 'Missing type or size',
                        'text' => 'Select truck type and size for temporary subcon row ' . ($idx + 1) . '.',
                    ]);
                }
            }

            if (empty($validated['date_range'])) {
                return back()->with('swal', [
                    'icon' => 'error',
                    'title' => 'Missing date range',
                    'text' => 'Pick a date range for temporary subcons.',
                ]);
            }

            $allLabels = array_merge(...array_map(fn($r) => array_column($r['items'], 'label'), $tempRows));
            $duplicateLabels = array_diff_assoc($allLabels, array_unique($allLabels));
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
                ->whereIn('label', $allLabels)
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
                DB::transaction(function () use ($tempDates, $firstLocation, $tempRows, &$tempCreated) {
                    foreach ($tempDates as $date) {
                        foreach ($tempRows as $row) {
                            foreach ($row['items'] as $item) {
                                TemporaryTruck::create([
                                    'date' => $date,
                                    'location' => $firstLocation,
                                    'chassis_type' => $row['type'],
                                    'size' => $row['size'],
                                    'label' => $item['label'],
                                    'floor_space' => $item['floor_space'],
                                ]);
                                $tempCreated++;
                            }
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
            'driver' => $request->input('driver'),
        ]);

        $validated = $request->validate([
            'truck' => 'required|string',
            'date' => 'required|date',
            'location' => 'nullable|in:MY,SG',
            'status' => 'nullable|string',
            'driver' => 'nullable|string',
            'remarks' => 'nullable|string|max:500',
        ]);

        // DEBUG: Log validated data
        \Log::info('Validated Data', $validated);

        // Find the related availability record. When the caller names a location
        // (MY/SG) target that exact record so a remark on one side never touches the
        // other; otherwise fall back to the first record for the truck+date.
        $truckId = Truck::where('number', $validated['truck'])->value('id');

        $availability = Availability::where('truck_id', $truckId)
            ->whereDate('date', $validated['date'])
            ->when(!empty($validated['location']), fn($q) => $q->where('location', $validated['location']))
            ->first();

        // DEBUG: Log what we found
        \Log::info('Availability Found', [
            'found' => $availability ? 'yes' : 'no',
            'id' => $availability?->id,
            'old_status' => $availability?->status,
            'new_status' => $validated['status'] ?? 'null',
        ]);

        if ($availability) {
            if (isset($validated['status'])) {
                $availability->status = $validated['status'];
            }
            // A blank remark clears the note (stored as NULL, not an empty string).
            if (array_key_exists('remarks', $validated)) {
                $remark = trim((string) $validated['remarks']);
                $availability->remarks = $remark === '' ? null : $remark;
            }
            $saved = $availability->save();

            // DEBUG: Log save result
            \Log::info('Save Result', [
                'saved' => $saved,
                'current_status' => $availability->fresh()->status,
            ]);
        }

        // Persist the explicit driver assignment to all consignments for this
        // truck/date. The cell-details dropdown posts the driver's name string.
        if (array_key_exists('driver', $validated)) {
            $driverUpdated = Consignment::where('truck_number', $validated['truck'])
                ->whereDate('load_date', $validated['date'])
                ->update(['driver' => $validated['driver']]);

            \Log::info('Driver Update', [
                'driver' => $validated['driver'],
                'rows_updated' => $driverUpdated,
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

    // Drag-drop move: relocate every consignment in a source cell (truck+date+location)
    // to a target cell. Unlike updateStatus, the driver is only carried on the moved rows
    // so the other MY/SG side of the target truck/date isn't clobbered.
    public function moveCell(Request $request)
    {
        $v = $request->validate([
            'source_truck'    => 'required|string',
            'source_date'     => 'required|date',
            'source_location' => 'required|in:MY,SG',
            'target_truck'    => 'required|string',
            'target_date'     => 'required|date',
            'target_location' => 'required|in:MY,SG',
        ]);

        if ($v['source_truck'] === $v['target_truck']
            && $v['source_date'] === $v['target_date']
            && $v['source_location'] === $v['target_location']) {
            return response()->json(['message' => 'Source and target are the same cell.'], 422);
        }

        // Same-truck only — cross-truck reassignment goes through the cell-details modal.
        if ($v['source_truck'] !== $v['target_truck']) {
            return response()->json([
                'message' => 'Consignments can only move within the same truck.',
            ], 422);
        }

        // Same-location only — MY↔SG migrations would have to rewrite pick_point (NOT NULL,
        // no sensible default for SG→MY), so they go through the cell-details modal instead.
        if ($v['source_location'] !== $v['target_location']) {
            return response()->json([
                'message' => 'Drag-drop can only shift dates. Use the cell modal to change MY/SG.',
            ], 422);
        }

        $targetTruck = Truck::where('number', $v['target_truck'])->where('is_outsider', 0)->first();
        if (!$targetTruck) {
            return response()->json(['message' => 'Target truck not found.'], 404);
        }

        return DB::transaction(function () use ($v, $targetTruck) {
            $srcOp = $v['source_location'] === 'SG' ? '=' : '!=';
            $tgtOp = $v['target_location'] === 'SG' ? '=' : '!=';

            $sourceCons = Consignment::where('truck_number', $v['source_truck'])
                ->whereDate('load_date', $v['source_date'])
                ->where('pick_point', $srcOp, 'Singapore')
                ->lockForUpdate()
                ->get();

            if ($sourceCons->isEmpty()) {
                return response()->json(['message' => 'Source cell has no consignments to move.'], 422);
            }

            $targetAvail = Availability::where('truck_id', $targetTruck->id)
                ->whereDate('date', $v['target_date'])
                ->where('location', $v['target_location'])
                ->first();
            if ($targetAvail && in_array($targetAvail->status, ['off-day', 'maintenance'], true)) {
                return response()->json(['message' => "Target cell is {$targetAvail->status}."], 422);
            }

            $unitsMap = Unit::all()->pluck('space', 'unit')
                ->mapWithKeys(fn($val, $key) => [trim($key) => (float) $val]);
            $existingTargetCons = Consignment::where('truck_number', $v['target_truck'])
                ->whereDate('load_date', $v['target_date'])
                ->where('pick_point', $tgtOp, 'Singapore')
                ->get();
            $usedTarget = $this->calcUsedCapacity($existingTargetCons, $unitsMap);
            $incoming   = $this->calcUsedCapacity($sourceCons, $unitsMap);
            $total      = (float) ($targetTruck->floor_space ?? 0);
            if (($usedTarget + $incoming) > $total) {
                return response()->json([
                    'message' => 'Target truck capacity exceeded ('
                        . number_format($usedTarget + $incoming, 1) . ' / '
                        . number_format($total, 1) . ').',
                ], 422);
            }

            // Same truck + same location guaranteed by the guards above, so pick_point
            // is preserved untouched — only truck_number (a no-op here) and load_date change.
            Consignment::whereIn('id', $sourceCons->pluck('id'))->update([
                'truck_number' => $v['target_truck'],
                'load_date'    => $v['target_date'],
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Moved ' . $sourceCons->count() . ' consignment(s).',
            ]);
        });
    }

    // Drag-drop move: relocate a white "available" cell's Availability record to a
    // different date on the SAME truck + SAME location. Mirrors moveCell, but acts on
    // the availability row rather than consignments. The target cell must be empty.
    public function moveAvailability(Request $request)
    {
        $v = $request->validate([
            'source_truck'    => 'required|string',
            'source_date'     => 'required|date',
            'source_location' => 'required|in:MY,SG',
            'target_truck'    => 'required|string',
            'target_date'     => 'required|date',
            'target_location' => 'required|in:MY,SG',
        ]);

        if ($v['source_truck'] === $v['target_truck']
            && $v['source_date'] === $v['target_date']
            && $v['source_location'] === $v['target_location']) {
            return response()->json(['message' => 'Source and target are the same cell.'], 422);
        }

        // Same-truck only — keep parity with consignment drag-drop.
        if ($v['source_truck'] !== $v['target_truck']) {
            return response()->json([
                'message' => 'Availability can only move within the same truck.',
            ], 422);
        }

        // Same-location only — changing MY/SG goes through the availability form.
        if ($v['source_location'] !== $v['target_location']) {
            return response()->json([
                'message' => 'Drag-drop can only shift dates. Use the form to change MY/SG.',
            ], 422);
        }

        $truck = Truck::where('number', $v['source_truck'])->where('is_outsider', 0)->first();
        if (!$truck) {
            return response()->json(['message' => 'Truck not found.'], 404);
        }

        return DB::transaction(function () use ($v, $truck) {
            $source = Availability::where('truck_id', $truck->id)
                ->where('location', $v['source_location'])
                ->whereDate('date', $v['source_date'])
                ->where('status', 'available')
                ->lockForUpdate()
                ->first();
            if (!$source) {
                return response()->json(['message' => 'Source cell has no availability to move.'], 422);
            }

            // Target must be free: no consignments on that side and no existing availability.
            $tgtOp = $v['target_location'] === 'SG' ? '=' : '!=';
            $targetHasCons = Consignment::where('truck_number', $v['target_truck'])
                ->whereDate('load_date', $v['target_date'])
                ->where('pick_point', $tgtOp, 'Singapore')
                ->exists();
            if ($targetHasCons) {
                return response()->json(['message' => 'Target cell already has consignments.'], 422);
            }

            $targetAvail = Availability::where('truck_id', $truck->id)
                ->where('location', $v['target_location'])
                ->whereDate('date', $v['target_date'])
                ->exists();
            if ($targetAvail) {
                return response()->json(['message' => 'Target cell already has an availability.'], 422);
            }

            $source->date = $v['target_date'];
            $source->save();

            return response()->json([
                'success' => true,
                'message' => 'Availability moved to ' . $v['target_date'] . '.',
            ]);
        });
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

    // Bulk-delete multiple 'available' availability records selected from the calendar.
    // Each item identifies a single cell by truck number + location + date, matching the
    // granularity used by destroy(). Only records currently in the 'available' status are
    // removed so an accidental selection can't wipe out off-day/maintenance entries.
    public function bulkDeleteAvailability(Request $request)
    {
        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.truck' => 'required|string',
            'items.*.location' => 'required|string',
            'items.*.date' => 'required|date',
        ]);

        $truckIds = Truck::whereIn('number', collect($validated['items'])->pluck('truck')->unique())
            ->pluck('id', 'number');

        $deleted = 0;
        foreach ($validated['items'] as $item) {
            $truckId = $truckIds[$item['truck']] ?? null;
            if (!$truckId) {
                continue;
            }

            $deleted += Availability::where('truck_id', $truckId)
                ->where('location', strtoupper($item['location']))
                ->where('status', 'available')
                ->whereDate('date', $item['date'])
                ->delete();
        }

        if ($deleted) {
            return response()->json([
                'success' => true,
                'deleted' => $deleted,
                'message' => "{$deleted} availability record(s) deleted successfully",
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'No matching available records found',
        ], 404);
    }

}
