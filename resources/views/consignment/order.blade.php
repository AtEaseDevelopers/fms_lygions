@extends('component.layout')
@section('title', 'Truck Planning')
@section('content')

    @php
        use App\Models\Truck;
        use App\Models\Subcon;
        use App\Models\Customer;
        use App\Models\DraftCustomer;
        use App\Models\Unit;

        $trucks = Truck::select('number', 'chassis_type', 'size')->get();
        $subcons = Subcon::select('truck_no', 'chassis_type', 'size')->get();
        $currentPage = $consignments->currentPage();
        $lastPage = $consignments->lastPage();
        $query = request()->query();
        unset($query['page']);
        unset($query['per_page']);

        $trucks = Truck::select('number', 'chassis_type', 'size')->get();
        $subcons = Subcon::select('truck_no', 'chassis_type', 'size')->get();

        $consignors = Customer::where('type', 'Consignor')
            ->pluck('name')
            ->merge(DraftCustomer::where('type', 'Consignor')->where('migrated', false)->pluck('name'))
            ->unique()
            ->sort()
            ->values()
            ->toArray();

        $consignees = Customer::where('type', 'Consignee')
            ->pluck('name')
            ->merge(DraftCustomer::where('type', 'Consignee')->where('migrated', false)->pluck('name'))
            ->unique()
            ->sort()
            ->values()
            ->toArray();

        $truckGroups = Truck::select('chassis_type')->distinct()->pluck('chassis_type')->filter()->values()->toArray();

        $units = Unit::all();
    @endphp
    @if (session('swal'))
        <script>
            Swal.fire({
                icon: "{{ session('swal.icon') }}",
                title: "{{ session('swal.title') }}",
                text: "{{ session('swal.text') }}",
                showConfirmButton: false,
                timer: 2000
            });
        </script>
    @endif
    <div class="row">
        <!-- Main Table Section -->
        <div class="col-md-12" id="tableSection">
            <div class="card">

                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center flex-wrap mb-3">
                        <form class="d-flex gap-3 align-items-stretch" id="filterForm" style="width: 100%;">
                            <div class="input-group">
                                <input type="text" name="filter_daterange" class="form-control" id="filter_daterange"
                                    value="{{ request('filter_daterange') }}" placeholder="Filter Date Range"
                                    autocomplete="off">
                                <span class="input-group-text"><i class="nc-icon nc-calendar-60"></i></span>
                                <button type="button" class="btn btn-outline-info " id="searchDateBtn"
                                    style="border-radius:12rem; margin-left: 0.5rem;">
                                    <i class="bi bi-search"></i>
                                </button>
                            </div>

                            <div class="input-group no-border w-100">
                                <input type="text" class="form-control" placeholder="Search..." name="search"
                                    value="{{ request('search') }}">
                                <div class="input-group-append">
                                    <span class="input-group-text"><i class="nc-icon nc-zoom-split"></i></span>
                                </div>
                            </div>

                            <div class="input-group">
                                <select name="status" id="statusFilter" class="form-select text-white">
                                    <option value="" {{ !request('status') ? 'selected' : '' }}
                                        style="background-color: #ffffff; color: #000;">Select Status
                                    </option>
                                    <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}
                                        style="background-color: #6c757d">Pending
                                    </option>
                                    <option value="planning" {{ request('status') == 'planning' ? 'selected' : '' }}
                                        style="background-color: #fbc658">Planning
                                    </option>
                                    <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}
                                        style="background-color: #28a745">Completed
                                    </option>
                                </select>
                            </div>

                            <div class="input-group">
                                <select name="truck_type" id="truckType" class="form-select">
                                    <option value="" {{ !request('truck_type') ? 'selected' : '' }}>Truck Type
                                    </option>
                                    <option value="40" {{ request('truck_type') == '40' ? 'selected' : '' }}>40 ft
                                    </option>
                                    <option value="20" {{ request('truck_type') == '20' ? 'selected' : '' }}>20 ft
                                    </option>
                                </select>
                            </div>

                            <div class="input-group">
                                <select name="truck_number" id="truckNumber" class="form-select">
                                    <option value="" {{ !request('truck_number') ? 'selected' : '' }}>Truck Number
                                    </option>
                                    @foreach ($trucks_no as $truck)
                                        <option value="{{ $truck->number }}"
                                            {{ request('truck_number') == $truck->number ? 'selected' : '' }}>
                                            {{ $truck->number }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="input-group" style="width: 5%">
                                <button type="button" class="btn btn-danger" style="margin: 0;" id="clearBtn">
                                    <i class="bi bi-x"></i>
                                </button>
                            </div>
                        </form>

                        <div class="d-flex justify-content-end align-items-center w-100">
                            <!-- All Buttons to the Right -->
                            <div class="d-flex gap-2">
                                <button type="button" id="bulkSaveBtn" class="btn btn-success d-none"
                                    style="border-radius: 0.2rem; display: inline-flex; align-items: center;">
                                    <i class="bi bi-save me-1" style="font-size: 20px;"></i>
                                    Save
                                </button>
                                <button type="button" class="btn btn-success" id="addInlineRowBtn"
                                    style="border-radius: 0.2rem; display: inline-flex; align-items: center;">
                                    <i class="bi bi-plus-circle me-1" style="font-size: 20px;"></i>
                                    Add Row
                                </button>
                                <button type="button" class="btn btn-outline-secondary" id="toggleTruckDetails"
                                    style="border-radius: 0.2rem; display: inline-flex; align-items: center;">
                                    <i class="bi bi-layout-sidebar-reverse me-1" style="font-size: 20px;"></i>
                                    Truck Details
                                </button>
                                <a href="{{ route('archived-consignment-order.index') }}">
                                    <button type="button" class="btn btn-outline-warning"
                                        style="border-radius: 0.2rem; display: inline-flex; align-items: center;">
                                        <i class="bi bi-archive me-1" style="font-size: 20px;"></i>
                                        View Archived CSN
                                    </button>
                                </a>
                                <div class="d-flex align-items-center gap-2 justify-content-center">
                                    <button type="button" class="btn btn-primary" data-bs-toggle="modal"
                                        data-bs-target="#addModal"
                                        style="border-radius: 0.2rem; display: inline-flex; align-items: center;">
                                        <i class="bi bi-clipboard2-plus-fill me-1" style="font-size: 20px;"></i>
                                        Create New Truck Planning
                                    </button>
                                </div>

                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <form method="GET" class="d-flex align-items-center gap-2">
                                        <label for="per_page" class="mb-0">Show</label>
                                        <select name="per_page" id="per_page" class="form-select"
                                            onchange="this.form.submit()">
                                            @foreach ([5, 10, 25, 50] as $limit)
                                                <option value="{{ $limit }}"
                                                    {{ request('per_page', 10) == $limit ? 'selected' : '' }}>
                                                    {{ $limit }}</option>
                                            @endforeach
                                        </select>
                                        <span class="mb-0">entries</span>
                                    </form>
                                </div>

                                @include('consignment.add-order')

                            </div>
                        </div>

                    </div>

                    {{-- <div class="table-responsive"> --}}
                    <div id="tableScrollTop" style="overflow-x:auto; overflow-y:hidden;"></div>
                    <div id="tableScrollBottom" style="overflow-x:auto;">
                        <table class="table table-sm table-striped table-bordered align-middle table-nowrap"
                            style="font-size: 0.75rem; border-collapse: collapse;">
                            <thead class="">
                                <tr>
                                    <th class="sticky-col"></th>
                                    <th class="sticky-col">No</th>
                                    <th class="sticky-col">
                                        <a class="text-dark text-decoration-none"
                                            href="{{ route('consignment-order.index', array_merge(request()->query(), ['sort_by' => 'load_date', 'sort_order' => request('sort_order') === 'asc' && request('sort_by') === 'load_date' ? 'desc' : 'asc'])) }}">
                                            <span class="d-inline-block" style="white-space: normal;">
                                                Pick Up Date</span>
                                            @if (request('sort_by') == 'load_date')
                                                <i
                                                    class="bi bi-caret-{{ request('sort_order') == 'asc' ? 'up' : 'down' }}-fill"></i>
                                            @endif
                                        </a>
                                    </th>
                                    {{-- <th>
                                        <a class="text-dark text-decoration-none"
                                            href="{{ route('consignment-order.index', array_merge(request()->query(), ['sort_by' => 'consignment_no', 'sort_order' => request('sort_order') === 'asc' && request('sort_by') === 'consignment_no' ? 'desc' : 'asc'])) }}">
                                            <span class="d-inline-block" style="white-space: normal;">
                                                Consignment Order No.
                                            </span>
                                            @if (request('sort_by') == 'consignment_no')
                                                <i
                                                    class="bi bi-caret-{{ request('sort_order') == 'asc' ? 'up' : 'down' }}-fill"></i>
                                            @endif
                                        </a>
                                    </th> --}}
                                    <th class="sticky-col">
                                        <a class="text-dark text-decoration-none"
                                            href="{{ route('consignment-order.index', array_merge(request()->query(), ['sort_by' => 'consignor', 'sort_order' => request('sort_order') === 'asc' && request('sort_by') === 'consignor' ? 'desc' : 'asc'])) }}">
                                            Consignor
                                            @if (request('sort_by') == 'consignor')
                                                <i
                                                    class="bi bi-caret-{{ request('sort_order') == 'asc' ? 'up' : 'down' }}-fill"></i>
                                            @endif
                                        </a>
                                    </th>
                                    <th class="sticky-col">
                                        <a class="text-dark text-decoration-none"
                                            href="{{ route('consignment-order.index', array_merge(request()->query(), ['sort_by' => 'pick_point', 'sort_order' => request('sort_order') === 'asc' && request('sort_by') === 'pick_point' ? 'desc' : 'asc'])) }}">
                                            <span class="d-inline-block" style="white-space: normal;">
                                                Pick Point </span>
                                            @if (request('sort_by') == 'pick_point')
                                                <i
                                                    class="bi bi-caret-{{ request('sort_order') == 'asc' ? 'up' : 'down' }}-fill"></i>
                                            @endif
                                        </a>
                                    </th>
                                    <th class="sticky-col">
                                        <a class="text-dark text-decoration-none"
                                            href="{{ route('consignment-order.index', array_merge(request()->query(), ['sort_by' => 'consignee', 'sort_order' => request('sort_order') === 'asc' && request('sort_by') === 'consignee' ? 'desc' : 'asc'])) }}">
                                            Consignee
                                            @if (request('sort_by') == 'consignee')
                                                <i
                                                    class="bi bi-caret-{{ request('sort_order') == 'asc' ? 'up' : 'down' }}-fill"></i>
                                            @endif
                                        </a>
                                    </th>
                                    <th class="sticky-col">
                                        <a class="text-dark text-decoration-none"
                                            href="{{ route('consignment-order.index', array_merge(request()->query(), ['sort_by' => 'drop_point', 'sort_order' => request('sort_order') === 'asc' && request('sort_by') === 'drop_point' ? 'desc' : 'asc'])) }}">
                                            <span class="d-inline-block" style="white-space: normal;">
                                                Drop Point </span>
                                            @if (request('sort_by') == 'drop_point')
                                                <i
                                                    class="bi bi-caret-{{ request('sort_order') == 'asc' ? 'up' : 'down' }}-fill"></i>
                                            @endif
                                        </a>
                                    </th>
                                    <th><span class="d-inline-block" style="white-space: normal;">
                                            Pick Truck Size</span></th>
                                    <th><span class="d-inline-block" style="white-space: normal;">
                                            Drop Truck Size </span></th>
                                    <th><span class="d-inline-block" style="white-space: normal;">
                                            Pick Truck Type </span></th>
                                    <th><span class="d-inline-block" style="white-space: normal;">
                                            Drop Truck Type </span></th>
                                    <th><span class="d-inline-block" style="white-space: normal;">
                                            Pick Up Time </span></th>
                                    <th><span class="d-inline-block" style="white-space: normal;">
                                            Qty </span></th>
                                    <th>Unit</th>
                                    <th><span class="d-inline-block" style="white-space: normal;">
                                            Pre-Pick </span></th>
                                    {{-- <th>
                                        <a class="text-dark text-decoration-none"
                                            href="{{ route('consignment-order.index', array_merge(request()->query(), ['sort_by' => 'truck_type', 'sort_order' => request('sort_order') === 'asc' && request('sort_by') === 'truck_type' ? 'desc' : 'asc'])) }}">
                                            <span class="d-inline-block" style="white-space: normal;">
                                                Truck Type</span>
                                            @if (request('sort_by') == 'truck_type')
                                                <i
                                                    class="bi bi-caret-{{ request('sort_order') == 'asc' ? 'up' : 'down' }}-fill"></i>
                                            @endif
                                        </a>
                                    </th> --}}
                                    <th>
                                        <a class="text-dark text-decoration-none"
                                            href="{{ route('consignment-order.index', array_merge(request()->query(), ['sort_by' => 'truck_number', 'sort_order' => request('sort_order') === 'asc' && request('sort_by') === 'truck_number' ? 'desc' : 'asc'])) }}">
                                            <span class="d-inline-block" style="white-space: normal;">
                                                Truck Number</span>
                                            @if (request('sort_by') == 'truck_number')
                                                <i
                                                    class="bi bi-caret-{{ request('sort_order') == 'asc' ? 'up' : 'down' }}-fill"></i>
                                            @endif
                                        </a>
                                    </th>
                                    <th>
                                        <a class="text-dark text-decoration-none"
                                            href="{{ route('consignment-order.index', array_merge(request()->query(), ['sort_by' => 'remarks', 'sort_order' => request('sort_order') === 'asc' && request('sort_by') === 'remarks' ? 'desc' : 'asc'])) }}">
                                            Remarks
                                            @if (request('sort_by') == 'remarks')
                                                <i
                                                    class="bi bi-caret-{{ request('sort_order') == 'asc' ? 'up' : 'down' }}-fill"></i>
                                            @endif
                                        </a>
                                    </th>
                                    <th>Billing Remarks</th>
                                    <th>
                                        <a class="text-dark text-decoration-none"
                                            href="{{ route('consignment-order.index', array_merge(request()->query(), ['sort_by' => 'status', 'sort_order' => request('sort_order') === 'asc' && request('sort_by') === 'status' ? 'desc' : 'asc'])) }}">
                                            Status
                                            @if (request('sort_by') == 'status')
                                                <i
                                                    class="bi bi-caret-{{ request('sort_order') == 'asc' ? 'up' : 'down' }}-fill"></i>
                                            @endif
                                        </a>
                                    </th>
                                </tr>
                            </thead>

                            <tbody>
                                @forelse ($consignments as $index => $order)
                                    <tr class="order-row" data-id="{{ $order->id }}"
                                        data-pick-type="{{ $order['pick_truck_type'] ?? '' }}"
                                        data-pick-size="{{ $order['pick_truck_size'] ?? '' }}"
                                        data-drop-type="{{ $order['drop_truck_type'] ?? '' }}"
                                        data-drop-size="{{ $order['drop_truck_size'] ?? '' }}"
                                        data-selected-truck="{{ $order['truck_number'] ?? '' }}">
                                        <td class="sticky-col">
                                            <div class="d-flex align-items-center gap-2 justify-content-center">
                                                <input type="checkbox">

                                            </div>
                                        </td>
                                        <td class="sticky-col" data-bs-toggle="tooltip" data-bs-placement="right"
                                            title="{{ $order['consignment_no'] ?? '-' }}">
                                            {{ $loop->iteration + ($consignments->currentPage() - 1) * $consignments->perPage() }}
                                        </td>
                                        <td class="sticky-col" style="text-align: left">
                                            {{ $order->load_date }}
                                            @if ($order->express_mode)
                                                <i class="bi bi-speedometer2 text-danger ms-2 express-icon"
                                                    title="Express Mode"></i>
                                            @endif
                                        </td>
                                        {{-- <td>{{ $order['consignment_no'] ?? '-' }}</td> --}}
                                        <td class="sticky-col">
                                            @php

                                                // Get consignor display name
                                                $consignorDisplay = '-';
                                                if (!empty($order['consignor'])) {
                                                    // Try to find the customer by name
                                                    $cust = $customers->firstWhere('name', $order['consignor']);
                                                    if ($cust && !empty($cust->nickname)) {
                                                        $consignorDisplay = $cust->nickname;
                                                    } else {
                                                        $consignorDisplay = $order['consignor'];
                                                    }
                                                }
                                            @endphp
                                            {!! nl2br(e(wordwrap($consignorDisplay, 20, "\n", true))) !!}
                                        </td>


                                        <td class="sticky-col">{{ $order['pick_point'] ?? '-' }}</td>
                                        <td class="sticky-col">
                                            @php
                                                // Get consignee display name
                                                $consigneeDisplay = '-';
                                                if (!empty($order['consignee'])) {
                                                    $cust = $customers->firstWhere('name', $order['consignee']);
                                                    if ($cust && !empty($cust->nickname)) {
                                                        $consigneeDisplay = $cust->nickname;
                                                    } else {
                                                        $consigneeDisplay = $order['consignee'];
                                                    }
                                                }
                                            @endphp
                                            {!! nl2br(e(wordwrap($consigneeDisplay, 20, "\n", true))) !!}
                                        </td>


                                        <td class="sticky-col">{{ $order['drop_point'] ?? '-' }}</td>
                                        <td>{{ $order['pick_truck_size'] ?? '-' }}</td>
                                        <td>{{ $order['drop_truck_size'] ?? '-' }}</td>
                                        <td>{{ $order['pick_truck_type'] ?? '-' }}</td>
                                        <td>{{ $order['drop_truck_type'] ?? '-' }}</td>
                                        <td>{{ $order['pick_time'] ? \Carbon\Carbon::parse($order['pick_time'])->format('H:i') : '-' }}
                                        </td>
                                        <td>
                                            @php
                                                $quantities = is_string($order['quantity'])
                                                    ? json_decode($order['quantity'], true)
                                                    : (is_array($order['quantity'])
                                                        ? $order['quantity']
                                                        : []);
                                            @endphp

                                            @forelse ($quantities as $qty)
                                                {{ $qty }}<br>
                                            @empty
                                                -
                                            @endforelse
                                        </td>

                                        <td>
                                            @php
                                                $units = is_string($order['unit'])
                                                    ? json_decode($order['unit'], true)
                                                    : (is_array($order['unit'])
                                                        ? $order['unit']
                                                        : []);
                                            @endphp

                                            @forelse ($units as $unit)
                                                {{ $unit }}<br>
                                            @empty
                                                -
                                            @endforelse
                                        </td>

                                        <td>{{ $order['pre_pick'] ?? '-' }}</td>

                                        {{-- <td>
                                            <select
                                                class="form-select
                                        form-select-sm auto-width">
                                                <option value="">-</option>
                                                @foreach ($trucks_grp as $group)
                                                    <option value="{{ $group }}"
                                                        {{ $order['truck_type'] == $group ? 'selected' : '' }}>
                                                        {{ $group }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </td> --}}
                                        {{-- <td>
                                            <select class="form-select form-select-sm auto-width" name="truck_number">
                                                <option value="">-</option>
                                                @foreach ($trucks_no as $truck_number)
                                                    <option value="{{ $truck_number['number'] }}"
                                                        {{ $order['truck_number'] == $truck_number['number'] ? 'selected' : '' }}>
                                                        {{ $truck_number['number'] }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </td> --}}
                                        <td>
                                            <select class="form-select form-select-sm truck-number-select">
                                                <option value=""></option>
                                            </select>
                                        </td>

                                        <td>{{ $order['remarks'] ?? '-' }}</td>
                                        <td>{{ $order['billing_remark'] ?? '-' }}</td>

                                        <td
                                            class="
                                            @if ($order['status'] == 'Completed') bg-success text-white
                                            @elseif ($order['status'] == 'Planning') bg-warning text-dark
                                            @else bg-secondary text-white @endif">
                                            {{-- <span
                                                class="badge bg-{{ $order['status'] == 'Completed' ? 'success' : ($order['status'] == 'Planning' ? 'warning text-dark' : 'secondary') }}">
                                                {{ $order['status'] }}
                                            </span> --}}
                                            {{ $order['status'] }}

                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center justify-content-center gap-2">
                                                <!-- Edit Button -->
                                                <button type="button" class="btn btn-info" data-bs-toggle="modal"
                                                    data-bs-target="#editModal{{ $index }}"> <i
                                                        class="bi bi-pencil-square"></i> </button>

                                                <!-- Delete Button -->
                                                <form action="{{ route('consignment-order.destroy', $order->id) }}"
                                                    method="POST" class="delete-form m-0 p-0">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-danger"
                                                        data-number="{{ $order->consignment_no ?? 'this order' }}">
                                                        <i class="bi bi-trash-fill"></i>
                                                    </button>
                                                </form>
                                            </div>
                                            @include('consignment.edit-order', [
                                                'order' => array_merge($order->toArray(), [
                                                    'index' => $order->index,
                                                ]),
                                            ])
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="25" class="text-center">No consignment orders found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{-- </div> --}}

                </div>
                <div class="d-flex justify-content-between align-items-center mt-4">
                    <div>
                        Showing {{ $consignments->firstItem() }} to {{ $consignments->lastItem() }} of
                        {{ $consignments->total() }} entries
                    </div>

                    <div>
                        {{ $consignments->appends(request()->except('page'))->links('pagination::bootstrap-5') }}
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column: Truck Cards -->
        <div class="col-md-2 d-none" id="truckDetailsSection">
            <div class="d-flex justify-content-between align-items-center flex-wrap ">
                <form class="d-flex flex-column gap-1 w-100">
                    <div class="input-group">
                        <input type="date" class="form-control" placeholder="Filter Date Range" autocomplete="off">
                    </div>

                    <div class="input-group">
                        <select name="status" class="form-control">
                            <option value="A" selected>Select a Truck Type</option>
                            <option value="A">Truck Type: SNL 40#</option>
                            <option value="A">20 footer & below</option>
                            <option value="A">Truck Type: SNL 40#</option>
                        </select>
                    </div>
                </form>

            </div>
            <h4 style="margin: 0">Truck Details</h4>
            @foreach ($trucks_no as $truck)
                @php
                    $color =
                        $truck->utilization >= 100 ? 'danger' : ($truck->utilization >= 90 ? 'warning' : 'success');
                @endphp

                <div class="card mb-3 p-3 truck-card" data-truck="{{ $truck->number }}">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="d-flex flex-column">
                            <!-- Truck Number -->
                            <h5 class="mb-2 fw-bold">{{ $truck->number }}</h5>

                            <!-- Capacity Info -->
                            <div>
                                Capacity:
                                <strong
                                    class="text-{{ $color }}">{{ number_format($truck->remaining, 2) }}</strong> /
                                {{ number_format($truck->floor_space, 0) }}
                            </div>
                        </div>

                        <div class="d-flex flex-column align-items-center text-end">
                            <div class="small mb-1">{{ $truck->group }}</div>

                            <i class="fa fa-truck fa-3x text-{{ $color }}"></i>
                            <div class="fw-bold text-{{ $color }}">{{ round($truck->utilization) }}%</div>
                        </div>
                    </div>
                </div>
            @endforeach

        </div>

    </div>
    <!-- Add Modal -->
    {{-- <div class="modal fade" id="addCsnModal" tabindex="-1" aria-labelledby="csnModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <!-- Header -->
                <div class="modal-header">
                    <h5 class="modal-title" id="availabilityModalLabel">
                        <i class="bi bi-pencil-square me-2"></i> Add Consignment Order
                    </h5>
                    <button type="button" class="btn-close text-danger" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>

                <!-- Body -->
                <div class="modal-body">
                    <form id="availabilityForm">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Truck Type:</label>
                            <select class="form-select">
                                <option>SNL 20#</option>
                                <option>SNL 40#</option>
                                <option>20 footer & below</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Truck Number:</label>
                            <select class="form-select">
                                <option>VKE 9711</option>
                                <option>ABC 1234</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Truck Team:</label>
                            <select class="form-select">
                                <option>1</option>
                                <option>2</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Status:</label>
                            <select class="form-select">
                                <option>Available</option>
                                <option>Off day</option>
                                <option>Occupied</option>
                                <option>Maintenance</option>
                            </select>
                        </div>
                    </form>
                </div>

                <!-- Footer -->
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">CANCEL</button>
                    <button type="submit" class="btn btn-primary">SUBMIT</button>
                </div>
            </div>
        </div>
    </div> --}}
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

@endsection
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {

        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function(tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });

        // Toggle Truck Details Sidebar
        const toggleBtn = document.getElementById('toggleTruckDetails');
        const tableSection = document.getElementById('tableSection');
        const truckDetailsSection = document.getElementById('truckDetailsSection');

        // Load saved preference from localStorage
        const sidebarVisible = localStorage.getItem('truckDetailsSidebarVisible') === 'true';
        if (sidebarVisible) {
            truckDetailsSection.classList.remove('d-none');
            tableSection.classList.remove('col-md-12');
            tableSection.classList.add('col-md-10');
            toggleBtn.classList.remove('btn-outline-secondary');
            toggleBtn.classList.add('btn-secondary');
        }

        toggleBtn.addEventListener('click', function() {
            const isHidden = truckDetailsSection.classList.contains('d-none');

            if (isHidden) {
                // Show sidebar
                truckDetailsSection.classList.remove('d-none');
                tableSection.classList.remove('col-md-12');
                tableSection.classList.add('col-md-10');
                toggleBtn.classList.remove('btn-outline-secondary');
                toggleBtn.classList.add('btn-secondary');
                localStorage.setItem('truckDetailsSidebarVisible', 'true');
            } else {
                // Hide sidebar
                truckDetailsSection.classList.add('d-none');
                tableSection.classList.remove('col-md-10');
                tableSection.classList.add('col-md-12');
                toggleBtn.classList.remove('btn-secondary');
                toggleBtn.classList.add('btn-outline-secondary');
                localStorage.setItem('truckDetailsSidebarVisible', 'false');
            }
        });

        const checkboxes = document.querySelectorAll('.order-row input[type="checkbox"]');
        const saveBtn = document.getElementById('bulkSaveBtn');

        function toggleSaveButton() {
            const anyChecked = Array.from(checkboxes).some(cb => cb.checked);
            saveBtn.classList.toggle('d-none', !anyChecked);
        }

        checkboxes.forEach(cb => cb.addEventListener('change', toggleSaveButton));

        saveBtn.addEventListener('click', function() {
            // Collect selected rows
            const selectedRows = Array.from(checkboxes)
                .filter(cb => cb.checked)
                .map(cb => {
                    const row = cb.closest('tr');
                    const consignmentId = row.dataset.id;
                    const select = row.querySelector('.truck-number-select');
                    const truckNumber = select ? select.value : '';
                    return {
                        id: consignmentId,
                        truck_number: truckNumber
                    };
                })
                .filter(r => r.truck_number); // ignore empty selections

            if (selectedRows.length === 0) {
                Swal.fire('Error', 'No truck selected for the checked rows.', 'error');
                return;
            }

            Swal.fire({
                title: 'Are you sure?',
                text: `This will update ${selectedRows.length} selected order(s).`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, save changes!'
            }).then((result) => {
                if (result.isConfirmed) {
                    const formData = new FormData();
                    selectedRows.forEach((row, i) => {
                        formData.append(`orders[${i}][id]`, row.id);
                        formData.append(`orders[${i}][truck_number]`, row.truck_number);
                    });

                    fetch("{{ route('consignment-order.bulk-update') }}", {
                            method: "POST",
                            headers: {
                                "X-CSRF-TOKEN": "{{ csrf_token() }}"
                            },
                            body: formData
                        })
                        .then(res => res.json())
                        .then(data => {
                            if (data.success) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Updated!',
                                    text: data.message,
                                    timer: 2000,
                                    showConfirmButton: false
                                }).then(() => {
                                    location.reload(); // refresh table
                                });
                            } else {
                                Swal.fire('Error', data.message || 'Something went wrong',
                                    'error');
                            }
                        })
                        .catch(err => Swal.fire('Error', 'Failed to update orders', 'error'));
                }
            });
        });

        // Get truck data
        const ALL_TRUCKS = @json($trucks);
        const ALL_SUBCONS = @json($subcons);

        function normalize(val) {
            return (val || '').toString().trim().toLowerCase();
        }

        function isValidTruck(truck, pickType, pickSize, dropType, dropSize) {
            // chassis_type is mandatory
            if (!truck.chassis_type) return false;

            const tType = normalize(truck.chassis_type);
            const tSize = normalize(truck.size); // may be empty

            let matchPick = false;
            let matchDrop = false;

            /* ---------- PICK CHECK ---------- */
            if (pickType) {
                matchPick = tType === pickType;

                // size only checked if truck HAS size AND order HAS size
                if (matchPick && tSize && pickSize) {
                    matchPick = tSize === pickSize;
                }
            }

            /* ---------- DROP CHECK ---------- */
            if (dropType) {
                matchDrop = tType === dropType;

                if (matchDrop && tSize && dropSize) {
                    matchDrop = tSize === dropSize;
                }
            }

            // Return true if EITHER pick OR drop matches
            return matchPick || matchDrop;
        }

        // Populate all truck number dropdowns
        document.querySelectorAll('.order-row').forEach(row => {
            const pickType = normalize(row.dataset.pickType);
            const pickSize = normalize(row.dataset.pickSize);
            const dropType = normalize(row.dataset.dropType);
            const dropSize = normalize(row.dataset.dropSize);
            const selectedTruck = row.dataset.selectedTruck;

            const select = row.querySelector('.truck-number-select');
            if (!select) return;

            const hasPickCriteria = !!pickType;
            const hasDropCriteria = !!dropType;

            // If no criteria at all, show empty dropdown
            if (!hasPickCriteria && !hasDropCriteria) {
                select.innerHTML = '<option value="">-</option>';
                return;
            }

            // Clear and enable dropdown
            select.innerHTML = '<option value="">-</option>';
            select.disabled = false;

            // Add matching main trucks
            ALL_TRUCKS
                .filter(t => isValidTruck(t, pickType, pickSize, dropType, dropSize))
                .forEach(t => {
                    const opt = document.createElement('option');
                    opt.value = t.number;
                    opt.textContent = t.number;
                    if (t.number === selectedTruck) opt.selected = true;
                    select.appendChild(opt);
                });

            // Add matching subcon trucks
            ALL_SUBCONS
                .filter(s => isValidTruck(s, pickType, pickSize, dropType, dropSize))
                .forEach(s => {
                    const opt = document.createElement('option');
                    opt.value = s.truck_no;
                    opt.textContent = s.truck_no + ' (Subcon)';
                    if (s.truck_no === selectedTruck) opt.selected = true;
                    select.appendChild(opt);
                });
        });

        // Set sticky column positions
        setTimeout(function() {
            const table = document.querySelector("table.table");
            if (!table) return;

            const firstRow = table.querySelector("tbody tr:first-child");
            const headerRow = table.querySelector("thead tr");

            if (!firstRow && !headerRow) return;

            // Use tbody row if available, otherwise use thead
            const cells = firstRow ? firstRow.querySelectorAll("td.sticky-col") : headerRow
                .querySelectorAll("th.sticky-col");

            let offset = 0;

            cells.forEach((cell, index) => {
                if (index > 0) {
                    document.documentElement.style.setProperty(`--col-${index}`, offset + "px");
                }

                const width = Math.round(cell.getBoundingClientRect().width);
                offset += width;
            });
        }, 150);

        // Initialize daterangepicker
        const daterangeInput = $('#filter_daterange');
        const filterForm = $('#filterForm');
        const searchDateBtn = $('#searchDateBtn');

        // Get initial value from input (Blade)
        let initialValue = daterangeInput.val();
        let startDate = moment().startOf('day');
        let endDate = moment().endOf('day');

        if (initialValue) {
            const dates = initialValue.split(' to ');
            if (dates.length === 2) {
                startDate = moment(dates[0], 'YYYY-MM-DD');
                endDate = moment(dates[1], 'YYYY-MM-DD');
            }
        }

        // Initialize daterangepicker
        daterangeInput.daterangepicker({
            startDate: startDate,
            endDate: endDate,
            autoUpdateInput: !!initialValue,
            locale: {
                format: 'YYYY-MM-DD',
                cancelLabel: 'Clear',
                applyLabel: 'Apply'
            },
            opens: 'right',
            autoApply: false
        });

        // When date range is applied, just update the input (don't submit yet)
        daterangeInput.on('apply.daterangepicker', function(ev, picker) {
            const selectedRange = picker.startDate.format('YYYY-MM-DD') + ' to ' + picker.endDate
                .format('YYYY-MM-DD');
            $(this).val(selectedRange);
            updateClearButton();
        });

        // When date range is cleared
        daterangeInput.on('cancel.daterangepicker', function(ev, picker) {
            $(this).val('');
            updateClearButton();
        });

        // Search button for date range - THIS SUBMITS THE FORM
        searchDateBtn.on('click', function() {
            if (daterangeInput.val()) {
                filterForm.submit();
            } else {
                Swal.fire({
                    icon: 'warning',
                    title: 'No Date Selected',
                    text: 'Please select a date range first',
                    timer: 2000,
                    showConfirmButton: false
                });
            }
        });

        // Other filters trigger submit immediately
        $('#statusFilter, #truckType, #truckNumber').on('change', function() {
            filterForm.submit();
        });

        // Search triggers submit on Enter
        $('input[name="search"]').on('keypress', function(e) {
            if (e.key === 'Enter' || e.keyCode === 13) {
                e.preventDefault();
                filterForm.submit();
            }
        });

        // Clear button functionality
        $('#clearBtn').on('click', function() {
            // Clear all form inputs
            daterangeInput.val('');
            $('input[name="search"]').val('');
            $('#statusFilter').val('');
            $('#truckType').val('');
            $('#truckNumber').val('');

            // Redirect to base URL without any filters
            window.location.href = "{{ route('consignment-order.index') }}";
        });

        // Show/hide clear button based on active filters
        function updateClearButton() {
            const hasFilters = daterangeInput.val() ||
                $('input[name="search"]').val() ||
                $('#statusFilter').val() ||
                $('#truckType').val() ||
                $('#truckNumber').val();

            if (hasFilters) {
                $('#clearBtn').removeClass('d-none');
            } else {
                $('#clearBtn').addClass('d-none');
            }
        }

        // Check on page load
        updateClearButton();

        // Check when filters change
        $('#filterForm input, #filterForm select').on('change input', function() {
            updateClearButton();
        });

        // Delete form confirmation
        document.querySelectorAll('.delete-form').forEach(form => {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                const csn = form.querySelector('button[type="submit"]').getAttribute(
                    "data-number");

                Swal.fire({
                    title: 'Are you sure?',
                    text: "This will delete the order '" + csn + "'",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Yes, delete it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            });
        });

        // Truck card selection
        document.querySelectorAll('.truck-card').forEach(card => {
            card.addEventListener('click', function() {
                document.querySelectorAll('.truck-card').forEach(c => c.classList.remove(
                    'active'));
                this.classList.add('active');
                const truckNumber = this.getAttribute('data-truck');
                console.log('Selected truck:', truckNumber);
            });
        });

        // Add/Remove quantity-unit rows
        document.addEventListener("click", function(e) {
            // Add new row
            const addBtn = e.target.closest(".addRow");
            if (addBtn) {
                const container = addBtn.closest(".quantityUnitContainer");
                const row = addBtn.closest(".quantity-unit-row");

                const clone = row.cloneNode(true);
                clone.querySelectorAll("input").forEach(input => {
                    input.value = "";
                });
                clone.querySelectorAll("select").forEach(select => {
                    select.selectedIndex = 0;
                });

                const btn = clone.querySelector(".addRow");
                btn.classList.remove("btn-success", "addRow");
                btn.classList.add("btn-danger", "removeRow");
                btn.innerHTML = '<i class="bi bi-dash"></i>';

                container.appendChild(clone);
            }

            // Remove row
            const removeBtn = e.target.closest(".removeRow");
            if (removeBtn) {
                const row = removeBtn.closest(".quantity-unit-row");
                if (document.querySelectorAll(".quantity-unit-row").length > 1) {
                    row.remove();
                }
            }
        });
        // Add Inline Row functionality
        const addInlineRowBtn = document.getElementById('addInlineRowBtn');
        const tableBody = document.querySelector('table.table tbody');

        // Get consignor and consignee lists from PHP
        const CONSIGNORS = @json($consignors ?? []);
        const CONSIGNEES = @json($consignees ?? []);
        const TRUCK_GROUPS = @json($truckGroups ?? []);
        const UNITS = @json($units ?? []);

        function recalculateStickyColumns() {
            setTimeout(function() {
                const table = document.querySelector("table.table");
                if (!table) return;

                // Use the first data row (not the inline edit row) for measurements
                const firstRow = table.querySelector("tbody tr:not(.inline-edit-row)");
                const headerRow = table.querySelector("thead tr");

                if (!firstRow && !headerRow) return;

                // Prefer data row for measurement, fallback to header
                const cells = firstRow ? firstRow.querySelectorAll("td.sticky-col") : headerRow
                    .querySelectorAll("th.sticky-col");

                let offset = 0;

                cells.forEach((cell, index) => {
                    if (index > 0) {
                        document.documentElement.style.setProperty(`--col-${index}`, offset +
                            "px");
                    }

                    const width = Math.round(cell.getBoundingClientRect().width);
                    offset += width;
                });

                console.log('Sticky columns recalculated'); // Debug log
            }, 150); // Increased timeout to ensure DOM is fully rendered
        }

        addInlineRowBtn.addEventListener('click', function() {
            // Check if there's already an inline edit row
            if (document.querySelector('.inline-edit-row')) {
                Swal.fire('Warning', 'Please save or cancel the current row first', 'warning');
                return;
            }

            // Build units options safely
            let unitsOptions = '<option value="">-</option>';
            if (UNITS && Array.isArray(UNITS)) {
                unitsOptions += UNITS.map(u => {
                    const unitUpper = (u.unit || '').toUpperCase();
                    const unitDesc = u.desc || '';
                    return `<option value="${u.unit}">${unitUpper} — ${unitDesc}</option>`;
                }).join('');
            }

            // Build truck groups options
            let truckGroupsOptions = '<option value="">-</option><option value="all">ALL</option>';
            if (TRUCK_GROUPS && Array.isArray(TRUCK_GROUPS)) {
                truckGroupsOptions += TRUCK_GROUPS.map(g => `<option value="${g}">${g}</option>`).join(
                    '');
            }

            // Build consignor options
            let consignorOptions = '';
            if (CONSIGNORS && Array.isArray(CONSIGNORS)) {
                consignorOptions = CONSIGNORS.map(c => `<option value="${c}"></option>`).join('');
            }

            // Build consignee options
            let consigneeOptions = '';
            if (CONSIGNEES && Array.isArray(CONSIGNEES)) {
                consigneeOptions = CONSIGNEES.map(c => `<option value="${c}"></option>`).join('');
            }

            // Build truck options
            let truckOptions = '<option value="">-</option>';
            if (ALL_TRUCKS && Array.isArray(ALL_TRUCKS)) {
                truckOptions += ALL_TRUCKS.map(t => `<option value="${t.number}">${t.number}</option>`)
                    .join('');
            }
            if (ALL_SUBCONS && Array.isArray(ALL_SUBCONS)) {
                truckOptions += ALL_SUBCONS.map(s =>
                    `<option value="${s.truck_no}">${s.truck_no} (Subcon)</option>`).join('');
            }

            // Create new editable row
            const newRow = document.createElement('tr');
            newRow.classList.add('inline-edit-row', 'table-warning');
            newRow.innerHTML = `
        <td class="sticky-col">
            <div class="d-flex align-items-center gap-2 justify-content-center">
                <button type="button" class="btn btn-sm btn-success save-inline-row">
                    <i class="bi bi-check-lg"></i>
                </button>
                <button type="button" class="btn btn-sm btn-danger cancel-inline-row">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
        </td>
        <td class="sticky-col">NEW</td>
        <td class="sticky-col">
            <input type="date" class="form-control form-control-sm" name="load_date" required>
        </td>
        <td class="sticky-col">
            <input name="consignor" list="inline_consignor_list" class="form-control form-control-sm"
                   placeholder="Consignor" required>
            <datalist id="inline_consignor_list">
                ${consignorOptions}
            </datalist>
        </td>
        <td class="sticky-col">
            <input type="text" class="form-control form-control-sm" name="pick_point" placeholder="Pick Point" required>
        </td>
        <td class="sticky-col">
            <input name="consignee" list="inline_consignee_list" class="form-control form-control-sm"
                   placeholder="Consignee" required>
            <datalist id="inline_consignee_list">
                ${consigneeOptions}
            </datalist>
        </td>
        <td class="sticky-col">
            <input type="text" class="form-control form-control-sm" name="drop_point" placeholder="Drop Point" required>
        </td>
        <td>
            <select class="form-select form-select-sm" name="pick_truck_size">
                <option value="">-</option>
                <option value="Small">Small</option>
                <option value="Any">Any</option>
            </select>
        </td>
        <td>
            <select class="form-select form-select-sm" name="drop_truck_size">
                <option value="">-</option>
                <option value="Small">Small</option>
                <option value="Any">Any</option>
            </select>
        </td>
        <td>
            <select class="form-select form-select-sm" name="pick_truck_type">
                ${truckGroupsOptions}
            </select>
        </td>
        <td>
            <select class="form-select form-select-sm" name="drop_truck_type">
                ${truckGroupsOptions}
            </select>
        </td>
        <td>
            <input type="time" class="form-control form-control-sm" name="pick_time">
        </td>
        <td>
            <input type="number" class="form-control form-control-sm" name="quantity" placeholder="Qty">
        </td>
        <td>
            <select class="form-select form-select-sm" name="unit">
                ${unitsOptions}
            </select>
        </td>
        <td>
            <select class="form-select form-select-sm" name="pre_pick">
                <option value="">-</option>
                <option value="SELF">SELF</option>
                <option value="WVS 5404">WVS 5404</option>
                <option value="NCR 8825">NCR 8825</option>
                <option value="BSG 8826">BSG 8826</option>
            </select>
        </td>
        <td>
            <select class="form-select form-select-sm" name="truck_number">
                ${truckOptions}
            </select>
        </td>
        <td>
            <input type="text" class="form-control form-control-sm" name="remarks" placeholder="Remarks">
        </td>
        <td>
            <input type="text" class="form-control form-control-sm" name="billing_remark" placeholder="Billing Remarks">
        </td>
        <td>
            <select class="form-select form-select-sm" name="status">
                <option value="Pending" selected>Pending</option>
                <option value="Planning">Planning</option>
                <option value="Completed">Completed</option>
            </select>
        </td>
    `;

            // Insert at the top of tbody
            tableBody.insertBefore(newRow, tableBody.firstChild);

            // Recalculate sticky column positions
            recalculateStickyColumns();

            // Disable add button while editing
            addInlineRowBtn.disabled = true;

            // Scroll to top
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });

        // Event delegation for save/cancel buttons
        tableBody.addEventListener('click', function(e) {
            // Save inline row
            if (e.target.closest('.save-inline-row')) {
                const row = e.target.closest('.inline-edit-row');
                const formData = new FormData();

                // Collect all input values
                row.querySelectorAll('input, select').forEach(input => {
                    if (input.name) {
                        formData.append(input.name, input.value);
                    }
                });

                // Validate required fields
                const loadDate = row.querySelector('[name="load_date"]').value;
                const consignor = row.querySelector('[name="consignor"]').value;
                const consignee = row.querySelector('[name="consignee"]').value;
                const pickPoint = row.querySelector('[name="pick_point"]').value;
                const dropPoint = row.querySelector('[name="drop_point"]').value;

                if (!loadDate || !consignor || !consignee || !pickPoint || !dropPoint) {
                    Swal.fire('Error', 'Please fill in all required fields', 'error');
                    return;
                }

                // Show loading
                Swal.fire({
                    title: 'Saving...',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                // Submit via AJAX
                fetch("{{ route('consignment-order.store-inline') }}", {
                        method: "POST",
                        headers: {
                            "X-CSRF-TOKEN": "{{ csrf_token() }}"
                        },
                        body: formData
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Created!',
                                text: data.message,
                                timer: 2000,
                                showConfirmButton: false
                            }).then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire('Error', data.message || 'Failed to create order', 'error');
                            addInlineRowBtn.disabled = false;
                        }
                    })
                    .catch(err => {
                        console.error(err);
                        Swal.fire('Error', 'Failed to create order', 'error');
                        addInlineRowBtn.disabled = false;
                    });
            }

            // Cancel inline row
            if (e.target.closest('.cancel-inline-row')) {
                const row = e.target.closest('.inline-edit-row');
                Swal.fire({
                    title: 'Discard changes?',
                    text: 'This new row will be removed',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, discard',
                    cancelButtonText: 'No, keep editing'
                }).then((result) => {
                    if (result.isConfirmed) {
                        row.remove();
                        addInlineRowBtn.disabled = false;
                        // Recalculate sticky columns after removing row
                        recalculateStickyColumns();
                    }
                });
            }
        });
    });
</script>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
<script src="https://cdn.jsdelivr.net/npm/moment@2.29.4/moment.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
<style>
    /* Inline editing row styles */
    .inline-edit-row {
        background-color: #fff3cd !important;
    }

    .inline-edit-row td {
        background-color: #fff3cd !important;
        padding: 0.5rem 0.4rem !important;
    }

    .inline-edit-row input,
    .inline-edit-row select {
        font-size: 0.75rem;
        min-width: 100px;
    }

    .inline-edit-row .sticky-col {
        background-color: #fff3cd !important;
    }

    .inline-edit-row td.sticky-col:nth-child(1) {
        width: 80px;
        min-width: 80px;
        max-width: 80px;
    }

    .inline-edit-row td.sticky-col:nth-child(2) {
        width: 50px;
        min-width: 50px;
        max-width: 50px;
    }

    .inline-edit-row td.sticky-col:nth-child(3) {
        width: 150px;
        min-width: 150px;
    }

    .inline-edit-row td.sticky-col:nth-child(4),
    .inline-edit-row td.sticky-col:nth-child(5),
    .inline-edit-row td.sticky-col:nth-child(6),
    .inline-edit-row td.sticky-col:nth-child(7) {
        width: 180px;
        min-width: 180px;
    }

    /* Match input widths to cell widths */
    .inline-edit-row input.form-control-sm,
    .inline-edit-row select.form-select-sm {
        width: 100%;
        box-sizing: border-box;
    }

    /* Fix border collapse issue with sticky columns */
    table.table {
        border-collapse: separate !important;
        border-spacing: 0;
        width: max-content;
    }

    /* Add borders manually since we're using border-collapse: separate */
    table.table th,
    table.table td {
        border-right: 1px solid #dee2e6;
        border-bottom: 1px solid #dee2e6;
        padding: 0.25rem 0.4rem !important;
        white-space: nowrap;
    }

    table.table th:first-child,
    table.table td:first-child {
        border-left: 1px solid #dee2e6;
    }

    table.table thead th {
        border-top: 1px solid #dee2e6;
    }

    /* Force all cells to have position: relative by default */
    table.table tbody td,
    table.table thead th {
        position: relative;
        background: #fff;
    }

    table.table thead th {
        background: #f8f9fa;
        font-size: 0.65rem !important;
    }

    table.table thead th span {
        font-size: 0.65rem;
    }

    /* Sticky columns - OVERRIDE everything */
    table.table tbody td.sticky-col,
    table.table thead th.sticky-col {
        position: -webkit-sticky !important;
        position: sticky !important;
        z-index: 1 !important;
        background: #fff !important;
    }

    table.table thead th.sticky-col {
        background: #f8f9fa !important;
        z-index: 2 !important;
    }

    table.table tbody td.sticky-col {
        background: #f8f9fa !important;
        z-index: 3 !important;
    }

    /* Position each sticky column */
    table.table th.sticky-col:nth-child(1),
    table.table td.sticky-col:nth-child(1) {
        left: 0 !important;
    }

    table.table th.sticky-col:nth-child(2),
    table.table td.sticky-col:nth-child(2) {
        left: var(--col-1) !important;
    }

    table.table th.sticky-col:nth-child(3),
    table.table td.sticky-col:nth-child(3) {
        left: var(--col-2) !important;
    }

    table.table th.sticky-col:nth-child(4),
    table.table td.sticky-col:nth-child(4) {
        left: var(--col-3) !important;
    }

    table.table th.sticky-col:nth-child(5),
    table.table td.sticky-col:nth-child(5) {
        left: var(--col-4) !important;
    }

    table.table th.sticky-col:nth-child(6),
    table.table td.sticky-col:nth-child(6) {
        left: var(--col-5) !important;
    }

    table.table th.sticky-col:nth-child(7),
    table.table td.sticky-col:nth-child(7) {
        left: var(--col-6) !important;
    }


    table.table tbody tr:nth-of-type(even) td.sticky-col {
        background-color: #fff !important;
    }


    /* Scrollable container */
    #tableScrollBottom {
        overflow-x: auto;
        overflow-y: hidden;
    }

    /* Truck card styles */
    .truck-card {
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .truck-card:hover {
        transform: translateY(-2px);
    }

    .truck-card.active {
        border: 2px solid #007bff;
    }

    .truck-card .card-body div>div {
        line-height: 1.8;
    }

    /* Table header styles */
    thead th {
        font-weight: normal !important;
    }

    /* Select dropdown styles */
    select.auto-width {
        width: auto !important;
        min-width: 80px;
        font-size: 0.85rem;
    }

    /* Express icon animation */
    .express-icon {
        font-size: 1.3em;
        font-weight: bold;
    }


    .bg-success {
        background-color: #28a745 !important;
    }

    .bg-warning {
        background-color: #fbc658 !important;
    }

    .bg-secondary {
        background-color: #6c757d !important;
    }

    /* Button styles in table */
    table .btn {
        padding: 0.25rem 0.5rem;
        font-size: 0.875rem;
    }

    table .btn i {
        font-size: 0.875rem;
    }

    /* Fixed-width select column */
    .truck-number-select {
        width: 15ch;
        max-width: 15ch;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    /* Truncate selected value */
    .truck-number-select option {
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    /* Smooth transition for sidebar toggle */
    #tableSection,
    #truckDetailsSection {
        transition: all 0.3s ease;
    }

    /* Two-row record styling */
    /* Remove bottom border from row 1 to visually connect with row 2 */
    table.table tbody tr.order-row-1 td {
        border-bottom: none !important;
    }

    /* Add thicker bottom border on row 2 to separate records */
    table.table tbody tr.order-row-2 td {
        border-bottom: 2px solid #adb5bd !important;
    }

    /* Different background for row 2 */
    table.table tbody tr.order-row-2 td {
        background-color: #f8f9fa !important;
    }

    /* Row 1 background */
    table.table tbody tr.order-row-1 td {
        background-color: #fff !important;
    }
</style>
