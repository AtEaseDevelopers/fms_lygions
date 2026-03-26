<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use App\Models\Customer;
use App\Models\DraftCustomer;
use App\Models\CustomerLocation;
use Illuminate\Support\Facades\DB;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $sortBy = $request->input('sort_by', 'id');
        $sortOrder = $request->input('sort_order', 'asc');

        $query = Customer::query();

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('account_number', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('company_registration_number', 'like', "%{$search}%")
                    ->orWhere('company_registration_number_old', 'like', "%{$search}%");
            });
        }

        if ($as = $request->input('as')) {
            $query->ofType($as);
        }

        $customers = $query->with('locations')->orderBy($sortBy, $sortOrder)->paginate($perPage);
        $customers->appends($request->all());

        return view('master-data.customer.customer', compact('customers'));
    }

    public function draftIndex(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $sortBy = $request->input('sort_by', 'id');
        $sortOrder = $request->input('sort_order', 'asc');

        $customers = DraftCustomer::where('migrated', 0)
            ->orderBy($sortBy, $sortOrder)
            ->paginate($perPage);

        return view('master-data.customer.draft-customer', compact('customers'));
    }

    public function draftCreate()
    {
        return view('master-data.customer.draft-customer-create');
    }

    public function syncDraftToCustomer(Request $request)
    {
        try {
            $data = $request->validate([
                'ids' => 'nullable|array',
                'ids.*' => 'integer|distinct|exists:draft_customers,id',
            ]);

            if (!empty($data['ids'])) {
                $drafts = DraftCustomer::with('locations')
                    ->whereIn('id', $data['ids'])
                    ->where('migrated', false)
                    ->get();
            } else {
                $drafts = DraftCustomer::with('locations')
                    ->where('migrated', false)
                    ->get();
            }

            if ($drafts->isEmpty()) {
                return response()->json([
                    'swal' => [
                        'icon' => 'info',
                        'title' => 'No Data',
                        'text' => 'No draft customers to sync (or they are already migrated).'
                    ]
                ]);
            }

            $processed = 0;

            DB::beginTransaction();

            foreach ($drafts as $draft) {
                $customer = Customer::create([
                    'name' => $draft->name,
                    'account_number' => $draft->account_number,
                    'phone' => $draft->phone,
                    'email' => $draft->email,
                    'billing_address' => $draft->billing_address,
                    'company_reg_no_new' => $draft->company_reg_no_new,
                    'company_reg_no_old' => $draft->company_reg_no_old,
                    'website' => $draft->website,
                    'remark' => $draft->remark,
                    'consignor_currency' => $draft->consignor_currency,
                    'consignee_currency' => $draft->consignee_currency,
                    'city' => $draft->city,
                    'post_code' => $draft->post_code,
                    'state' => $draft->state,
                    'country' => $draft->country,
                    'tin' => $draft->tin,
                    'service_tax_no' => $draft->service_tax_no,
                    'contact_person' => $draft->contact_person,
                    'term' => $draft->term,
                    'type' => $draft->type,
                    'nickname' => $draft->nickname,
                    'billing_phone' => $draft->billing_phone,
                ]);

                if ($draft->locations && $draft->locations->isNotEmpty()) {
                    $customer->locations()->delete();

                    foreach ($draft->locations as $loc) {
                        $customer->locations()->create([
                            'state' => $loc->state,
                            'address' => $loc->address,
                            'pic' => $loc->pic,
                            'phone' => $loc->phone,
                            'type' => $loc->type,
                            'truck_size' => $loc->truck_size,
                            'truck_type' => $loc->truck_type,
                            'pickup_dropoff_point' => $loc->pickup_dropoff_point,
                        ]);
                    }
                }

                $draft->update(['migrated' => true]);
                $processed++;
            }

            DB::commit();

            return response()->json([
                'redirect' => route('customer.index'),
                'swal' => [
                    'icon' => 'success',
                    'title' => 'Sync Complete!',
                    'text' => "Successfully migrated $processed draft customer(s)."
                ]
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();
            \Log::error('Draft sync error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);

            return response()->json([
                'swal' => [
                    'icon' => 'error',
                    'title' => 'Sync Failed',
                    'text' => $e->getMessage()
                ]
            ], 500);
        }
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'nullable|string',
            'phone' => 'nullable|string|max:20',
            'billing_address' => 'nullable|string',
            'locations' => 'nullable|array',
            'locations.*.state' => 'nullable|string|max:255',
            'locations.*.address' => 'nullable|string',
            'locations.*.pic' => 'nullable|string|max:255',
            'locations.*.phone' => 'nullable|string|max:20',
            'locations.*.type' => 'nullable|string|max:255',
            'locations.*.truck_type' => 'nullable|string|max:255',
            'locations.*.truck_size' => 'nullable|string|max:255',
            'locations.*.pickup_dropoff_point' => 'nullable|string|max:255',
        ], [
            'name.required' => 'Please enter the customer name.',
            'account_number.unique' => 'This account number is already taken.',
        ]);

        $types = [];
        if ($request->has('type')) {
            $types = explode(',', $request->type);
        } else {
            if ($request->input('consignor_currency') !== null && $request->input('type') !== 'Consignee') {
                $types[] = 'Consignor';
            }
            if ($request->input('consignee_currency') !== null && $request->input('type') !== 'Consignor') {
                $types[] = 'Consignee';
            }
        }

        if (empty($types) && $request->input('type')) {
            $types[] = $request->input('type');
        }

        foreach ($types as $type) {
            $customer = DraftCustomer::create(array_merge(
                $request->only([
                    'name', 'nickname', 'account_number', 'phone', 'billing_address',
                    'email', 'company_reg_no_new', 'company_reg_no_old', 'website',
                    'billing_phone', 'remark', 'consignor_currency', 'consignee_currency',
                    'city', 'post_code', 'state', 'country', 'tin', 'service_tax_no',
                    'contact_person', 'term',
                ]),
                ['type' => $type]
            ));

            if (!empty($validated['locations'])) {
                foreach ($validated['locations'] as $location) {
                    if (isset($location['load_type']) && is_array($location['load_type'])) {
                        $location['load_type'] = implode(',', $location['load_type']);
                    }
                    $customer->locations()->create($location);
                }
            }
        }

        return redirect()->route('draft-customer.index')->with('swal', [
            'icon' => 'success',
            'title' => 'Created!',
            'text' => 'Draft Customer(s) created successfully.'
        ]);
    }

    public function edit(Customer $customer)
    {
        return view('master-data.customer.edit', compact('customer'));
    }

    public function draftEdit(DraftCustomer $draftCustomer)
    {
        return view('master-data.customer.draft-customer-edit', compact('draftCustomer'));
    }

    public function update(Request $request, Customer $customer)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:Consignor,Consignee',
            'phone' => 'nullable|string|max:20',
            'billing_address' => 'nullable|string',
            'locations' => 'nullable|array',
            'locations.*.state' => 'nullable|string|max:255',
            'locations.*.address' => 'nullable|string',
            'locations.*.pic' => 'nullable|string|max:255',
            'locations.*.phone' => 'nullable|string|max:20',
            'locations.*.type' => 'nullable|string|max:255',
            'locations.*.load_type' => 'nullable|array',
            'locations.*.load_type.*' => 'string|max:50',
            'locations.*.pickup_dropoff_point' => 'nullable|string|max:255',
        ]);

        $customer->update($request->only([
            'name', 'nickname', 'type', 'account_number', 'phone', 'billing_address',
            'email', 'company_reg_no_new', 'company_reg_no_old', 'website', 'billing_phone',
            'remark', 'consignor_currency', 'consignee_currency', 'city', 'post_code',
            'state', 'country', 'tin', 'service_tax_no', 'contact_person', 'term',
        ]));

        $locations = $request->input('locations', []);
        $customer->locations()->delete();

        foreach ($locations as $location) {
            if (!empty(array_filter($location))) {
                if (isset($location['load_type']) && is_array($location['load_type'])) {
                    $location['load_type'] = implode(',', $location['load_type']);
                }
                $customer->locations()->create($location);
            }
        }

        return redirect()->route('customer.index')->with('swal', [
            'icon' => 'success',
            'title' => 'Updated!',
            'text' => 'Customer and locations updated successfully.'
        ]);
    }

    public function draftUpdate(Request $request, DraftCustomer $draftCustomer)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'nullable|in:Consignor,Consignee',
            'account_number' => 'nullable|string|max:255|unique:draft_customers,account_number,' . $draftCustomer->id,
            'phone' => 'nullable|string|max:20',
            'billing_address' => 'nullable|string',
            'locations' => 'nullable|array',
            'locations.*.state' => 'nullable|string|max:255',
            'locations.*.address' => 'nullable|string',
            'locations.*.pic' => 'nullable|string|max:255',
            'locations.*.phone' => 'nullable|string|max:20',
            'locations.*.type' => 'nullable|string|max:255',
            'locations.*.load_type' => 'nullable|array',
            'locations.*.load_type.*' => 'string|max:50',
            'locations.*.pickup_dropoff_point' => 'nullable|string|max:255',
        ]);

        $draftCustomer->update($request->only([
            'name', 'nickname', 'type', 'account_number', 'phone', 'billing_address',
            'email', 'company_reg_no_new', 'company_reg_no_old', 'website', 'billing_phone',
            'remark', 'consignor_currency', 'consignee_currency', 'city', 'post_code',
            'state', 'country', 'tin', 'service_tax_no', 'contact_person', 'term',
        ]));

        $locations = $request->input('locations', []);
        $draftCustomer->locations()->delete();

        foreach ($locations as $location) {
            if (!empty(array_filter($location))) {
                if (isset($location['load_type']) && is_array($location['load_type'])) {
                    $location['load_type'] = implode(',', $location['load_type']);
                }
                $draftCustomer->locations()->create($location);
            }
        }

        return redirect()->route('draft-customer.index')->with('swal', [
            'icon' => 'success',
            'title' => 'Updated!',
            'text' => 'Draft Customer and locations updated successfully.'
        ]);
    }

    public function destroy(Customer $customer)
    {
        $customer->delete();

        return redirect()->route('customer.index')->with('swal', [
            'icon' => 'success',
            'title' => 'Deleted!',
            'text' => 'Customer has been deleted successfully.'
        ]);
    }

    public function draftDestroy(DraftCustomer $draftCustomer)
    {
        $draftCustomer->delete();

        return redirect()->route('draft-customer.index')->with('swal', [
            'icon' => 'success',
            'title' => 'Deleted!',
            'text' => 'Draft Customer has been deleted successfully.'
        ]);
    }
}
