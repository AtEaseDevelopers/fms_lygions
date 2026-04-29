<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Availability;
use App\Models\Consignment;
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

        $cards = $dates->map(function ($day) use ($trucks, $availableByDate, $consByDate, $unitsMap) {
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
            ];
        });

        $notifications = Notification::unread()
            ->latest()
            ->limit(20)
            ->with(['consignment', 'trigger'])
            ->get();
        $unreadCount = Notification::unread()->count();

        return view('welcome', [
            'cards' => $cards,
            'rangeStart' => $start,
            'rangeEnd' => $end,
            'prevStart' => $prevStart,
            'nextStart' => $nextStart,
            'notifications' => $notifications,
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
