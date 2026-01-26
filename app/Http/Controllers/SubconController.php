<?php

namespace App\Http\Controllers;

use App\Models\Subcon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
class SubconController extends Controller
{
    /**
     * Display a listing of the resource.
     */
   public function index(Request $request)
{
    $sortBy = $request->query('sort_by', 'id');
    $sortOrder = $request->query('sort_order', 'asc');
    $search = $request->query('search');
    $perPage = $request->query('per_page', 10); // Default 10 entries per page

    $query = Subcon::query();

    // 🔍 Search filter
    if ($search) {
        $query->where(function ($q) use ($search) {
            $q->where('subcon_name', 'like', "%$search%")
                ->orWhere('truck_no', 'like', "%$search%")
                ->orWhere('driver_name', 'like', "%$search%")
                ->orWhere('group', 'like', "%$search%")
                ->orWhere('tonnage', 'like', "%$search%")
                ->orWhere('floor_space', 'like', "%$search%")
                ->orWhere('chassis_type', 'like', "%$search%")
                ->orWhere('phone_my', 'like', "%$search%")
                ->orWhere('phone_sg', 'like', "%$search%");
        });
    }

    $subcons = $query->orderBy($sortBy, $sortOrder)->paginate($perPage)->withQueryString();

    return view('master-data.subcon.index', compact('subcons'));
}

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('master-data.subcon.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'subcon_name' => 'nullable|string|max:255',
            'truck_no' => 'nullable|string|max:255|unique:subcons',
            'driver_name' => 'nullable|string|max:255',
            'group' => 'nullable|string|max:255',
            'tonnage' => 'nullable|numeric|min:0',
            'floor_space' => 'nullable|numeric|min:0',
            'chassis_type' => 'nullable',
            'phone_my' => 'nullable|string|max:20',
            'phone_sg' => 'nullable|string|max:20',
        ]);

        Subcon::create($request->all());

        return redirect()->route('subcon.index')->with('swal', [
            'icon' => 'success',
            'title' => 'Created!',
            'text' => 'Subcon created successfully.'
        ]);
    }

    public function syncFromLygions()
    {
        try {
            $response = Http::get('https://lygions.com/api/v1/lorry/get-subcons');

            if ($response->failed()) {
                return response()->json([
                    'swal' => [
                        'icon' => 'error',
                        'title' => 'Sync Failed',
                        'text' => 'Unable to fetch subcons from Lygion API.'
                    ]
                ]);
            }

            $data = $response->json();
            $processed = 0;
            foreach ($data['subcons'] as $item) {
                Subcon::updateOrCreate(
                    ['lygion_id' => $item['id']],
                    [
                        'subcon_name' => null,
                        'truck_no' => $item['number'],
                        'group' => $item['group'],
                        'size' => $item['size_label'],
                        'tonnage' => $item['tonnage'] ?? 0,
                        'floor_space' => $item['floor_space'] ?? 0,
                        'chassis_type' => $item['chassis_type'] ?? null,
                        'phone_my' => null,
                        'phone_sg' => null,
                    ]
                );

                $processed++;
            }

            return response()->json([
                'swal' => [
                    'icon' => 'success',
                    'title' => 'Sync Complete!',
                    'text' => "Synced $processed subcons from Lygion."
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'swal' => [
                    'icon' => 'error',
                    'title' => 'Error',
                    'text' => $e->getMessage()
                ]
            ]);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Subcon $subcon)
    {
        return view('master-data.subcon.show', compact('subcon'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Subcon $subcon)
    {
        return view('master-data.subcon.edit', compact('subcon'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $subcon = Subcon::findOrFail($id);
        $subcon->update($request->all());

        return redirect()->route('subcon.index')->with('swal', [
            'icon' => 'success',
            'title' => 'Updated!',
            'text' => 'Subcon updated successfully.'
        ]);
    }

    public function destroy($id)
    {
        $subcon = Subcon::findOrFail($id);
        $subcon->delete();

        return redirect()->route('subcon.index')->with('swal', [
            'icon' => 'success',
            'title' => 'Deleted!',
            'text' => 'Subcon deleted successfully.'
        ]);
    }

}
