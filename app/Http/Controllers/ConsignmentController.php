<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Consignment;
use Carbon\Carbon;
use App\Models\Truck;
use App\Models\Customer;
use App\Models\Unit;
use App\Models\DraftCustomer;
use App\Models\Availability;
use App\Models\Notification;
use App\Models\TemporaryTruck;
use App\Traits\HandlesExpressModeSwap;
class ConsignmentController extends Controller
{
    use HandlesExpressModeSwap;

    /**
     * Build the consignment query with the Truck Planning listing filters applied
     * (date range, status, truck type/number, search) plus sorting. Shared by the
     * listing (index) and the Excel/CSV export so both stay in sync.
     */
    private function filteredConsignmentQuery(Request $request)
    {
        $query = Consignment::query();

        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $monthStart = now()->startOfMonth()->format('Y-m-d');

        // Date range filter
        if ($request->filled('filter_daterange')) {
            $dates = explode(' to ', $request->filter_daterange);
            if (count($dates) === 2) {
                $query->whereBetween('load_date', [trim($dates[0]), trim($dates[1])]);
            }
        } else {
            // Default: current month onward. Earlier months live in the archive.
            $query->where('load_date', '>=', $monthStart);
        }

        // Status filter
        if ($request->filled('status')) {
            $query->where('status', 'LIKE', '%' . $request->status . '%');
        }

        // Truck type filter
        if ($request->filled('truck_type')) {
            $query->where(function ($q) use ($request) {
                $q->where('pick_truck_type', 'LIKE', '%' . $request->truck_type . '%')
                    ->orWhere('drop_truck_type', 'LIKE', '%' . $request->truck_type . '%');
            });
        }

        // Truck number filter
        if ($request->filled('truck_number')) {
            $query->where('truck_number', $request->truck_number);
        }

        // Search filter (searches across multiple fields)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('consignment_no', 'LIKE', "%{$search}%")
                    ->orWhere('consignor', 'LIKE', "%{$search}%")
                    ->orWhere('consignee', 'LIKE', "%{$search}%")
                    ->orWhere('pick_point', 'LIKE', "%{$search}%")
                    ->orWhere('drop_point', 'LIKE', "%{$search}%")
                    ->orWhere('truck_number', 'LIKE', "%{$search}%")
                    ->orWhere('remarks', 'LIKE', "%{$search}%");
            });
        }

        return $query->orderBy($sortBy, $sortOrder);
    }

    /**
     * Export the currently-filtered Truck Planning records as a CSV (opens in Excel).
     * Uses the exact same filters as the listing, but exports every matching row
     * (not just the current page).
     */
    public function export(Request $request)
    {
        $consignments = $this->filteredConsignmentQuery($request)->get();

        $dash = fn ($v) => ($v === null || $v === '' || $v === []) ? '-' : $v;
        $flatten = function ($v) {
            $decoded = json_decode((string) $v, true);
            return is_array($decoded) ? implode(', ', $decoded) : (string) $v;
        };

        $headers = [
            'Pick Up Date', 'Consignment No', 'Consignor', 'Pick Point', 'Pick Address',
            'Consignee', 'Drop Point', 'Drop Address',
            'Pick Truck Size', 'Drop Truck Size', 'Pick Truck Type', 'Drop Truck Type',
            'Pick Up Time', 'Qty', 'Unit', 'Pre-Pick',
            'Truck Number', 'Remarks', 'Billing Remarks', 'Status',
        ];

        $filename = 'truck-planning-' . now()->format('Y-m-d') . '.csv';

        return response()->streamDownload(function () use ($consignments, $headers, $dash, $flatten) {
            $out = fopen('php://output', 'w');
            // BOM so Excel reads UTF-8 correctly.
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, $headers);

            foreach ($consignments as $c) {
                fputcsv($out, [
                    $dash($c->load_date),
                    $dash($c->consignment_no),
                    $dash($c->consignor),
                    $dash($c->pick_point),
                    $dash($c->pick_address),
                    $dash($c->consignee),
                    $dash($c->drop_point),
                    $dash($c->drop_address),
                    $dash($c->pick_truck_size),
                    $dash($c->drop_truck_size),
                    $dash($c->pick_truck_type),
                    $dash($c->drop_truck_type),
                    $dash($c->pick_time),
                    $dash($flatten($c->quantity)),
                    $dash($flatten($c->unit)),
                    $c->pre_pick ? 'Yes' : 'No',
                    $dash($c->truck_number),
                    $dash($c->remarks),
                    $dash($c->billing_remark),
                    $dash($c->status),
                ]);
            }

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // --- Truck Planning listing preferences ---------------------------------
        // Filters and sorting are remembered per user so they survive a refresh,
        // switching to Truck Capacity and back, or a fresh login. Server-side state
        // (the query string) is re-applied via a redirect; the client-side column
        // filters are handed to the view to restore.
        $filterKeys = ['filter_daterange', 'search', 'status', 'truck_type', 'truck_number', 'per_page', 'sort_by', 'sort_order'];
        // per_page defaults to 10, so it alone should not re-apply / count as a filter.
        $redirectKeys = ['filter_daterange', 'search', 'status', 'truck_type', 'truck_number', 'sort_by', 'sort_order'];
        $user = $request->user();

        if ($user) {
            $prefs = $user->planning_prefs ?? [];

            if ($request->has('reset')) {
                $prefs['query'] = [];
                $user->planning_prefs = $prefs;
                $user->save();
                return redirect()->route('consignment-order.index');
            }

            $hasFilterParams = $request->hasAny($filterKeys);
            // A "clean" arrival (e.g. from the sidebar) carries no filter/sort/page params.
            $isNavClean = !$hasFilterParams && !$request->has('page') && !$request->boolean('applied');

            if ($isNavClean) {
                $savedQuery = array_filter($prefs['query'] ?? [], fn($v) => $v !== null && $v !== '');
                $meaningful = array_intersect_key($savedQuery, array_flip($redirectKeys));
                if (!empty($meaningful)) {
                    return redirect()->route('consignment-order.index', $savedQuery);
                }
            } elseif ($hasFilterParams) {
                // Explicit filter/sort load — remember it for next time.
                $prefs['query'] = array_filter($request->only($filterKeys), fn($v) => $v !== null && $v !== '');
                $user->planning_prefs = $prefs;
                $user->save();
            }
        }

        $planningColumnFilters = $user ? ($user->planning_prefs['columnFilters'] ?? []) : [];

        $perPage = $request->input('per_page', 10);

        // Date range filter default anchor (also drives the truck-capacity panel).
        $today = now()->format('Y-m-d');
        $truckDate = $request->input('truck_date', $today);

        $query = $this->filteredConsignmentQuery($request);

        $consignments = $query->paginate($perPage);

        // ... rest of your existing code for trucks calculation ...

        $trucks_no = Truck::select('id', 'number', 'group', 'tonnage', 'floor_space')->where('is_outsider', 0)->get();
        $trucks_grp = $trucks_no->pluck('group')->unique()->values();
        $unitSpaces = Unit::pluck('space', 'unit')->toArray();

        foreach ($trucks_no as $truck) {
            $csn = Consignment::where('truck_number', $truck->number)
                ->where('load_date', $truckDate)
                ->get();

            $used = $csn->sum(function ($c) use ($unitSpaces) {
                $parseToArray = function ($v) {
                    if (is_string($v)) {
                        $decoded = json_decode($v, true);
                        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                            return array_map('trim', $decoded);
                        }
                    }
                    return is_array($v) ? $v : array_map('trim', explode(',', (string) $v));
                };

                $qtys = $parseToArray($c->quantity);
                $unitStrings = $parseToArray($c->unit);

                $units = array_map(function ($u) use ($unitSpaces) {
                    $key = trim($u);
                    return isset($unitSpaces[$key]) ? (float) $unitSpaces[$key] : 0;
                }, $unitStrings);

                $max = max(count($qtys), count($units), 1);
                $balance = (float) 0;

                for ($i = 0; $i < $max; $i++) {
                    $q = (float) ($qtys[$i] ?? ($qtys[0] ?? 0));
                    $u = (float) ($units[$i] ?? ($units[0] ?? 1));
                    $balance += $q * $u;
                }

                return $balance;
            });

            $truck->used = $used;
            $truck->remaining = max(0, (float) $truck->floor_space - $used);
            $truck->utilization = (float) $truck->floor_space > 0
                ? ($used / (float) $truck->floor_space) * 100
                : 0;
        }

        // Trucks are NOT available by default — only those with a positive availability
        // record for the date show in the truck filter (see truckAvailabilityChecker).
        $isAvailable = $this->truckAvailabilityChecker($truckDate);
        $trucks_no = $trucks_no->filter(fn($truck) => $isAvailable($truck->id));

        $customers = Customer::all();

        $affectedIds = Notification::whereNull('read_at')
            ->where('type', 'express_swap_unassigned')
            ->whereIn('consignment_id', $consignments->pluck('id'))
            ->pluck('consignment_id')
            ->unique()
            ->values()
            ->all();

        return view('consignment.order', compact('customers', 'consignments', 'trucks_no', 'trucks_grp', 'affectedIds', 'planningColumnFilters'));
    }

    /**
     * Persist the current user's Truck Planning per-column filters. The server-side
     * filters/sort are saved by index() on load; this handles the client-only column
     * filters so they survive a refresh, navigation, or logout too.
     */
    public function savePrefs(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $data = $request->validate([
            'columnFilters' => 'nullable|array',
            'columnFilters.*' => 'nullable|array',
            'columnFilters.*.*' => 'nullable|string',
        ]);

        $prefs = $user->planning_prefs ?? [];
        $prefs['columnFilters'] = $data['columnFilters'] ?? [];
        $user->planning_prefs = $prefs;
        $user->save();

        return response()->json(['success' => true]);
    }

    /**
     * Lightweight inline update of just the truck assignment for one consignment,
     * so planners can reassign trucks directly in the listing (no "Edit All", no
     * page reload — filters and sorting stay put).
     */
    public function updateTruckNumber(Request $request, $id)
    {
        $data = $request->validate([
            'truck_number' => 'nullable|string|max:50',
        ]);

        $consignment = Consignment::findOrFail($id);
        $consignment->truck_number = $this->normalizeTruckNumber($data['truck_number'] ?? null);
        $consignment->save();

        return response()->json([
            'success' => true,
            'message' => 'Truck assignment updated.',
            'truck_number' => $consignment->truck_number,
        ]);
    }

    private function getTrucksWithCapacity(string $date)
    {
        $trucks = Truck::select('id', 'number', 'group', 'tonnage', 'floor_space', 'chassis_type', 'size')
            ->where('is_outsider', 0)
            ->get();

        $unitSpaces = Unit::pluck('space', 'unit')->toArray();

        foreach ($trucks as $truck) {
            $csn = Consignment::where('truck_number', $truck->number)
                ->where('load_date', $date)
                ->get();

            $used = $csn->sum(function ($c) use ($unitSpaces) {
                $parseToArray = function ($v) {
                    if (is_string($v)) {
                        $decoded = json_decode($v, true);
                        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                            return array_map('trim', $decoded);
                        }
                    }
                    return is_array($v) ? $v : array_map('trim', explode(',', (string) $v));
                };

                $qtys = $parseToArray($c->quantity);
                $unitStrings = $parseToArray($c->unit);

                $units = array_map(function ($u) use ($unitSpaces) {
                    $key = trim($u);
                    return isset($unitSpaces[$key]) ? (float) $unitSpaces[$key] : 0;
                }, $unitStrings);

                $max = max(count($qtys), count($units), 1);
                $balance = (float) 0;

                for ($i = 0; $i < $max; $i++) {
                    $q = (float) ($qtys[$i] ?? ($qtys[0] ?? 0));
                    $u = (float) ($units[$i] ?? ($units[0] ?? 1));
                    $balance += $q * $u;
                }

                return $balance;
            });

            $truck->used = $used;
            $truck->remaining = max(0, (float) $truck->floor_space - $used);
        }

        return $trucks;
    }

    private function normalizeTruckNumber(?string $value): ?string
    {
        if ($value === null) return null;
        $trimmed = preg_replace('/\s*\((?:Temp|Subcon)\)\s*$/i', '', $value);
        return $trimmed === '' ? null : $trimmed;
    }

    /**
     * Build the next consignment number for the given moment, e.g. "CSN. 0308-0007".
     *
     * The index is the highest existing number that shares today's "CSN. dm-"
     * prefix, plus one — so it is always unique for the prefix and never affected
     * by unrelated rows created the same day (which previously caused duplicate
     * consignment_no collisions).
     */
    public function nextConsignmentNumber(?Carbon $now = null): string
    {
        $now = $now ?? Carbon::now('Asia/Kuala_Lumpur');
        $prefix = 'CSN. ' . $now->format('dm') . '-';

        $maxIndex = Consignment::where('consignment_no', 'LIKE', $prefix . '%')
            ->pluck('consignment_no')
            ->map(fn ($no) => (int) substr((string) $no, -4))
            ->max();

        return $prefix . str_pad(((int) $maxIndex) + 1, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Availability statuses that mark a truck as NOT operating on a given date.
     * Everything else (express, saturday-loading/unloading, available, occupied, or
     * no record at all) leaves the truck available.
     */
    private const UNAVAILABLE_STATUSES = ['off-day', 'maintenance', 'holiday', 'breakdown', 'inspection'];

    /**
     * Trucks are NOT available by default. A truck is only available for $date when it
     * has a positive availability record (e.g. available, express, saturday-loading/
     * unloading) and no blocking record (see UNAVAILABLE_STATUSES). This applies to both
     * weekdays and weekends — availability must be created per-month for weekdays first.
     *
     * Returns a closure fn(int $truckId): bool used to filter truck collections.
     */
    private function truckAvailabilityChecker(string $date): \Closure
    {
        $records = Availability::whereDate('date', $date)->get(['truck_id', 'status']);

        $offIds = $records->whereIn('status', self::UNAVAILABLE_STATUSES)
            ->pluck('truck_id')->unique();
        $onIds = $records->whereNotIn('status', self::UNAVAILABLE_STATUSES)
            ->pluck('truck_id')->unique();

        return function ($truckId) use ($offIds, $onIds) {
            if ($offIds->contains($truckId)) {
                return false;
            }
            // Require a positive availability record — nothing is available by default.
            return $onIds->contains($truckId);
        };
    }

    public function getAvailableTrucks(Request $request)
    {
        $date = $request->input('date', now()->format('Y-m-d'));

        $trucks = $this->getTrucksWithCapacity($date);

        $isAvailable = $this->truckAvailabilityChecker($date);

        // The inline lorry select mirrors the truck-availability for the date: owned
        // company trucks only (no subcons, no temporary trucks). A full truck (no
        // remaining floor space) stays in the list so a lorry can still be assigned
        // extra capacity — overloading is allowed, so we do not filter on remaining.
        $available = $trucks->filter(fn($t) => $isAvailable($t->id));

        // Temporary subcon trucks reserved for this date are bookable capacity too, so
        // they appear in the Truck Planning dropdown (labelled "(Temp)" until a real
        // subcon is assigned, then "(Subcon)"). One entry per label; MY/SG is decided
        // by the consignment's pick point, same as owned trucks.
        $tempTrucks = TemporaryTruck::whereDate('date', $date)
            ->with('subcon')
            ->get()
            ->unique('label')
            ->map(fn($t) => [
                'truck_no' => $t->label,
                'subcon_id' => $t->subcon_id,
                'subcon_truck_no' => $t->subcon?->truck_no,
            ])
            ->values();

        return response()->json([
            'trucks' => $available->map(fn($t) => [
                'number' => $t->number,
                'chassis_type' => $t->chassis_type,
                'size' => $t->size,
                'remaining' => $t->remaining,
                'floor_space' => $t->floor_space,
            ])->values(),
            // Availability-based subcons stay empty (owned trucks only); temporary
            // subcon slots are surfaced so planners can book against reserved capacity.
            'subcons' => [],
            'temp_trucks' => $tempTrucks,
        ]);
    }

    public function archivedIndex(Request $request)
    {
        $query = Consignment::query();

        // Filter: load_date before the start of the current month (previous months only)
        $monthStart = now()->startOfMonth()->format('Y-m-d');
        $query->where('load_date', '<', $monthStart);

        // Optional: filtering by date range or other filters from request
        if ($request->filled('filter_daterange')) {
            $dates = explode(' to ', $request->filter_daterange);
            if (count($dates) === 2) {
                $startDate = trim($dates[0]);
                $endDate = trim($dates[1]);
                $query->whereBetween('load_date', [$startDate, $endDate]);
            }
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'load_date');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $perPage = $request->input('per_page', 10);
        $consignments = $query->paginate($perPage);

        // Trucks info, similar to index()
        $trucks_no = Truck::select('id', 'number', 'group', 'tonnage', 'floor_space')->where('is_outsider', 0)->get();
        $trucks_grp = $trucks_no->pluck('group')->unique()->values();
        $unitSpaces = Unit::pluck('space', 'unit')->toArray();

        foreach ($trucks_no as $truck) {
            $csn = Consignment::where('truck_number', $truck->number)->get();

            $used = $csn->sum(function ($c) use ($unitSpaces) {
                $parseToArray = function ($v) {
                    if (is_string($v)) {
                        $decoded = json_decode($v, true);
                        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                            return array_map('trim', $decoded);
                        }
                    }
                    return is_array($v) ? $v : array_map('trim', explode(',', (string) $v));
                };

                $qtys = $parseToArray($c->quantity);
                $unitStrings = $parseToArray($c->unit);

                $units = array_map(fn($u) => $unitSpaces[$u] ?? 0, $unitStrings);

                $max = max(count($qtys), count($units), 1);
                $balance = 0;
                for ($i = 0; $i < $max; $i++) {
                    $q = (float) ($qtys[$i] ?? ($qtys[0] ?? 0));
                    $u = (float) ($units[$i] ?? ($units[0] ?? 1));
                    $balance += $q * $u;
                }
                return $balance;
            });

            $truck->used = $used;
            $truck->remaining = max(0, (float) $truck->floor_space - $used);
            $truck->utilization = (float) $truck->floor_space > 0 ? ($used / (float) $truck->floor_space) * 100 : 0;
        }

        $customers = Customer::all();
        $affectedIds = [];

        return view('consignment.archived-order', compact('consignments', 'trucks_no', 'trucks_grp', 'customers', 'affectedIds'));
    }




    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $trucks = Truck::pluck('truck_number', 'id');
        return view('consignment-order.create', compact('trucks'));
    }

    public function store(Request $request)
    {
        // Validate input
        $request->validate([
            'load_date' => 'required|date',
            'consignor' => 'required|string|max:255',
            'consignee' => 'required|string|max:255',
            'pick_point' => 'required|string',
            'drop_point' => 'required|string',
            'pick_truck_type' => 'nullable|string',
            'drop_truck_type' => 'nullable|string',
            'truck_number' => 'nullable|string|max:50',
            'status' => 'nullable|string|max:50',
            'pick_truck_size' => 'nullable|string',
            'drop_truck_size' => 'nullable|string',
            'pick_time' => 'nullable|string',
            'quantity' => 'nullable|array',
            'quantity.*' => 'nullable|integer|min:1',
            'unit' => 'nullable|array',
            'unit.*' => 'nullable|string',
            'remarks' => 'nullable|string',
            'pre_pick' => 'nullable|string',
            'self_delivery' => 'nullable|boolean',
        ]);

        // generate consignment number (unique for today's "CSN. dm-" prefix)
        $consignmentNumber = $this->nextConsignmentNumber(Carbon::now('Asia/Kuala_Lumpur'));

        // Encode quantity & unit arrays as JSON (empty array if none)
        $quantityJson = !empty($request->quantity) ? json_encode($request->quantity) : json_encode([]);
        $unitJson = !empty($request->unit) ? json_encode($request->unit) : json_encode([]);

        // save
        $consignment = Consignment::create([
            'load_date' => $request->load_date,
            'consignment_no' => $consignmentNumber,
            'consignor' => $request->consignor,
            'consignee' => $request->consignee,
            'pick_point' => $request->pick_point,
            'drop_point' => $request->drop_point,
            'pick_time' => $request->pick_time,
            'remarks' => $request->remarks,
            'billing_remark' => $request->billing_remark,
            'pre_pick' => $request->pre_pick,
            'pick_truck_type' => $request->pick_truck_type,
            'drop_truck_type' => $request->drop_truck_type,
            'truck_number' => $this->normalizeTruckNumber($request->truck_number),
            'pick_truck_size' => $request->pick_truck_size,
            'drop_truck_size' => $request->drop_truck_size,
            'quantity' => $quantityJson,
            'pick_address' => $request->pick_address,
            'drop_address' => $request->drop_address,
            'unit' => $unitJson,
            'status' => $request->status ?? 'Pending',
            'express_mode' => (bool) $request->input('express_mode'),
            'self_delivery' => (bool) $request->input('self_delivery'),
        ]);

        $action = $request->input('express_action', 'swap');
        $affected = $action === 'unassign'
            ? $this->applyExpressUnassign($consignment)['affected']
            : $this->applyExpressSwap($consignment)['affected'];

        if ($action === 'unassign' && (bool) $consignment->express_mode) {
            $swal = [
                'icon' => 'warning',
                'title' => 'Created with side-effects',
                'text' => sprintf(
                    'Express order saved. Truck marked unavailable from load date; %d other planning(s) were unassigned.',
                    $affected->count()
                ),
            ];
        } else {
            $swal = $affected->isEmpty()
                ? [
                    'icon' => 'success',
                    'title' => 'Created!',
                    'text' => 'Consignment order created successfully.',
                ]
                : [
                    'icon' => 'warning',
                    'title' => 'Created with side-effects',
                    'text' => sprintf(
                        'Express order saved. %d existing planning(s) were unassigned. See dashboard alerts.',
                        $affected->count()
                    ),
                ];
        }

        return redirect()->back()->with('swal', $swal);
    }

    public function storeInline(Request $request)
    {
        $request->validate([
            'load_date' => 'required|date',
            'consignor' => 'required|string|max:255',
            'consignee' => 'required|string|max:255',
            'pick_point' => 'required|string',
            'drop_point' => 'required|string',
            'pick_truck_type' => 'nullable|string',
            'drop_truck_type' => 'nullable|string',
            'truck_number' => 'nullable|string|max:50',
            'status' => 'nullable|string|max:50',
            'pick_truck_size' => 'nullable|string',
            'drop_truck_size' => 'nullable|string',
            'pick_time' => 'nullable|string',
            'quantity' => 'nullable|array',
            'quantity.*' => 'nullable|integer|min:1',
            'unit' => 'nullable|array',
            'unit.*' => 'nullable|string',
            'remarks' => 'nullable|string',
            'pre_pick' => 'nullable|string',
            'billing_remark' => 'nullable|string',
            'pick_address' => 'nullable|string',
            'drop_address' => 'nullable|string',
        ]);

        $consignmentNumber = $this->nextConsignmentNumber(Carbon::now('Asia/Kuala_Lumpur'));

        $quantityJson = !empty($request->quantity) ? json_encode($request->quantity) : json_encode([]);
        $unitJson = !empty($request->unit) ? json_encode($request->unit) : json_encode([]);

        $consignment = Consignment::create([
            'load_date' => $request->load_date,
            'consignment_no' => $consignmentNumber,
            'consignor' => $request->consignor,
            'consignee' => $request->consignee,
            'pick_point' => $request->pick_point,
            'drop_point' => $request->drop_point,
            'pick_time' => $request->pick_time,
            'remarks' => $request->remarks,
            'billing_remark' => $request->billing_remark,
            'pre_pick' => $request->pre_pick,
            'pick_truck_type' => $request->pick_truck_type,
            'drop_truck_type' => $request->drop_truck_type,
            'truck_number' => $this->normalizeTruckNumber($request->truck_number),
            'pick_truck_size' => $request->pick_truck_size,
            'drop_truck_size' => $request->drop_truck_size,
            'quantity' => $quantityJson,
            'pick_address' => $request->pick_address,
            'drop_address' => $request->drop_address,
            'unit' => $unitJson,
            'status' => $request->status ?? 'Pending',
            'express_mode' => (bool) $request->input('express_mode'),
            'self_delivery' => (bool) $request->input('self_delivery'),
        ]);

        $action = $request->input('express_action', 'swap');
        $affected = $action === 'unassign'
            ? $this->applyExpressUnassign($consignment)['affected']
            : $this->applyExpressSwap($consignment)['affected'];

        if ($action === 'unassign' && (bool) $consignment->express_mode) {
            $message = sprintf(
                'Express order saved. Truck marked unavailable from load date; %d other planning(s) were unassigned.',
                $affected->count()
            );
        } else {
            $message = $affected->isEmpty()
                ? 'Consignment order created successfully.'
                : sprintf(
                    'Express order saved. %d existing planning(s) were unassigned. See dashboard alerts.',
                    $affected->count()
                );
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'consignment' => $consignment,
            'affected_count' => $affected->count(),
            'affected' => $affected->map(fn($c) => [
                'id' => $c->id,
                'consignment_no' => $c->consignment_no,
                'load_date' => $c->load_date,
            ])->values(),
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
    public function edit($id)
    {

    }


    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $consignment = Consignment::findOrFail($id);
        $wasExpress = (bool) $consignment->express_mode;

        $data = $request->all();

        // Ensure quantity/unit arrays are stored properly
        $data['quantity'] = is_array($request->quantity)
            ? json_encode($request->quantity)
            : $request->quantity;

        $data['unit'] = is_array($request->unit)
            ? json_encode($request->unit)
            : $request->unit;

        if (array_key_exists('truck_number', $data)) {
            $data['truck_number'] = $this->normalizeTruckNumber($data['truck_number']);
        }

        foreach (['consignor', 'consignee', 'pick_point', 'drop_point'] as $field) {
            if (array_key_exists($field, $data) && ($data[$field] === null || $data[$field] === '')) {
                $data[$field] = '-';
            }
        }

        if (array_key_exists('status', $data) && empty($data['status'])) {
            $data['status'] = 'Pending';
        }

        // Checkboxes are absent from the payload when unticked.
        $data['self_delivery'] = (bool) $request->input('self_delivery');

        $consignment->update($data);

        $affected = collect();
        $action = $request->input('express_action', 'swap');
        $expressTurnedOn = !$wasExpress && (bool) $consignment->express_mode;
        if ($expressTurnedOn) {
            $affected = $action === 'unassign'
                ? $this->applyExpressUnassign($consignment->fresh())['affected']
                : $this->applyExpressSwap($consignment->fresh())['affected'];
        }

        if ($expressTurnedOn && $action === 'unassign') {
            $swal = [
                'icon' => 'warning',
                'title' => 'Updated with side-effects',
                'text' => sprintf(
                    'Express mode enabled. Truck marked unavailable from load date; %d other planning(s) were unassigned.',
                    $affected->count()
                ),
            ];
        } else {
            $swal = $affected->isEmpty()
                ? [
                    'icon' => 'success',
                    'title' => 'Updated!',
                    'text' => 'Order updated successfully.',
                ]
                : [
                    'icon' => 'warning',
                    'title' => 'Updated with side-effects',
                    'text' => sprintf(
                        'Express mode enabled. %d existing planning(s) were unassigned. See dashboard alerts.',
                        $affected->count()
                    ),
                ];
        }

        return redirect()->back()->with('swal', $swal);
    }

    public function updateInline(Request $request, $id)
    {
        $consignment = Consignment::findOrFail($id);
        $wasExpress = (bool) $consignment->express_mode;

        $request->validate([
            'load_date' => 'required|date',
            'consignor' => 'nullable|string|max:255',
            'consignee' => 'nullable|string|max:255',
            'pick_point' => 'nullable|string',
            'drop_point' => 'nullable|string',
            'pick_truck_type' => 'nullable|string',
            'drop_truck_type' => 'nullable|string',
            'truck_number' => 'nullable|string|max:50',
            'status' => 'nullable|string|max:50',
            'pick_truck_size' => 'nullable|string',
            'drop_truck_size' => 'nullable|string',
            'pick_time' => 'nullable|string',
            'quantity' => 'nullable|array',
            'quantity.*' => 'nullable|integer|min:1',
            'unit' => 'nullable|array',
            'unit.*' => 'nullable|string',
            'remarks' => 'nullable|string',
            'pre_pick' => 'nullable|string',
            'billing_remark' => 'nullable|string',
            'pick_address' => 'nullable|string',
            'drop_address' => 'nullable|string',
        ]);

        $quantityJson = !empty($request->quantity) ? json_encode($request->quantity) : json_encode([]);
        $unitJson = !empty($request->unit) ? json_encode($request->unit) : json_encode([]);

        $consignment->update([
            'load_date' => $request->load_date,
            'consignor' => $request->consignor ?? '-',
            'consignee' => $request->consignee ?? '-',
            'pick_point' => $request->pick_point ?? '-',
            'drop_point' => $request->drop_point ?? '-',
            'pick_time' => $request->pick_time,
            'remarks' => $request->remarks,
            'billing_remark' => $request->billing_remark,
            'pre_pick' => $request->pre_pick,
            'pick_truck_type' => $request->pick_truck_type,
            'drop_truck_type' => $request->drop_truck_type,
            'truck_number' => $this->normalizeTruckNumber($request->truck_number),
            'pick_truck_size' => $request->pick_truck_size,
            'drop_truck_size' => $request->drop_truck_size,
            'quantity' => $quantityJson,
            'unit' => $unitJson,
            'pick_address' => $request->pick_address,
            'drop_address' => $request->drop_address,
            'status' => $request->status ?? 'Pending',
            'express_mode' => (bool) $request->input('express_mode'),
        ]);

        $affected = collect();
        $action = $request->input('express_action', 'swap');
        $expressTurnedOn = !$wasExpress && (bool) $consignment->express_mode;
        if ($expressTurnedOn) {
            $affected = $action === 'unassign'
                ? $this->applyExpressUnassign($consignment->fresh())['affected']
                : $this->applyExpressSwap($consignment->fresh())['affected'];
        }

        if ($expressTurnedOn && $action === 'unassign') {
            $message = sprintf(
                'Express mode enabled. Truck marked unavailable from load date; %d other planning(s) were unassigned.',
                $affected->count()
            );
        } else {
            $message = $affected->isEmpty()
                ? 'Order updated successfully.'
                : sprintf(
                    'Express mode enabled. %d existing planning(s) were unassigned. See dashboard alerts.',
                    $affected->count()
                );
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'affected_count' => $affected->count(),
            'affected' => $affected->map(fn($c) => [
                'id' => $c->id,
                'consignment_no' => $c->consignment_no,
                'load_date' => $c->load_date,
            ])->values(),
        ]);
    }

    public function bulkUpdate(Request $request)
    {
        $orders = $request->input('orders', []);

        foreach ($orders as $orderData) {
            if (!isset($orderData['id'], $orderData['truck_number']))
                continue;

            $consignment = Consignment::find($orderData['id']);
            if (!$consignment)
                continue;

            $consignment->update([
                'truck_number' => $this->normalizeTruckNumber($orderData['truck_number']),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Selected orders updated successfully.'
        ]);
    }

    public function bulkStatusUpdate(Request $request)
    {
        $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|exists:consignments,id',
            'status' => 'required|in:Pending,Planning,Completed',
        ]);

        Consignment::whereIn('id', $request->ids)
            ->update(['status' => $request->status]);

        return response()->json([
            'success' => true,
            'message' => count($request->ids) . ' order(s) status updated to ' . $request->status . '.',
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $order = Consignment::findOrFail($id);
        $order->delete();
        return redirect()->back()->with('swal', [
            'icon' => 'success',
            'title' => 'Deleted!',
            'text' => 'Order deleted successfully.'
        ]);
    }


    public function getCustomerLocations($name)
    {
        $customer = Customer::where('name', $name)
            ->with('locations')
            ->first()
            ?? DraftCustomer::where('name', $name)
                ->where('migrated', false)
                ->with('locations')
                ->first();

        if (!$customer) {
            return response()->json(['error' => 'Customer not found'], 404);
        }

        return response()->json([
            'name' => $customer->name,
            'locations' => $customer->locations->map(function ($loc) {
                return [
                    'id' => $loc->id,
                    'state' => $loc->state ?? 'Unknown',
                    'address' => $loc->address ?? 'Unknown',
                    'type' => $loc->type,
                    'types' => $loc->type_labels,
                    'has_self_delivery' => $loc->has_self_delivery,
                    'default_truck_type' => $loc->default_truck_type,
                    'truck_size' => $loc->truck_size,
                    'truck_type' => $loc->truck_type,
                    'pickup_dropoff_point' => $loc->pickup_dropoff_point,
                    'operation_hours' => $loc->operation_hours_array,
                ];
            }),
        ]);
    }

}
