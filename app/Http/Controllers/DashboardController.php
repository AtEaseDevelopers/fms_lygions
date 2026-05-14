<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Availability;
use App\Models\Consignment;
use App\Models\DriverHoliday;
use App\Models\Notification;
use App\Models\Truck;
use App\Models\Unit;
use App\Traits\CalculatesConsignmentCapacity;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    use CalculatesConsignmentCapacity;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $start = $this->getStartDate($request);
        $end = $start->copy()->addDays(6);
        $dates = collect(range(0, 6))->map(fn($i) => $start->copy()->addDays($i));

        $prevStart = $start->copy()->subDays(7)->toDateString();
        $nextStart = $start->copy()->addDays(7)->toDateString();

        $startStr = $start->toDateString();
        $endStr = $end->toDateString();

        $trucks = Truck::where('is_outsider', 0)->get(['id', 'floor_space'])->keyBy('id');

        $availableByDate = Availability::whereBetween('date', [$startStr, $endStr])
            ->where('status', 'available')
            ->get(['truck_id', 'date'])
            ->groupBy(fn($a) => Carbon::parse($a->date)->toDateString());

        $consignments = Consignment::whereBetween('load_date', [$startStr, $endStr])->get();

        $consByDate = $consignments->groupBy(fn($c) => Carbon::parse($c->load_date)->toDateString());

        $unitsMap = Unit::all()
            ->mapWithKeys(fn($u) => [trim($u->unit) => (float) $u->space])
            ->all();

        $leavesByDate = [];
        $leaves = DriverHoliday::with('driver:id,name')
            ->where('start_date', '<=', $endStr)
            ->where('end_date', '>=', $startStr)
            ->get(['id', 'driver_id', 'start_date', 'end_date', 'remarks']);
        foreach ($leaves as $leave) {
            $from = Carbon::parse($leave->start_date)->max($start);
            $to = Carbon::parse($leave->end_date)->min($end);
            for ($d = $from->copy(); $d->lte($to); $d->addDay()) {
                $leavesByDate[$d->format('Y-m-d')][] = [
                    'name' => $leave->driver?->name ?? 'Unknown',
                    'remarks' => $leave->remarks,
                ];
            }
        }
        foreach ($leavesByDate as $k => $list) {
            usort($list, fn($a, $b) => strcasecmp($a['name'], $b['name']));
            $leavesByDate[$k] = $list;
        }

        $cards = $dates->map(function ($day) use ($trucks, $availableByDate, $consByDate, $unitsMap, $leavesByDate) {
            $key = $day->toDateString();

            $availableTruckIds = $availableByDate->has($key)
                ? $availableByDate->get($key)->pluck('truck_id')->unique()
                : collect();

            $dayCapacity = $availableTruckIds
                ->map(fn($id) => optional($trucks->get($id))->floor_space ?? 0)
                ->sum();

            $dayCons = $consByDate->get($key, collect());
            $myCons = $dayCons->where('pick_point', '!=', 'Singapore');
            $sgCons = $dayCons->where('pick_point', '=', 'Singapore');

            $myBooked = $myCons->sum(fn($c) => $this->consignmentVolume($c, $unitsMap));
            $sgBooked = $sgCons->sum(fn($c) => $this->consignmentVolume($c, $unitsMap));
            $totalBooked = $myBooked + $sgBooked;
            $free = max($dayCapacity - $totalBooked, 0);
            $util = $dayCapacity > 0 ? ($totalBooked / $dayCapacity) * 100 : 0;

            return [
                'day' => $day,
                'dayCapacity' => $dayCapacity,
                'totalBooked' => $totalBooked,
                'free' => $free,
                'myBooked' => $myBooked,
                'sgBooked' => $sgBooked,
                'util' => $util,
                'myCount' => $myCons->count(),
                'sgCount' => $sgCons->count(),
                'leaves' => $leavesByDate[$key] ?? [],
            ];
        });

        $weekAlerts = Notification::with(['consignment', 'trigger'])
            ->whereNotNull('affected_date')
            ->whereBetween('affected_date', [$startStr, $endStr])
            ->latest()
            ->get();

        $alertsByDate = $weekAlerts->groupBy(fn($n) => Carbon::parse($n->affected_date)->toDateString());

        $unreadCount = Notification::unread()->count();

        return view('welcome', [
            'cards' => $cards,
            'rangeStart' => $start,
            'rangeEnd' => $end,
            'prevStart' => $prevStart,
            'nextStart' => $nextStart,
            'alertsByDate' => $alertsByDate,
            'unreadCount' => $unreadCount,
        ]);
    }

    private function getStartDate(Request $request): Carbon
    {
        $startDate = $request->query('start_date');
        return $startDate
            ? Carbon::parse($startDate)->startOfWeek(Carbon::MONDAY)
            : Carbon::now()->startOfWeek(Carbon::MONDAY);
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
