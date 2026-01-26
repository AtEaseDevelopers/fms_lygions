<?php
namespace App\Http\Controllers;

use App\Models\Truck;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
class TruckController extends Controller
{public function index(Request $request)
{
    $sortBy = $request->query('sort_by', 'number');
    $sortOrder = $request->query('sort_order', 'asc');
    $search = $request->query('search');

    $query = Truck::query();

    if ($search) {
        $query->where(function ($q) use ($search) {
            $q->where('number', 'like', "%$search%")
              ->orWhere('group', 'like', "%$search%")
              ->orWhere('tonnage', 'like', "%$search%")
              ->orWhere('floor_space', 'like', "%$search%")
              ->orWhere('chassis_type', 'like', "%$search%");
        });
    }

    $trucks = $query->orderBy($sortBy, $sortOrder)->get();

    return view('master-data.truck.truck', compact('trucks'));
}


    public function create()
    {
        return view('master-data.truck.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'number' => 'required|unique:trucks',
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

        Truck::create($request->all());

        return redirect()->route('truck.index')->with('swal', [
            'icon' => 'success',
            'title' => 'Created!',
            'text' => 'Truck added successfully.'
        ]);
    }

    public function syncFromLygions()
{
    try {
        $response = Http::get('https://lygions.com/api/v1/lorry/get-trucks');

        if ($response->failed()) {
            return response()->json([
                'swal' => [
                    'icon' => 'error',
                    'title' => 'Sync Failed',
                    'text' => 'Unable to fetch trucks from Lygion API.'
                ]
            ], 500);
        }

        $trucks = $response->json('trucks', []);
        $processed = 0;

        foreach ($trucks as $truck) {
            $lygionId = 'lygion_' . $truck['id']; // prefix added

            Truck::updateOrCreate(
                ['lygions_id' => $lygionId],
                [
                    'number' => $truck['number'] ?? null,
                    'group' => $truck['group'] ?? null,
                    'size' => $truck['size_label'] ?? null,
                    'tonnage' => $truck['tonnage'] ?? 0,
                    'floor_space' => $truck['floor_space'] ?? 0,
                    'chassis_type' => $truck['chassis_type'] ?? null,
                    'next_inspection' => null,
                    'next_tyre' => null,
                    'next_permit' => null,
                    'next_extinguisher' => null,
                    'next_roadtax' => null,
                    'next_insurance' => null,
                    'next_others' => null,
                ]
            );

            $processed++;
        }

        return response()->json([
            'swal' => [
                'icon' => 'success',
                'title' => 'Sync Complete!',
                'text' => "Successfully synced {$processed} trucks from Lygion."
            ]
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'swal' => [
                'icon' => 'error',
                'title' => 'Error',
                'text' => $e->getMessage()
            ]
        ], 500);
    }
}



    public function update(Request $request, Truck $truck)
    {
        $request->validate([
            'number' => 'required|unique:trucks,number,' . $truck->id,
            'group' => 'required',
            'tonnage' => 'required',
            'floor_space' => 'required',
            'chassis_type' => 'required',
        ]);

        $truck->update($request->all());

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

    // public function holidayIndex(Request $request)
    // {
    //     // 1. Determine the start of the week
    //     $weekStart = $request->input('week_start') ? Carbon::parse($request->input('week_start'))->startOfWeek(Carbon::MONDAY) : Carbon::now()->startOfWeek(Carbon::MONDAY);
    //     // 2. Generate 7 consecutive days for the week
    //     $days = collect();
    //     for ($i = 0; $i < 7; $i++) {
    //         $days->push($weekStart->copy()->addDays($i));
    //     }
    //     // 3. Truck types and their dummy trucks
    //     $truckTypes = ['40', '20']; // 40-footer and 20-footer
    //     $locations = ['KL', 'SG']; // Locations
    //     $dummyTrucks = ['40' => ['9551', '9771', '8888'], '20' => ['4471', '3333', '1212'],];
    //     // 4. Sample customer names
    //     $customers = ['MIX', 'YCLEWORLD', 'CHEMETAL', 'AESS', 'XYZ', 'DEF'];
    //     // 5. Initialize truck holiday data structure
    //     $truckData = [];
    //     // 6. Generate random truck entries
    //     foreach ($truckTypes as $type) {
    //         foreach ($locations as $location) {
    //             foreach ($days as $day) {
    //                 $date = $day->toDateString();
    //                 $entries = [];
    //                 // Random number of entries per cell (0–2)
    //                 $count = rand(0, 2);
    //                 for ($i = 0; $i < $count; $i++) {
    //                     $truckNo = $dummyTrucks[$type][array_rand($dummyTrucks[$type])];
    //                     $customer = $customers[array_rand($customers)];
    //                     // Format as truck badge and customer badge
    //                     $entries[] = '<span class="badge bg-primary">' . $truckNo . '</span> <br>' . '<span class="badge bg-success text-dark">' . $customer . '</span>';
    //                 }
    //                 // Save entries to truckData if not empty
    //                 if (!empty($entries)) {
    //                     $truckData[$type][$location][$date] = implode('<hr class="my-1">', $entries);
    //                 }
    //             }
    //         }
    //     }
    //     // 7. Randomly select 1–2 holiday days
    //     $holidays = $days->shuffle()->take(rand(1, 2))->map(fn($d) => $d->toDateString())->toArray();
    //     // 8. Return view with data
    //     return view('master-data.truck.driver-holiday', compact('days', 'truckData', 'holidays', 'weekStart'));
    // }


}
