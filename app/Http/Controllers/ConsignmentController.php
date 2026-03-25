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
use App\Models\Subcon;
use App\Models\Availability;
class ConsignmentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Consignment::query();

        $perPage = $request->input('per_page', 10);

        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');

        // Date range filter
        $today = now()->format('Y-m-d');
        $truckDate = $request->input('truck_date', $today);
        if ($request->filled('filter_daterange')) {
            $dates = explode(' to ', $request->filter_daterange);
            if (count($dates) === 2) {
                $startDate = trim($dates[0]);
                $endDate = trim($dates[1]);
                $query->whereBetween('load_date', [$startDate, $endDate]);
            }
        } else {
            $query->where('load_date', '>=', $today);
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

        $query->orderBy($sortBy, $sortOrder);

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

        // Filter trucks to only those available or occupied on the selected truck date
        $availableTruckIds = Availability::where('date', $truckDate)
            ->whereIn('status', ['available', 'occupied'])
            ->pluck('truck_id')
            ->unique();

        $trucks_no = $trucks_no->filter(function ($truck) use ($availableTruckIds) {
            return $availableTruckIds->contains($truck->id);
        });

        $customers = Customer::all();
        return view('consignment.order', compact('customers', 'consignments', 'trucks_no', 'trucks_grp'));
    }


    public function archivedIndex(Request $request)
    {
        $query = Consignment::query();

        // Filter: load_date before today
        $today = now()->format('Y-m-d');
        $query->where('load_date', '<', $today);

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

        return view('consignment.archived-order', compact('consignments', 'trucks_no', 'trucks_grp'));
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
        ]);

        // current date in GMT+8
        $date = Carbon::now('Asia/Kuala_Lumpur');
        $dateCode = $date->format('dm'); // e.g. 0510 for 5 Oct

        // find last consignment for today
        $lastConsignment = Consignment::whereDate('created_at', $date->toDateString())
            ->orderBy('id', 'desc')
            ->first();

        $newIndex = $lastConsignment
            ? str_pad(((int) substr($lastConsignment->consignment_no, -4)) + 1, 4, '0', STR_PAD_LEFT)
            : '0001';

        // generate consignment number
        $consignmentNumber = 'CSN. ' . $dateCode . '-' . $newIndex;

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
            'truck_number' => $request->truck_number,
            'pick_truck_size' => $request->pick_truck_size,
            'drop_truck_size' => $request->drop_truck_size,
            'quantity' => $quantityJson,
            'pick_address' => $request->pick_address,
            'drop_address' => $request->drop_address,
            'unit' => $unitJson,
            'status' => $request->status ?? 'Pending',
            'express_mode' => $request->has('express_mode'),
        ]);

        if ($request->has('express_mode')) {

            $loadDate = Carbon::parse($request->load_date);
            $currentDate = $loadDate->copy()->addDay(); // start updating from next day

            $nextLocation = (strtoupper($request->pick_point) === 'Singapore') ? 'SG' : 'MY';

            $truckId = null;
            $subconId = null;

            if ($request->truck_number) {
                $truckId = Truck::where('number', $request->truck_number)->value('id');
            }

            if (!$truckId && $request->pick_truck) {
                $subconId = Subcon::where('truck_no', $request->pick_truck)->value('id');
            }


            if ($truckId || $subconId) {

                while (true) {
                    $query = Availability::query()
                        ->whereDate('date', $currentDate->toDateString());

                    if ($truckId) {
                        $query->where('truck_id', $truckId);
                    } else {
                        $query->where('subcon_id', $subconId);
                    }

                    $availability = $query->first();

                    // STOP IF no record or not "available"
                    if (!$availability || strtolower($availability->status) !== 'available') {
                        break;
                    }

                    // update only location
                    $availability->update([
                        'location' => $availability->location === 'SG' ? 'MY' : 'SG'
                    ]);

                    // move to next day
                    $currentDate->addDay();
                }
            }
        }



        return redirect()->back()->with('swal', [
            'icon' => 'success',
            'title' => 'Created!',
            'text' => 'Consignment order created successfully.'
        ]);
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
        ]);

        $date = Carbon::now('Asia/Kuala_Lumpur');
        $dateCode = $date->format('dm');

        $lastConsignment = Consignment::whereDate('created_at', $date->toDateString())
            ->orderBy('id', 'desc')
            ->first();

        $newIndex = $lastConsignment
            ? str_pad(((int) substr($lastConsignment->consignment_no, -4)) + 1, 4, '0', STR_PAD_LEFT)
            : '0001';

        $consignmentNumber = 'CSN. ' . $dateCode . '-' . $newIndex;

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
            'truck_number' => $request->truck_number,
            'pick_truck_size' => $request->pick_truck_size,
            'drop_truck_size' => $request->drop_truck_size,
            'quantity' => $quantityJson,
            'pick_address' => $request->pick_address,
            'drop_address' => $request->drop_address,
            'unit' => $unitJson,
            'status' => $request->status ?? 'Pending',
            'express_mode' => $request->has('express_mode'),
        ]);

        // Express mode logic (same as your store method)
        if ($request->has('express_mode')) {
            $loadDate = Carbon::parse($request->load_date);
            $currentDate = $loadDate->copy()->addDay();
            $nextLocation = (strtoupper($request->pick_point) === 'Singapore') ? 'SG' : 'MY';
            $truckId = null;
            $subconId = null;

            if ($request->truck_number) {
                $truckId = Truck::where('number', $request->truck_number)->value('id');
            }

            if (!$truckId && $request->pick_truck) {
                $subconId = Subcon::where('truck_no', $request->pick_truck)->value('id');
            }

            if ($truckId || $subconId) {
                while (true) {
                    $query = Availability::query()->whereDate('date', $currentDate->toDateString());
                    if ($truckId) {
                        $query->where('truck_id', $truckId);
                    } else {
                        $query->where('subcon_id', $subconId);
                    }
                    $availability = $query->first();
                    if (!$availability || strtolower($availability->status) !== 'available') {
                        break;
                    }
                    $availability->update(['location' => $availability->location === 'SG' ? 'MY' : 'SG']);
                    $currentDate->addDay();
                }
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Consignment order created successfully.',
            'consignment' => $consignment
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

        $data = $request->all();

        // Ensure quantity/unit arrays are stored properly
        $data['quantity'] = is_array($request->quantity)
            ? json_encode($request->quantity)
            : $request->quantity;

        $data['unit'] = is_array($request->unit)
            ? json_encode($request->unit)
            : $request->unit;

        $consignment->update($data);

        return redirect()->back()->with('swal', [
            'icon' => 'success',
            'title' => 'Updated!',
            'text' => 'Order updated successfully.'
        ]);
    }

    public function updateInline(Request $request, $id)
    {
        $consignment = Consignment::findOrFail($id);

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

        $quantityJson = !empty($request->quantity) ? json_encode($request->quantity) : json_encode([]);
        $unitJson = !empty($request->unit) ? json_encode($request->unit) : json_encode([]);

        $consignment->update([
            'load_date' => $request->load_date,
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
            'truck_number' => $request->truck_number,
            'pick_truck_size' => $request->pick_truck_size,
            'drop_truck_size' => $request->drop_truck_size,
            'quantity' => $quantityJson,
            'unit' => $unitJson,
            'pick_address' => $request->pick_address,
            'drop_address' => $request->drop_address,
            'status' => $request->status ?? 'Pending',
            'express_mode' => $request->has('express_mode'),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Order updated successfully.',
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
                'truck_number' => $orderData['truck_number'],
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
                    'default_truck_type' => $loc->default_truck_type,
                    'truck_size' => $loc->truck_size,
                    'truck_type' => $loc->truck_type,
                ];
            }),
        ]);
    }

}
