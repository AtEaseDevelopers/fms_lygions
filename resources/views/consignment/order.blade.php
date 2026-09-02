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

        $consignors = Customer::ofType('Consignor')
            ->pluck('name')
            ->merge(DraftCustomer::where('type', 'Consignor')->where('migrated', false)->pluck('name'))
            ->unique()
            ->sort()
            ->values()
            ->toArray();

        $consignees = Customer::ofType('Consignee')
            ->pluck('name')
            ->merge(DraftCustomer::where('type', 'Consignee')->where('migrated', false)->pluck('name'))
            ->unique()
            ->sort()
            ->values()
            ->toArray();

        $truckGroups = Truck::where('is_outsider', false)->select('chassis_type')->distinct()->pluck('chassis_type')->filter()->values()->toArray();

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
    <style>
        /* Searchable inline dropdowns (Select2) sized to match the small table inputs */
        .inline-edit-row .select2-container {
            width: 100% !important;
            min-width: 110px;
        }
        .inline-edit-row .select2-container--default .select2-selection--single {
            height: calc(1.5em + 0.5rem + 2px);
            padding: 0.2rem 0.4rem;
            font-size: 0.75rem;
            border: 1px solid #ced4da;
            border-radius: 0.2rem;
        }
        .inline-edit-row .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 1.5;
            padding-left: 0;
            color: #000;
        }
        .inline-edit-row .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 100%;
        }
        /* Dropdown menu is attached to <body>; keep it readable and above modals */
        .select2-container--open { z-index: 2000; }
        .select2-dropdown { font-size: 0.8rem; }
        .select2-search__field { font-size: 0.8rem; }
    </style>
    <div class="row">
        <!-- Main Table Section -->
        <div class="col-md-12" id="tableSection">
            <div class="card">

                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center flex-wrap mb-3">
                        <form class="d-flex gap-3 align-items-stretch" id="filterForm" style="width: 100%;">
                            <input type="hidden" name="per_page" value="{{ request('per_page', 10) }}">
                            <div class="input-group">
                                <input type="text" name="filter_daterange" class="form-control" id="filter_daterange"
                                    value="{{ request('filter_daterange') }}" placeholder="Filter Date Range"
                                    autocomplete="off">
                                <span class="input-group-text"><i class="nc-icon nc-calendar-60"></i></span>
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
                                <button type="button" class="btn btn-danger d-none" style="margin: 0;" id="clearBtn">
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
                                    Bulk Save
                                </button>
                                <select id="bulkStatusSelect" class="form-select d-none" style="width: auto; border-radius: 0.2rem;">
                                    <option value="" disabled selected>Status</option>
                                    <option value="Pending">Pending</option>
                                    <option value="Planning">Planning</option>
                                    <option value="Completed">Completed</option>
                                </select>
                                <button type="button" id="bulkStatusBtn" class="btn btn-info d-none"
                                    style="border-radius: 0.2rem; display: inline-flex; align-items: center;">
                                    <i class="bi bi-arrow-repeat me-1" style="font-size: 20px;"></i>
                                    Update Status
                                </button>
                                <button type="button" class="btn btn-success" id="addInlineRowBtn"
                                    style="border-radius: 0.2rem; display: inline-flex; align-items: center;">
                                    <i class="bi bi-plus-circle me-1" style="font-size: 20px;"></i>
                                    Add Row
                                </button>
                                <button type="button" class="btn btn-warning" id="editAllBtn"
                                    style="border-radius: 0.2rem; display: inline-flex; align-items: center;">
                                    <i class="bi bi-pencil me-1" style="font-size: 20px;"></i>
                                    Edit All
                                </button>
                                <button type="button" class="btn btn-danger d-none" id="cancelAllBtn"
                                    style="border-radius: 0.2rem; display: inline-flex; align-items: center;">
                                    <i class="bi bi-x-circle me-1" style="font-size: 20px;"></i>
                                    Cancel All
                                </button>
                                <button type="button" class="btn btn-success d-none" id="saveAllBtn"
                                    style="border-radius: 0.2rem; display: inline-flex; align-items: center;">
                                    <i class="bi bi-save me-1" style="font-size: 20px;"></i>
                                    Save All
                                </button>
                                <button type="button" class="btn btn-outline-secondary" id="toggleTruckDetails"
                                    style="border-radius: 0.2rem; display: inline-flex; align-items: center;">
                                    <i class="bi bi-layout-sidebar-reverse me-1" style="font-size: 20px;"></i>
                                    Truck Details
                                </button>
                                <a href="{{ route('consignment-order.export', request()->query()) }}">
                                    <button type="button" class="btn btn-success"
                                        style="border-radius: 0.2rem; display: inline-flex; align-items: center;"
                                        title="Export filtered records to Excel">
                                        <i class="bi bi-file-excel-fill me-1" style="font-size: 20px;"></i>
                                        Export Excel
                                    </button>
                                </a>
                                <a href="{{ route('archived-consignment-order.index') }}">
                                    <button type="button" class="btn btn-outline-warning"
                                        style="border-radius: 0.2rem; display: inline-flex; align-items: center;">
                                        <i class="bi bi-archive me-1" style="font-size: 20px;"></i>
                                        View Archived Truck Planning
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
                                        <input type="hidden" name="filter_daterange" value="{{ request('filter_daterange') }}">
                                        <input type="hidden" name="search" value="{{ request('search') }}">
                                        <input type="hidden" name="status" value="{{ request('status') }}">
                                        <input type="hidden" name="truck_type" value="{{ request('truck_type') }}">
                                        <input type="hidden" name="truck_number" value="{{ request('truck_number') }}">
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
                    <div class="d-flex justify-content-end align-items-center gap-2 mb-2">
                        <small class="text-muted">Tip: click a column's <i class="bi bi-funnel-fill"></i> to filter, or drag its right edge to resize.</small>
                        <button type="button" id="clearColFiltersBtn" class="btn btn-sm btn-outline-secondary"
                            style="border-radius: 0.2rem;" title="Clear all column filters">
                            <i class="bi bi-funnel me-1"></i> Clear filters
                        </button>
                        <button type="button" id="resetColWidthsBtn" class="btn btn-sm btn-outline-secondary"
                            style="border-radius: 0.2rem;" title="Reset all column widths to their default">
                            <i class="bi bi-arrows-angle-contract me-1"></i> Reset column widths
                        </button>
                    </div>
                    <div id="tableScrollTop" style="overflow-x:auto; overflow-y:hidden;"></div>
                    <div id="tableScrollBottom" style="overflow-x:auto;">
                        <table id="consignmentTable" class="table table-sm table-striped table-bordered align-middle table-nowrap"
                            style="font-size: 0.75rem; border-collapse: collapse;">
                            <thead class="">
                                <tr>
                                    <th class="sticky-col" rowspan="2">
                                        <input type="checkbox" id="selectAllCheckbox">
                                    </th>
                                    <th class="sticky-col" rowspan="2">No</th>
                                    <th class="sticky-col" rowspan="2">
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
                                    <th class="sticky-col" rowspan="2">
                                        <a class="text-dark text-decoration-none"
                                            href="{{ route('consignment-order.index', array_merge(request()->query(), ['sort_by' => 'consignor', 'sort_order' => request('sort_order') === 'asc' && request('sort_by') === 'consignor' ? 'desc' : 'asc'])) }}">
                                            Consignor
                                            @if (request('sort_by') == 'consignor')
                                                <i
                                                    class="bi bi-caret-{{ request('sort_order') == 'asc' ? 'up' : 'down' }}-fill"></i>
                                            @endif
                                        </a>
                                    </th>
                                    <th class="sticky-col" rowspan="2">Pick Point</th>
                                    <th class="sticky-col" rowspan="2">
                                        <a class="text-dark text-decoration-none"
                                            href="{{ route('consignment-order.index', array_merge(request()->query(), ['sort_by' => 'consignee', 'sort_order' => request('sort_order') === 'asc' && request('sort_by') === 'consignee' ? 'desc' : 'asc'])) }}">
                                            Consignee
                                            @if (request('sort_by') == 'consignee')
                                                <i
                                                    class="bi bi-caret-{{ request('sort_order') == 'asc' ? 'up' : 'down' }}-fill"></i>
                                            @endif
                                        </a>
                                    </th>
                                    <th class="sticky-col" rowspan="2">Drop Point</th>
                                    <th colspan="2"><span class="d-inline-block" style="white-space: normal;">Truck Size</span></th>
                                    <th colspan="2"><span class="d-inline-block" style="white-space: normal;">Truck Type</span></th>
                                    <th rowspan="2"><span class="d-inline-block" style="white-space: normal;">
                                            Pick Up Time </span></th>
                                    <th rowspan="2"><span class="d-inline-block" style="white-space: normal;">
                                            Qty </span></th>
                                    <th rowspan="2">Unit</th>
                                    <th rowspan="2"><span class="d-inline-block" style="white-space: normal;">
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
                                    <th rowspan="2">
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
                                    <th rowspan="2">
                                        <a class="text-dark text-decoration-none"
                                            href="{{ route('consignment-order.index', array_merge(request()->query(), ['sort_by' => 'remarks', 'sort_order' => request('sort_order') === 'asc' && request('sort_by') === 'remarks' ? 'desc' : 'asc'])) }}">
                                            Remarks
                                            @if (request('sort_by') == 'remarks')
                                                <i
                                                    class="bi bi-caret-{{ request('sort_order') == 'asc' ? 'up' : 'down' }}-fill"></i>
                                            @endif
                                        </a>
                                    </th>
                                    <th rowspan="2">Billing Remarks</th>
                                    <th rowspan="2">
                                        <a class="text-dark text-decoration-none"
                                            href="{{ route('consignment-order.index', array_merge(request()->query(), ['sort_by' => 'status', 'sort_order' => request('sort_order') === 'asc' && request('sort_by') === 'status' ? 'desc' : 'asc'])) }}">
                                            Status
                                            @if (request('sort_by') == 'status')
                                                <i
                                                    class="bi bi-caret-{{ request('sort_order') == 'asc' ? 'up' : 'down' }}-fill"></i>
                                            @endif
                                        </a>
                                    </th>
                                    <th style="text-align: center" rowspan="2">Express</th>
                                    <th rowspan="2"></th>
                                </tr>
                                <tr>
                                    <th>Pick</th>
                                    <th>Drop</th>
                                    <th>Pick</th>
                                    <th>Drop</th>
                                </tr>
                            </thead>

                            <tbody>
                                @forelse ($consignments as $index => $order)
                                    @php
                                        $qtyArr = is_string($order['quantity']) ? json_decode($order['quantity'], true) : (is_array($order['quantity']) ? $order['quantity'] : []);
                                        $unitArr = is_string($order['unit']) ? json_decode($order['unit'], true) : (is_array($order['unit']) ? $order['unit'] : []);
                                    @endphp
                                    <tr class="order-row" data-id="{{ $order->id }}"
                                        data-load-date="{{ $order['load_date'] ?? '' }}"
                                        data-consignor="{{ $order['consignor'] ?? '' }}"
                                        data-pick-point="{{ $order['pick_point'] ?? '' }}"
                                        data-consignee="{{ $order['consignee'] ?? '' }}"
                                        data-drop-point="{{ $order['drop_point'] ?? '' }}"
                                        data-pick-address="{{ $order['pick_address'] ?? '' }}"
                                        data-drop-address="{{ $order['drop_address'] ?? '' }}"
                                        data-pick-truck-size="{{ $order['pick_truck_size'] ?? '' }}"
                                        data-drop-truck-size="{{ $order['drop_truck_size'] ?? '' }}"
                                        data-pick-type="{{ $order['pick_truck_type'] ?? '' }}"
                                        data-pick-size="{{ $order['pick_truck_size'] ?? '' }}"
                                        data-drop-type="{{ $order['drop_truck_type'] ?? '' }}"
                                        data-drop-size="{{ $order['drop_truck_size'] ?? '' }}"
                                        data-pick-truck-type="{{ $order['pick_truck_type'] ?? '' }}"
                                        data-drop-truck-type="{{ $order['drop_truck_type'] ?? '' }}"
                                        data-pick-time="{{ $order['pick_time'] ?? '' }}"
                                        data-quantity="{{ json_encode($qtyArr) }}"
                                        data-unit="{{ json_encode($unitArr) }}"
                                        data-pre-pick="{{ $order['pre_pick'] ?? '' }}"
                                        data-selected-truck="{{ $order['truck_number'] ?? '' }}"
                                        data-truck-number="{{ $order['truck_number'] ?? '' }}"
                                        data-remarks="{{ $order['remarks'] ?? '' }}"
                                        data-billing-remark="{{ $order['billing_remark'] ?? '' }}"
                                        data-status="{{ $order['status'] ?? '' }}"
                                        data-express-mode="{{ $order['express_mode'] ? '1' : '0' }}">
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
                                            @if (!empty($order['remarks']))
                                                <i class="bi bi-chat-left-text-fill text-primary ms-2 remark-icon"
                                                    data-bs-toggle="tooltip" data-bs-placement="right"
                                                    title="Remarks: {{ $order['remarks'] }}"></i>
                                            @endif
                                            @if (!empty($order['billing_remark']))
                                                <i class="bi bi-receipt text-success ms-2 remark-icon"
                                                    data-bs-toggle="tooltip" data-bs-placement="right"
                                                    title="Billing Remarks: {{ $order['billing_remark'] }}"></i>
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
                                            <div style="display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:3;line-clamp:3;overflow:hidden;">
                                                {!! nl2br(e(wordwrap($consignorDisplay, 10, "\n", true))) !!}
                                            </div>
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
                                            <div style="display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:3;line-clamp:3;overflow:hidden;">
                                                {!! nl2br(e(wordwrap($consigneeDisplay, 10, "\n", true))) !!}
                                            </div>
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
                                                $orderUnits = is_string($order['unit'])
                                                    ? json_decode($order['unit'], true)
                                                    : (is_array($order['unit'])
                                                        ? $order['unit']
                                                        : []);
                                            @endphp

                                            @forelse ($orderUnits as $unit)
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
                                            <div class="d-flex align-items-center gap-1">
                                                <select class="form-select form-select-sm truck-number-select" disabled>
                                                    <option value=""></option>
                                                </select>
                                                @if(in_array($order->id, $affectedIds ?? []))
                                                    <i class="bi bi-exclamation-triangle-fill text-warning express-affected-icon"
                                                       data-bs-toggle="tooltip"
                                                       title="Truck unassigned by an express swap. See dashboard alerts."></i>
                                                @endif
                                            </div>
                                        </td>

                                        <td>{{ $order['remarks'] ?? '-' }}</td>
                                        <td>{{ $order['billing_remark'] ?? '-' }}</td>

                                        <td
                                            class="
                                            @if ($order['status'] == 'Completed') bg-success text-white
                                            @elseif ($order['status'] == 'Planning') bg-warning text-dark
                                            @else bg-secondary text-white @endif">
                                            {{ $order['status'] }}
                                        </td>
                                        <td>{{ $order['express_mode'] ? 'Yes' : '-' }}</td>
                                        <td>
                                            <div class="d-flex align-items-center justify-content-center gap-2">
                                                <!-- Pick/Drop Details Dropdown -->
                                                <div class="dropdown">
                                                    <button type="button" class="btn btn-outline-secondary btn-sm detail-dropdown-toggle" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false" title="View Pick/Drop Details">
                                                        <i class="bi bi-eye"></i>
                                                    </button>
                                                    <div class="dropdown-menu p-3" style="min-width: 480px; max-width: 560px; font-size: 0.85rem; white-space: normal;">
                                                        <div style="display: flex; gap: 1rem;">
                                                            <!-- Left column: Pick Details -->
                                                            <div style="flex: 1; min-width: 0;">
                                                                <h6 class="dropdown-header px-0 pt-0">Pick Details</h6>
                                                                <div class="mb-1" style="word-wrap: break-word;"><strong>Point:</strong> {{ $order['pick_point'] ?? '-' }}</div>
                                                                <div class="mb-1" style="word-wrap: break-word;"><strong>Address:</strong> {{ $order['pick_address'] ?? '-' }}</div>
                                                                <div class="mb-1" style="word-wrap: break-word;"><strong>Truck Size:</strong> {{ $order['pick_truck_size'] ?? '-' }}</div>
                                                                <div style="word-wrap: break-word;"><strong>Truck Type:</strong> {{ $order['pick_truck_type'] ?? '-' }}</div>
                                                            </div>
                                                            <!-- Right column: Drop Details -->
                                                            <div style="flex: 1; min-width: 0;">
                                                                <h6 class="dropdown-header px-0 pt-0">Drop Details</h6>
                                                                <div class="mb-1" style="word-wrap: break-word;"><strong>Point:</strong> {{ $order['drop_point'] ?? '-' }}</div>
                                                                <div class="mb-1" style="word-wrap: break-word;"><strong>Address:</strong> {{ $order['drop_address'] ?? '-' }}</div>
                                                                <div class="mb-1" style="word-wrap: break-word;"><strong>Truck Size:</strong> {{ $order['drop_truck_size'] ?? '-' }}</div>
                                                                <div style="word-wrap: break-word;"><strong>Truck Type:</strong> {{ $order['drop_truck_type'] ?? '-' }}</div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <!-- Edit Button -->
                                                <button type="button" class="btn btn-info edit-inline-btn">
                                                    <i class="bi bi-pencil-square"></i>
                                                </button>

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
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="21" class="text-center">No consignment orders found.</td>
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
                        <input type="date" class="form-control" id="truckDateFilter" value="{{ request('truck_date', now()->format('Y-m-d')) }}" autocomplete="off">
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
            @php
                $displayedTrucks = $trucks_no;
                $totalUnused = $displayedTrucks->sum('remaining');
                $totalCapacity = $displayedTrucks->sum('floor_space');
                $overallUtilization = $totalCapacity > 0 ? (($totalCapacity - $totalUnused) / $totalCapacity) * 100 : 0;
                $totalColor = $overallUtilization >= 90 ? 'danger' : ($overallUtilization >= 70 ? 'warning' : 'success');
            @endphp
            <div class="card mb-3 p-3 bg-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <strong>Capacity:</strong>
                        <strong class="text-{{ $totalColor }}">{{ number_format(max(0, $totalUnused), 2) }}</strong> /
                        {{ number_format($totalCapacity, 0) }}
                    </div>
                </div>
            </div>
            <div id="truckCardsContainer">
                @foreach ($trucks_no as $truck)
                    @php
                        $color =
                            $truck->utilization >= 100 ? 'danger' : ($truck->utilization >= 90 ? 'warning' : 'success');
                    @endphp

                    <div class="card mb-3 p-3 truck-card" data-truck="{{ $truck->number }}">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="d-flex flex-column">
                                <h5 class="mb-2 fw-bold">{{ $truck->number }}</h5>
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
                                <option value="MY">MY Team</option>
                                <option value="SG">SG Team</option>
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
<script>
    document.addEventListener('DOMContentLoaded', function() {
        console.log('DOMContentLoaded START');

        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function(tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });

        // Fix detail dropdowns clipped by overflow container — move menu to body on open
        document.addEventListener('shown.bs.dropdown', function(e) {
            if (!e.target.classList.contains('detail-dropdown-toggle')) return;
            var menu = e.target.nextElementSibling;
            if (!menu) return;
            var rect = e.target.getBoundingClientRect();
            menu.style.position = 'fixed';
            menu.style.top = (rect.bottom + 2) + 'px';
            menu.style.left = (rect.right - menu.offsetWidth) + 'px';
            menu.style.zIndex = '1080';
            document.body.appendChild(menu);
            menu._originalParent = e.target.parentElement;
        });
        document.addEventListener('hidden.bs.dropdown', function(e) {
            if (!e.target.classList.contains('detail-dropdown-toggle')) return;
            var menu = e.target.nextElementSibling || document.querySelector('.dropdown-menu[style*="position: fixed"]');
            if (!menu) {
                // Menu was moved to body, find it there
                var menus = document.body.querySelectorAll(':scope > .dropdown-menu');
                menus.forEach(function(m) {
                    if (m._originalParent) {
                        m.style.position = '';
                        m.style.top = '';
                        m.style.left = '';
                        m.style.zIndex = '';
                        m._originalParent.appendChild(m);
                        delete m._originalParent;
                    }
                });
                return;
            }
            if (menu._originalParent) {
                menu.style.position = '';
                menu.style.top = '';
                menu.style.left = '';
                menu.style.zIndex = '';
                menu._originalParent.appendChild(menu);
                delete menu._originalParent;
            }
        });

        // Toggle Truck Details Sidebar
        const toggleBtn = document.getElementById('toggleTruckDetails');
        const tableSection = document.getElementById('tableSection');
        const truckDetailsSection = document.getElementById('truckDetailsSection');

        if (toggleBtn && tableSection && truckDetailsSection) {
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
        }

        const checkboxes = document.querySelectorAll('.order-row input[type="checkbox"]');
        const saveBtn = document.getElementById('bulkSaveBtn');
        const bulkStatusSelect = document.getElementById('bulkStatusSelect');
        const bulkStatusBtn = document.getElementById('bulkStatusBtn');

        function toggleSaveButton() {
            const anyChecked = Array.from(checkboxes).some(cb => cb.checked);
            saveBtn.classList.toggle('d-none', !anyChecked);
            bulkStatusSelect.classList.toggle('d-none', !anyChecked);
            bulkStatusBtn.classList.toggle('d-none', !anyChecked);
            if (!anyChecked) bulkStatusSelect.selectedIndex = 0;
        }

        const selectAllCheckbox = document.getElementById('selectAllCheckbox');

        selectAllCheckbox.addEventListener('change', function() {
            checkboxes.forEach(cb => cb.checked = this.checked);
            toggleSaveButton();
        });

        checkboxes.forEach(cb => cb.addEventListener('change', function() {
            const allChecked = Array.from(checkboxes).every(cb => cb.checked);
            const anyChecked = Array.from(checkboxes).some(cb => cb.checked);
            selectAllCheckbox.checked = allChecked;
            selectAllCheckbox.indeterminate = anyChecked && !allChecked;
            toggleSaveButton();
        }));

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

        bulkStatusBtn.addEventListener('click', function() {
            const status = bulkStatusSelect.value;
            if (!status) {
                Swal.fire('Error', 'Please select a status first.', 'error');
                return;
            }

            const selectedIds = Array.from(checkboxes)
                .filter(cb => cb.checked)
                .map(cb => cb.closest('tr').dataset.id);

            if (selectedIds.length === 0) {
                Swal.fire('Error', 'No rows selected.', 'error');
                return;
            }

            Swal.fire({
                title: 'Are you sure?',
                text: `This will update ${selectedIds.length} order(s) to "${status}".`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, update status!'
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch("{{ route('consignment-order.bulk-status-update') }}", {
                            method: "POST",
                            headers: {
                                "X-CSRF-TOKEN": "{{ csrf_token() }}",
                                "Content-Type": "application/json",
                                "Accept": "application/json"
                            },
                            body: JSON.stringify({ ids: selectedIds, status: status })
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
                                    location.reload();
                                });
                            } else {
                                Swal.fire('Error', data.message || 'Something went wrong', 'error');
                            }
                        })
                        .catch(err => Swal.fire('Error', 'Failed to update status', 'error'));
                }
            });
        });

        // Get truck data
        const ALL_TRUCKS = @json($trucks);
        const ALL_SUBCONS = @json($subcons);
        const truckCacheByDate = {};

        // Today's date in the user's local timezone as YYYY-MM-DD (for date input min/value).
        function localTodayStr() {
            const d = new Date();
            const pad = n => String(n).padStart(2, '0');
            return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
        }

        function invalidateTruckCache(date) {
            if (date) {
                delete truckCacheByDate[date];
            } else {
                Object.keys(truckCacheByDate).forEach(k => delete truckCacheByDate[k]);
            }
        }

        function normalize(val) {
            return (val || '').toString().trim().toLowerCase();
        }

        // Strip legacy " (Temp)" / " (Subcon)" suffix from a saved truck_number so it
        // matches the canonical option value (just the label/truck_no).
        function stripTruckSuffix(value) {
            return (value || '').toString().replace(/\s*\((?:Temp|Subcon)\)\s*$/i, '');
        }

        // Identical matching rule to the inline-edit dropdown (populateInlineTruckSelect
        // → isValid), so the always-on listing dropdown offers EXACTLY the same trucks:
        // a truck must satisfy BOTH the pick and drop criteria; a criterion that isn't
        // set (or is "any") doesn't exclude anything, so with none set all trucks show.
        function isValidTruck(truck, pickType, pickSize, dropType, dropSize) {
            const tType = normalize(truck.chassis_type);
            const tSize = normalize(truck.size); // may be empty

            let matchPick = true;
            let matchDrop = true;

            if (pickType && pickType !== 'any') {
                matchPick = tType === pickType;
            }
            if (matchPick && pickSize && pickSize !== 'any' && tSize) {
                matchPick = tSize === pickSize;
            }
            if (dropType && dropType !== 'any') {
                matchDrop = tType === dropType;
            }
            if (matchDrop && dropSize && dropSize !== 'any' && tSize) {
                matchDrop = tSize === dropSize;
            }

            return matchPick && matchDrop;
        }

        function populateRowSelect(row, trucks, subcons, tempTrucks) {
            const pickType = normalize(row.dataset.pickType);
            const pickSize = normalize(row.dataset.pickSize);
            const dropType = normalize(row.dataset.dropType);
            const dropSize = normalize(row.dataset.dropSize);
            const selectedTruck = stripTruckSuffix(row.dataset.selectedTruck);

            const select = row.querySelector('.truck-number-select');
            if (!select) return;

            // Keep the dropdown LIVE at all times (reassign inline, no "Edit All").
            select.innerHTML = '<option value="">-</option>';
            select.disabled = false;

            // Add matching main trucks + subcons — same filter as the inline-edit
            // dropdown, and NOT gated on having a truck type (so a row without a type
            // still lists every available truck, exactly like inline edit).
            (trucks || [])
                .filter(t => isValidTruck(t, pickType, pickSize, dropType, dropSize))
                .forEach(t => {
                    const opt = document.createElement('option');
                    opt.value = t.number;
                    opt.textContent = t.number;
                    if (t.number === selectedTruck) opt.selected = true;
                    select.appendChild(opt);
                });

            (subcons || [])
                .filter(s => isValidTruck(s, pickType, pickSize, dropType, dropSize))
                .forEach(s => {
                    const opt = document.createElement('option');
                    opt.value = s.truck_no;
                    opt.textContent = s.truck_no + ' (Subcon)';
                    if (s.truck_no === selectedTruck) opt.selected = true;
                    select.appendChild(opt);
                });

            // Add ALL temporary subcon trucks for this date — even when criteria are missing.
            // Once a subcon is assigned, show the real truck no with " (Subcon)"; otherwise " (Temp)".
            // Option value stays as the temp's label so the consignment's truck_number is stable.
            (tempTrucks || []).forEach(t => {
                const opt = document.createElement('option');
                opt.value = t.truck_no;
                if (t.subcon_id && t.subcon_truck_no) {
                    opt.textContent = t.subcon_truck_no + ' (Subcon)';
                } else {
                    opt.textContent = t.truck_no + ' (Temp)';
                }
                if (t.truck_no === selectedTruck) opt.selected = true;
                select.appendChild(opt);
            });

            // Ensure currently assigned truck is always in the list
            if (selectedTruck && !select.querySelector(`option[value="${selectedTruck}"]`)) {
                const opt = document.createElement('option');
                opt.value = selectedTruck;
                opt.textContent = selectedTruck;
                opt.selected = true;
                select.insertBefore(opt, select.options[1] || null);
            }
        }

        // Fetch available trucks by date and populate row selects
        function fetchAndPopulateRows() {
            const rows = document.querySelectorAll('.order-row');
            const dateRows = {};

            rows.forEach(row => {
                const loadDate = row.dataset.loadDate || '';
                if (!dateRows[loadDate]) dateRows[loadDate] = [];
                dateRows[loadDate].push(row);
            });

            Object.keys(dateRows).forEach(date => {
                if (!date) {
                    dateRows[date].forEach(row => populateRowSelect(row, ALL_TRUCKS, ALL_SUBCONS, []));
                    return;
                }

                if (truckCacheByDate[date]) {
                    const cached = truckCacheByDate[date];
                    dateRows[date].forEach(row => populateRowSelect(row, cached.trucks, cached.subcons, cached.temp_trucks || []));
                    return;
                }

                fetch(`/api/available-trucks?date=${date}`)
                    .then(r => r.json())
                    .then(data => {
                        truckCacheByDate[date] = data;
                        dateRows[date].forEach(row => populateRowSelect(row, data.trucks, data.subcons, data.temp_trucks || []));
                    })
                    .catch(() => {
                        dateRows[date].forEach(row => populateRowSelect(row, ALL_TRUCKS, ALL_SUBCONS, []));
                    });
            });
        }

        fetchAndPopulateRows();

        // Inline truck reassignment: each row's truck dropdown is live at all times
        // (no "Edit All"). Changing it saves immediately with no page reload, so the
        // active filters and sorting are never reset.
        // jQuery delegation (not native addEventListener): Select2 fires its change
        // through jQuery, which a vanilla listener would miss.
        jQuery(document).on('change', '.truck-number-select', function() {
            const sel = this;
            const row = sel.closest('.order-row');
            if (!row || !row.dataset.id) return;
            // While "Edit All" is open, let that flow own the save instead.
            if (typeof editAllMode !== 'undefined' && editAllMode) return;

            const id = row.dataset.id;
            const truckNumber = sel.value;
            fetch(`{{ url('consignment-order') }}/${id}/truck-number`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ truck_number: truckNumber }),
            }).then(async res => {
                const body = await res.json().catch(() => ({}));
                if (!res.ok) throw new Error(body.message || 'Could not update truck.');
                row.dataset.selectedTruck = body.truck_number || '';
                Swal.fire({ toast: true, position: 'top-end', icon: 'success',
                    title: body.message || 'Truck updated', timer: 1400, showConfirmButton: false });
            }).catch(err => {
                Swal.fire({ toast: true, position: 'top-end', icon: 'error',
                    title: err.message, timer: 2500, showConfirmButton: false });
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

        // Auto-slide the table horizontally when a cell/input is focused (e.g. via Tab),
        // so the focused column is never hidden behind the sticky left columns or off-screen.
        (function setupFocusAutoSlide() {
            const scrollContainer = document.getElementById('tableScrollBottom');
            if (!scrollContainer) return;

            // Total width of the fixed (sticky) left columns — they cover the left edge,
            // so a scrollable cell must land to the right of this to be fully visible.
            function getStickyWidth() {
                const stickyCells = scrollContainer.querySelectorAll('thead tr:first-child th.sticky-col');
                let w = 0;
                stickyCells.forEach(c => w += c.getBoundingClientRect().width);
                return w;
            }

            // Tell the browser's OWN focus/scroll-into-view to keep clear of the sticky columns,
            // so its native scrolling (which ignores sticky cells) doesn't overshoot to the end.
            function refreshScrollPadding() {
                scrollContainer.style.scrollPaddingLeft = getStickyWidth() + 'px';
            }
            refreshScrollPadding();
            window.addEventListener('resize', refreshScrollPadding);

            function ensureCellVisible(el) {
                if (!el) return;
                const cell = el.closest('td, th');
                // Sticky columns are always visible — no need to slide for them.
                if (!cell || cell.classList.contains('sticky-col')) return;

                const margin = 12; // small breathing room
                const cRect = scrollContainer.getBoundingClientRect();
                const cellRect = cell.getBoundingClientRect();
                const visibleLeft = cRect.left + getStickyWidth(); // right edge of the fixed columns
                const visibleRight = cRect.right;

                let delta = 0;
                if (cellRect.left < visibleLeft + margin) {
                    // Hidden behind the fixed columns / off the left — reveal just past the fixed edge.
                    delta = cellRect.left - (visibleLeft + margin);
                } else if (cellRect.right > visibleRight - margin) {
                    // Off the right edge — reveal just inside the right edge.
                    delta = cellRect.right - (visibleRight - margin);
                }

                if (delta !== 0) {
                    // NOTE: assign scrollLeft directly (instant), NOT scrollTo({behavior:'smooth'}).
                    // The shared layout syncs the top & bottom scrollbars bidirectionally on every
                    // 'scroll' event; a smooth animation fires those events each frame and the
                    // write-back cancels the animation mid-way, so it never reaches the target.
                    const max = scrollContainer.scrollWidth - scrollContainer.clientWidth;
                    scrollContainer.scrollLeft = Math.max(0, Math.min(max, scrollContainer.scrollLeft + delta));
                }
            }

            // All focusable fields inside the table, in DOM (= visual left-to-right) order.
            function getFocusables() {
                return Array.prototype.filter.call(
                    scrollContainer.querySelectorAll('input:not([type=hidden]), select, textarea, button, a[href], [tabindex]:not([tabindex="-1"])'),
                    function(el) {
                        return !el.disabled && el.tabIndex !== -1 && el.offsetParent !== null;
                    }
                );
            }

            // Take full control of Tab: move focus WITHOUT the browser's native scroll
            // (preventScroll), which with sticky columns is unreliable and overshoots,
            // then slide the table ourselves. No race with the browser.
            scrollContainer.addEventListener('keydown', function(e) {
                if (e.key !== 'Tab') return;

                const focusables = getFocusables();
                const idx = focusables.indexOf(document.activeElement);
                if (idx === -1) return; // focus isn't on a table field — let the browser handle it

                const nextIdx = e.shiftKey ? idx - 1 : idx + 1;
                if (nextIdx < 0 || nextIdx >= focusables.length) return; // leaving the table — let the browser handle it

                e.preventDefault();
                const next = focusables[nextIdx];
                next.focus({ preventScroll: true });
                ensureCellVisible(next);
            });

            // Fallback for focus not driven by Tab (clicks, programmatic focus).
            scrollContainer.addEventListener('focusin', function(e) {
                requestAnimationFrame(() => ensureCellVisible(e.target));
            });
        })();

        // Floating horizontal scrollbar: when many rows push the table's own
        // scrollbar below the fold, mirror it in a bar pinned to the viewport
        // bottom so planners can always scroll sideways.
        (function setupFloatingScrollbar() {
            const container = document.getElementById('tableScrollBottom');
            if (!container) return;
            const table = document.getElementById('consignmentTable');

            const bar = document.createElement('div');
            bar.id = 'floatingHScroll';
            const inner = document.createElement('div');
            bar.appendChild(inner);
            document.body.appendChild(bar);

            let syncing = false;
            bar.addEventListener('scroll', function() {
                if (syncing) return;
                syncing = true;
                container.scrollLeft = bar.scrollLeft;
                syncing = false;
            });
            container.addEventListener('scroll', function() {
                if (syncing) return;
                syncing = true;
                bar.scrollLeft = container.scrollLeft;
                syncing = false;
            });

            function update() {
                const overflow = container.scrollWidth - container.clientWidth > 1;
                const rect = container.getBoundingClientRect();
                // The real scrollbar is hidden when the container bottom sits
                // below the viewport bottom.
                const belowFold = rect.bottom > window.innerHeight + 1;
                if (overflow && belowFold && rect.width > 0) {
                    bar.style.display = 'block';
                    bar.style.left = rect.left + 'px';
                    bar.style.width = rect.width + 'px';
                    inner.style.width = container.scrollWidth + 'px';
                    bar.scrollLeft = container.scrollLeft;
                } else {
                    bar.style.display = 'none';
                }
            }

            window.addEventListener('scroll', update, { passive: true });
            window.addEventListener('resize', update);
            if (table) {
                new MutationObserver(function() {
                    setTimeout(update, 50);
                }).observe(table, { childList: true, subtree: true });
            }
            window.updateFloatingScrollbar = update;
            setTimeout(update, 200);
        })();

        const filterForm = $('#filterForm');
        const daterangeInput = $('#filter_daterange');

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

        // When date range is applied, update the input and submit
        daterangeInput.on('apply.daterangepicker', function(ev, picker) {
            const selectedRange = picker.startDate.format('YYYY-MM-DD') + ' to ' + picker.endDate
                .format('YYYY-MM-DD');
            $(this).val(selectedRange);
            updateClearButton();
            filterForm.submit();
        });

        // When date range is cleared
        daterangeInput.on('cancel.daterangepicker', function(ev, picker) {
            $(this).val('');
            updateClearButton();
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

            // Redirect to base URL and clear the saved filters/sort for this user
            // (reset=1), otherwise the page would just re-apply them.
            window.location.href = "{{ route('consignment-order.index') }}?reset=1";
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

        // Truck date filter — reload page with query param
        const truckDateFilter = document.getElementById('truckDateFilter');
        if (truckDateFilter) {
            truckDateFilter.addEventListener('change', function() {
                const url = new URL(window.location.href);
                url.searchParams.set('truck_date', this.value);
                window.location.href = url.toString();
            });
        }

        // Add/Remove quantity-unit rows
        document.addEventListener("click", function(e) {
            // Add new row (modal)
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

                container.appendChild(clone);

                // Show all remove buttons when there are multiple rows
                container.querySelectorAll(".removeRow").forEach(btn => {
                    btn.style.display = "";
                });
            }

            // Remove row (modal)
            const removeBtn = e.target.closest(".removeRow");
            if (removeBtn) {
                const row = removeBtn.closest(".quantity-unit-row");
                const container = row.closest(".quantityUnitContainer");
                const rows = container.querySelectorAll(".quantity-unit-row");
                if (rows.length > 1) {
                    row.remove();
                    // Hide remove button if only one row left
                    const remaining = container.querySelectorAll(".quantity-unit-row");
                    if (remaining.length === 1) {
                        remaining[0].querySelector(".removeRow").style.display = "none";
                    }
                }
            }

        });

        // Add/Remove inline quantity-unit rows
        document.addEventListener("click", function(e) {
            const addBtn = e.target.closest(".addInlineQtyRow");
            if (addBtn) {
                const container = addBtn.closest(".inline-qty-unit-container");
                const row = addBtn.closest(".inline-qty-unit-row");
                const clone = row.cloneNode(true);
                clone.querySelectorAll("input").forEach(input => input.value = "");
                // Drop any Select2 markup copied by cloneNode so we can re-init a clean widget.
                clone.querySelectorAll(".select2-container").forEach(el => el.remove());
                clone.querySelectorAll("[data-select2-id]").forEach(el => el.removeAttribute("data-select2-id"));
                clone.querySelectorAll("select").forEach(select => {
                    select.classList.remove("select2-hidden-accessible");
                    select.selectedIndex = 0;
                });
                container.appendChild(clone);
                container.querySelectorAll(".removeInlineQtyRow").forEach(btn => btn.style.display = "");
                clone.querySelectorAll("select").forEach(select => initSearchableSelect(select));
            }

            const removeBtn = e.target.closest(".removeInlineQtyRow");
            if (removeBtn) {
                const row = removeBtn.closest(".inline-qty-unit-row");
                const container = row.closest(".inline-qty-unit-container");
                const rows = container.querySelectorAll(".inline-qty-unit-row");
                if (rows.length > 1) {
                    row.remove();
                    const remaining = container.querySelectorAll(".inline-qty-unit-row");
                    if (remaining.length === 1) {
                        remaining[0].querySelector(".removeInlineQtyRow").style.display = "none";
                    }
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

        // Escape values for safe use in template literal HTML attributes
        function escapeAttr(str) {
            return (str || '').replace(/&/g, '&amp;').replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
                .replace(/`/g, '&#96;').replace(/\$/g, '&#36;');
        }

        // Required fields for an inline truck planning row.
        const REQUIRED_INLINE_FIELDS = ['load_date', 'consignor', 'consignee', 'pick_point', 'drop_point'];

        // Remove any red-box error highlighting from a row's inputs.
        function clearRowFieldErrors(row) {
            row.querySelectorAll('.field-error').forEach(el => el.classList.remove('field-error'));
        }

        // Highlight empty required fields in an inline row with a red box.
        // Returns the list of invalid field names (empty = valid).
        function validateInlineRow(row) {
            clearRowFieldErrors(row);
            const invalid = [];
            REQUIRED_INLINE_FIELDS.forEach(name => {
                const input = row.querySelector(`[name="${name}"]`);
                if (input && !input.value.trim()) {
                    input.classList.add('field-error');
                    invalid.push(name);
                }
            });
            return invalid;
        }

        // Flag a row that failed to save on the server: red tint + a warning
        // icon in the NEW/EDIT column whose tooltip shows why it failed.
        function markRowSaveError(row, message) {
            row.classList.add('row-save-error');
            const labelCell = row.querySelectorAll('td')[1]; // NEW / EDIT column
            if (!labelCell) return;
            let icon = labelCell.querySelector('.row-error-icon');
            if (!icon) {
                icon = document.createElement('i');
                icon.className = 'bi bi-exclamation-triangle-fill text-danger row-error-icon ms-1';
                labelCell.appendChild(icon);
            }
            icon.title = message || 'Failed to save';
        }

        // Remove any previous save-error flag from a row.
        function clearRowSaveError(row) {
            row.classList.remove('row-save-error');
            row.querySelectorAll('.row-error-icon').forEach(el => el.remove());
        }

        // Pull a human-readable reason out of a save response (duplicate
        // message, first validation error, or a generic fallback).
        function extractSaveError(data) {
            if (!data) return 'Server error — please try again.';
            if (data.message) return data.message;
            if (data.errors) {
                const first = Object.values(data.errors)[0];
                if (Array.isArray(first) && first.length) return first[0];
            }
            return 'Failed to save.';
        }

        let editAllMode = false;
        const editAllBtn = document.getElementById('editAllBtn');
        const cancelAllBtn = document.getElementById('cancelAllBtn');
        const saveAllBtn = document.getElementById('saveAllBtn');

        // Show the bulk Save/Cancel controls (used for edit-all and bulk-add)
        function showBulkEditControls() {
            editAllBtn.classList.add('d-none');
            cancelAllBtn.classList.remove('d-none');
            saveAllBtn.classList.remove('d-none');
        }

        // Reset the toolbar back to its idle state
        function resetBulkEditControls() {
            editAllMode = false;
            editAllBtn.classList.remove('d-none');
            cancelAllBtn.classList.add('d-none');
            saveAllBtn.classList.add('d-none');
            addInlineRowBtn.disabled = false;
        }

        function convertRowToEdit(row) {
            const id = row.dataset.id;

            const loadDate = row.dataset.loadDate || '';
            const consignor = row.dataset.consignor || '';
            const pickPoint = row.dataset.pickPoint || '';
            const consignee = row.dataset.consignee || '';
            const dropPoint = row.dataset.dropPoint || '';
            const pickAddress = row.dataset.pickAddress || '';
            const dropAddress = row.dataset.dropAddress || '';
            const pickTruckSize = row.dataset.pickTruckSize || '';
            const dropTruckSize = row.dataset.dropTruckSize || '';
            const pickTruckType = row.dataset.pickTruckType || '';
            const dropTruckType = row.dataset.dropTruckType || '';
            const pickTime = row.dataset.pickTime || '';
            const prePick = row.dataset.prePick || '';
            const truckNumber = row.dataset.truckNumber || '';
            const remarks = row.dataset.remarks || '';
            const billingRemark = row.dataset.billingRemark || '';
            const status = row.dataset.status || '';
            const expressMode = row.dataset.expressMode || '0';

            let quantities = [];
            let unitValues = [];
            try { quantities = JSON.parse(row.dataset.quantity || '[]'); } catch(err) { quantities = []; }
            try { unitValues = JSON.parse(row.dataset.unit || '[]'); } catch(err) { unitValues = []; }

            const originalHTML = row.innerHTML;
            const originalClassName = row.className;
            const originalDataset = { ...row.dataset };

            // Build unit options helper for a given selected value
            function buildUnitsOptions(selectedUnit) {
                let opts = '<option value="">-</option>';
                if (UNITS && Array.isArray(UNITS)) {
                    opts += UNITS.map(u => {
                        const unitUpper = (u.unit || '').toUpperCase();
                        const unitDesc = u.desc || '';
                        const sel = (selectedUnit || '') === u.unit ? ' selected' : '';
                        return `<option value="${u.unit}"${sel}>${unitUpper} — ${unitDesc}</option>`;
                    }).join('');
                }
                return opts;
            }

            // Build quantity/unit rows HTML for inline edit
            const pairCount = Math.max(quantities.length, unitValues.length, 1);
            let qtyUnitRows = '';
            for (let i = 0; i < pairCount; i++) {
                qtyUnitRows += `
                    <div class="d-flex align-items-center gap-1 mb-1 inline-qty-unit-row">
                        <input type="number" class="form-control form-control-sm" name="quantity[]" placeholder="Qty" value="${quantities[i] || ''}" style="width:60px">
                        <select class="form-select form-select-sm" name="unit[]" style="width:120px">
                            ${buildUnitsOptions(unitValues[i] || '')}
                        </select>
                        <button type="button" class="btn btn-success btn-sm addInlineQtyRow"><i class="bi bi-plus"></i></button>
                        <button type="button" class="btn btn-danger btn-sm removeInlineQtyRow" ${pairCount <= 1 ? 'style="display:none;"' : ''}><i class="bi bi-dash"></i></button>
                    </div>`;
            }

            let truckGroupsOptions = '<option value="">-</option><option value="any"' + (pickTruckType === 'any' ? ' selected' : '') + '>Any</option>';
            if (TRUCK_GROUPS && Array.isArray(TRUCK_GROUPS)) {
                truckGroupsOptions += TRUCK_GROUPS.map(g => `<option value="${g}"${g === pickTruckType ? ' selected' : ''}>${g}</option>`).join('');
            }

            let dropTruckGroupsOptions = '<option value="">-</option><option value="any"' + (dropTruckType === 'any' ? ' selected' : '') + '>Any</option>';
            if (TRUCK_GROUPS && Array.isArray(TRUCK_GROUPS)) {
                dropTruckGroupsOptions += TRUCK_GROUPS.map(g => `<option value="${g}"${g === dropTruckType ? ' selected' : ''}>${g}</option>`).join('');
            }

            let consignorOptions = '';
            if (CONSIGNORS && Array.isArray(CONSIGNORS)) {
                consignorOptions = CONSIGNORS.map(c => `<option value="${escapeAttr(c)}"></option>`).join('');
            }

            let consigneeOptions = '';
            if (CONSIGNEES && Array.isArray(CONSIGNEES)) {
                consigneeOptions = CONSIGNEES.map(c => `<option value="${escapeAttr(c)}"></option>`).join('');
            }

            let truckOptions = '<option value="">-</option>';
            // Will be populated via AJAX after row is rendered

            function sizeOptions(selected) {
                return `<option value=""${!selected ? ' selected' : ''}>-</option>
                        <option value="Small"${selected === 'Small' ? ' selected' : ''}>Small</option>
                        <option value="Any"${selected === 'Any' ? ' selected' : ''}>Any</option>
                        <option value="Warehouse Truck"${selected === 'Warehouse Truck' ? ' selected' : ''}>Warehouse Truck</option>`;
            }

            row.className = 'inline-edit-row table-warning';
            row.dataset.editId = id;
            row._originalHTML = originalHTML;
            row._originalClassName = originalClassName;
            row._originalDataset = originalDataset;

            row.innerHTML = `
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
                <td class="sticky-col">EDIT</td>
                <td class="sticky-col">
                    <div class="d-flex align-items-center gap-1">
                        <input type="date" class="form-control form-control-sm" name="load_date" value="${escapeAttr(loadDate)}" required>
                        <span class="text-danger">*</span>
                    </div>
                </td>
                <td class="sticky-col">
                    <div class="d-flex align-items-center gap-1">
                        <input name="consignor" list="edit_consignor_list_${id}" class="form-control form-control-sm"
                               placeholder="Consignor" value="${escapeAttr(consignor)}" required>
                        <span class="text-danger">*</span>
                    </div>
                    <datalist id="edit_consignor_list_${id}">
                        ${consignorOptions}
                    </datalist>
                </td>
                <td class="sticky-col">
                    <div class="d-flex align-items-center gap-1">
                        <input type="text" class="form-control form-control-sm" name="pick_point" placeholder="Pick Point" value="${escapeAttr(pickPoint)}" required>
                        <span class="text-danger">*</span>
                    </div>
                    <input type="text" class="form-control form-control-sm mt-1" name="pick_address"
                           list="pick_address_list_${id}" placeholder="Pick Address" value="${escapeAttr(pickAddress)}">
                    <datalist id="pick_address_list_${id}"></datalist>
                </td>
                <td class="sticky-col">
                    <div class="d-flex align-items-center gap-1">
                        <input name="consignee" list="edit_consignee_list_${id}" class="form-control form-control-sm"
                               placeholder="Consignee" value="${escapeAttr(consignee)}" required>
                        <span class="text-danger">*</span>
                    </div>
                    <datalist id="edit_consignee_list_${id}">
                        ${consigneeOptions}
                    </datalist>
                </td>
                <td class="sticky-col">
                    <div class="d-flex align-items-center gap-1">
                        <input type="text" class="form-control form-control-sm" name="drop_point" placeholder="Drop Point" value="${escapeAttr(dropPoint)}" required>
                        <span class="text-danger">*</span>
                    </div>
                    <input type="text" class="form-control form-control-sm mt-1" name="drop_address"
                           list="drop_address_list_${id}" placeholder="Drop Address" value="${escapeAttr(dropAddress)}">
                    <datalist id="drop_address_list_${id}"></datalist>
                </td>
                <td>
                    <select class="form-select form-select-sm" name="pick_truck_size">
                        ${sizeOptions(pickTruckSize)}
                    </select>
                </td>
                <td>
                    <select class="form-select form-select-sm" name="drop_truck_size">
                        ${sizeOptions(dropTruckSize)}
                    </select>
                </td>
                <td>
                    <select class="form-select form-select-sm" name="pick_truck_type">
                        ${truckGroupsOptions}
                    </select>
                </td>
                <td>
                    <select class="form-select form-select-sm" name="drop_truck_type">
                        ${dropTruckGroupsOptions}
                    </select>
                </td>
                <td>
                    <input type="time" class="form-control form-control-sm" name="pick_time" value="${escapeAttr(pickTime)}">
                </td>
                <td colspan="2">
                    <div class="inline-qty-unit-container">
                        ${qtyUnitRows}
                    </div>
                </td>
                <td>
                    <select class="form-select form-select-sm" name="pre_pick">
                        <option value="">-</option>
                        <option value="SELF"${prePick === 'SELF' ? ' selected' : ''}>SELF</option>
                        <option value="WVS 5404"${prePick === 'WVS 5404' ? ' selected' : ''}>WVS 5404</option>
                        <option value="NCR 8825"${prePick === 'NCR 8825' ? ' selected' : ''}>NCR 8825</option>
                        <option value="BSG 8826"${prePick === 'BSG 8826' ? ' selected' : ''}>BSG 8826</option>
                    </select>
                </td>
                <td>
                    <select class="form-select form-select-sm" name="truck_number">
                        ${truckOptions}
                    </select>
                </td>
                <td>
                    <input type="text" class="form-control form-control-sm" name="remarks" placeholder="Remarks" value="${escapeAttr(remarks)}">
                </td>
                <td>
                    <input type="text" class="form-control form-control-sm" name="billing_remark" placeholder="Billing Remarks" value="${escapeAttr(billingRemark)}">
                </td>
                <td>
                    <select class="form-select form-select-sm" name="status">
                        <option value="Pending"${status === 'Pending' ? ' selected' : ''}>Pending</option>
                        <option value="Planning"${status === 'Planning' ? ' selected' : ''}>Planning</option>
                        <option value="Completed"${status === 'Completed' ? ' selected' : ''}>Completed</option>
                    </select>
                </td>
                <td>
                    <select class="form-select form-select-sm" name="express_mode">
                        <option value="0"${expressMode === '1' ? '' : ' selected'}>No</option>
                        <option value="1"${expressMode === '1' ? ' selected' : ''}>Yes</option>
                    </select>
                </td>
                <td></td>
            `;

            // Populate truck select based on load_date capacity
            populateInlineTruckSelect(row, loadDate, truckNumber);

            // Re-populate truck select when load_date changes in inline edit
            const loadDateInput = row.querySelector('[name="load_date"]');
            if (loadDateInput) {
                let lastLoadDate = loadDateInput.value || '';
                loadDateInput.addEventListener('change', function() {
                    const currentTruck = row.querySelector('[name="truck_number"]')?.value || '';
                    invalidateTruckCache(lastLoadDate);
                    invalidateTruckCache(this.value);
                    lastLoadDate = this.value || '';
                    populateInlineTruckSelect(row, this.value, currentTruck);
                });
            }

            // Re-run filter when pick/drop size or type changes
            ['pick_truck_size', 'drop_truck_size', 'pick_truck_type', 'drop_truck_type']
                .forEach(name => {
                    const el = row.querySelector(`[name="${name}"]`);
                    if (el) {
                        el.addEventListener('change', function() {
                            const currentDate = row.querySelector('[name="load_date"]')?.value || '';
                            const currentTruck = row.querySelector('[name="truck_number"]')?.value || '';
                            populateInlineTruckSelect(row, currentDate, currentTruck);
                        });
                    }
                });

            // Pre-populate pick/drop address datalists with the customer's saved locations,
            // without overwriting the row's existing values
            if (consignor) fetchAndFillLocations(row, consignor, 'consignor', { overwriteValue: false });
            if (consignee) fetchAndFillLocations(row, consignee, 'consignee', { overwriteValue: false });

            // Make all dropdowns in this edit row searchable.
            makeRowSelectsSearchable(row);

            return row;
        }

        // Turn every <select> in an inline edit row into a searchable Select2 dropdown.
        function makeRowSelectsSearchable(row) {
            if (!window.jQuery || !jQuery.fn.select2) return;
            jQuery(row).find('select').each(function() {
                initSearchableSelect(this);
            });
        }

        // Initialise (or re-initialise) Select2 on a single <select>. Safe to call
        // repeatedly and on cloned selects — it strips any stale Select2 markup first.
        function initSearchableSelect(el) {
            if (!window.jQuery || !jQuery.fn.select2) return;
            const $el = jQuery(el);
            if ($el.hasClass('select2-hidden-accessible')) {
                try { $el.select2('destroy'); } catch (err) {}
                $el.removeClass('select2-hidden-accessible')
                   .removeAttr('data-select2-id aria-hidden tabindex');
                $el.siblings('.select2-container').remove();
            }
            $el.select2({
                width: '100%',
                dropdownParent: jQuery('body'),
                placeholder: '-',
                allowClear: false
            });

            // Unit dropdown shortcut: typing "p" then Enter/Tab picks P4,
            // "f" picks FT. Full search still works (e.g. "pallet", "p4").
            if ((el.name || '').indexOf('unit') === 0) {
                bindUnitShortcut($el, el);
            }
        }

        // Map a single typed letter to a default unit code.
        const UNIT_SHORTCUTS = { p: 'p4', f: 'ft' };

        // When a unit Select2 is open, let "p"/"f" + Enter/Tab default to
        // P4/FT so planners can key units fast without scrolling.
        function bindUnitShortcut($el, el) {
            $el.on('select2:open', function() {
                const search = document.querySelector(
                    '.select2-container--open .select2-search__field');
                if (!search || search.dataset.unitShortcutBound) return;
                search.dataset.unitShortcutBound = '1';
                // Capture phase + stopImmediatePropagation so this runs BEFORE Select2's
                // own Enter handler (which would otherwise pick the first filtered result).
                search.addEventListener('keydown', function(e) {
                    if (e.key !== 'Enter' && e.key !== 'Tab') return;
                    const q = (search.value || '').trim().toLowerCase();
                    const target = UNIT_SHORTCUTS[q];
                    if (!target) return; // let normal search/select happen
                    const opt = Array.from(el.options).find(o =>
                        (o.value || '').trim().toLowerCase() === target);
                    if (!opt) return;
                    e.preventDefault();
                    e.stopImmediatePropagation();
                    $el.val(opt.value).trigger('change');
                    $el.select2('close');
                }, true);
            });
        }

        function populateInlineTruckSelect(row, date, selectedTruck) {
            const select = row.querySelector('[name="truck_number"]');
            if (!select) return;

            selectedTruck = stripTruckSuffix(selectedTruck);

            const normalize = v => (v || '').toString().trim().toLowerCase();
            const pickType = normalize(row.querySelector('[name="pick_truck_type"]')?.value);
            const pickSize = normalize(row.querySelector('[name="pick_truck_size"]')?.value);
            const dropType = normalize(row.querySelector('[name="drop_truck_type"]')?.value);
            const dropSize = normalize(row.querySelector('[name="drop_truck_size"]')?.value);

            function isValid(t) {
                const tType = normalize(t.chassis_type);
                const tSize = normalize(t.size);

                let matchPick = true;
                let matchDrop = true;

                if (pickType && pickType !== 'any') {
                    matchPick = tType === pickType;
                }
                if (matchPick && pickSize && pickSize !== 'any' && tSize) {
                    matchPick = tSize === pickSize;
                }

                if (dropType && dropType !== 'any') {
                    matchDrop = tType === dropType;
                }
                if (matchDrop && dropSize && dropSize !== 'any' && tSize) {
                    matchDrop = tSize === dropSize;
                }

                return matchPick && matchDrop;
            }

            function fillOptions(trucks, subcons, tempTrucks) {
                select.innerHTML = '<option value="">-</option>';
                if (trucks && Array.isArray(trucks)) {
                    trucks.filter(isValid).forEach(t => {
                        const opt = document.createElement('option');
                        opt.value = t.number;
                        opt.textContent = t.number;
                        if (t.number === selectedTruck) opt.selected = true;
                        select.appendChild(opt);
                    });
                }
                if (subcons && Array.isArray(subcons)) {
                    subcons.filter(isValid).forEach(s => {
                        const opt = document.createElement('option');
                        opt.value = s.truck_no;
                        opt.textContent = s.truck_no + ' (Subcon)';
                        if (s.truck_no === selectedTruck) opt.selected = true;
                        select.appendChild(opt);
                    });
                }
                // ALL temp subcons for the date (no type/size filter), label switches once a real subcon is bound
                if (tempTrucks && Array.isArray(tempTrucks)) {
                    tempTrucks.forEach(t => {
                        const opt = document.createElement('option');
                        opt.value = t.truck_no;
                        opt.textContent = (t.subcon_id && t.subcon_truck_no)
                            ? t.subcon_truck_no + ' (Subcon)'
                            : t.truck_no + ' (Temp)';
                        if (t.truck_no === selectedTruck) opt.selected = true;
                        select.appendChild(opt);
                    });
                }
                // Ensure currently assigned truck stays in list even if it no longer matches
                // (run last so it doesn't duplicate a temp-truck label that's already been appended).
                if (selectedTruck && !select.querySelector(`option[value="${selectedTruck}"]`)) {
                    const opt = document.createElement('option');
                    opt.value = selectedTruck;
                    opt.textContent = selectedTruck;
                    opt.selected = true;
                    select.insertBefore(opt, select.options[1] || null);
                }

                // Make the freshly-rebuilt truck dropdown searchable.
                initSearchableSelect(select);
            }

            if (!date) {
                fillOptions(ALL_TRUCKS, ALL_SUBCONS, []);
                return;
            }

            if (truckCacheByDate[date]) {
                const cached = truckCacheByDate[date];
                fillOptions(cached.trucks, cached.subcons, cached.temp_trucks || []);
                return;
            }

            fetch(`/api/available-trucks?date=${date}`)
                .then(r => r.json())
                .then(data => {
                    truckCacheByDate[date] = data;
                    fillOptions(data.trucks, data.subcons, data.temp_trucks || []);
                })
                .catch(() => fillOptions(ALL_TRUCKS, ALL_SUBCONS, []));
        }

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
            // Allow adding multiple new rows at once, but not while existing rows are
            // being edited (single-row edit or edit-all mode).
            if (editAllMode || document.querySelector('.inline-edit-row[data-edit-id]')) {
                Swal.fire('Warning', 'Please save or cancel the row you are editing first', 'warning');
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
            let truckGroupsOptions = '<option value="">-</option><option value="any">Any</option>';
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

            // Truck options will be populated via AJAX after row is rendered
            let truckOptions = '<option value="">-</option>';

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
            <div class="d-flex align-items-center gap-1">
                <input type="date" class="form-control form-control-sm" name="load_date" required>
                <span class="text-danger">*</span>
            </div>
        </td>
        <td class="sticky-col">
            <div class="d-flex align-items-center gap-1">
                <input name="consignor" list="inline_consignor_list" class="form-control form-control-sm"
                       placeholder="Consignor" required>
                <span class="text-danger">*</span>
            </div>
            <datalist id="inline_consignor_list">
                ${consignorOptions}
            </datalist>
        </td>
        <td class="sticky-col">
            <div class="d-flex align-items-center gap-1">
                <input type="text" class="form-control form-control-sm" name="pick_point" placeholder="Pick Point" required>
                <span class="text-danger">*</span>
            </div>
            <input type="text" class="form-control form-control-sm mt-1" name="pick_address"
                   list="inline_pick_address_list" placeholder="Pick Address">
            <datalist id="inline_pick_address_list"></datalist>
        </td>
        <td class="sticky-col">
            <div class="d-flex align-items-center gap-1">
                <input name="consignee" list="inline_consignee_list" class="form-control form-control-sm"
                       placeholder="Consignee" required>
                <span class="text-danger">*</span>
            </div>
            <datalist id="inline_consignee_list">
                ${consigneeOptions}
            </datalist>
        </td>
        <td class="sticky-col">
            <div class="d-flex align-items-center gap-1">
                <input type="text" class="form-control form-control-sm" name="drop_point" placeholder="Drop Point" required>
                <span class="text-danger">*</span>
            </div>
            <input type="text" class="form-control form-control-sm mt-1" name="drop_address"
                   list="inline_drop_address_list" placeholder="Drop Address">
            <datalist id="inline_drop_address_list"></datalist>
        </td>
        <td>
            <select class="form-select form-select-sm" name="pick_truck_size">
                <option value="">-</option>
                <option value="Small">Small</option>
                <option value="Any">Any</option>
                <option value="Warehouse Truck">Warehouse Truck</option>
            </select>
        </td>
        <td>
            <select class="form-select form-select-sm" name="drop_truck_size">
                <option value="">-</option>
                <option value="Small">Small</option>
                <option value="Any">Any</option>
                <option value="Warehouse Truck">Warehouse Truck</option>
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
        <td colspan="2">
            <div class="inline-qty-unit-container">
                <div class="d-flex align-items-center gap-1 mb-1 inline-qty-unit-row">
                    <input type="number" class="form-control form-control-sm" name="quantity[]" placeholder="Qty" style="width:60px">
                    <select class="form-select form-select-sm" name="unit[]" style="width:120px">
                        ${unitsOptions}
                    </select>
                    <button type="button" class="btn btn-success btn-sm addInlineQtyRow"><i class="bi bi-plus"></i></button>
                    <button type="button" class="btn btn-danger btn-sm removeInlineQtyRow" style="display:none;"><i class="bi bi-dash"></i></button>
                </div>
            </div>
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
        <td>
            <select class="form-select form-select-sm" name="express_mode">
                <option value="0" selected>No</option>
                <option value="1">Yes</option>
            </select>
        </td>
        <td></td>
    `;

            // Insert at the top of tbody
            tableBody.insertBefore(newRow, tableBody.firstChild);

            // Add load_date change listener to populate truck options
            const newLoadDateInput = newRow.querySelector('[name="load_date"]');
            if (newLoadDateInput) {
                // Default new rows to today and block picking any past date.
                const today = localTodayStr();
                newLoadDateInput.min = today;
                newLoadDateInput.value = today;

                newLoadDateInput.addEventListener('change', function() {
                    const currentTruck = newRow.querySelector('[name="truck_number"]')?.value || '';
                    populateInlineTruckSelect(newRow, this.value, currentTruck);
                });

                // Load trucks for today's default date immediately.
                populateInlineTruckSelect(newRow, today, '');
            }

            // Re-run filter when pick/drop size or type changes
            ['pick_truck_size', 'drop_truck_size', 'pick_truck_type', 'drop_truck_type']
                .forEach(name => {
                    const el = newRow.querySelector(`[name="${name}"]`);
                    if (el) {
                        el.addEventListener('change', function() {
                            const currentDate = newRow.querySelector('[name="load_date"]')?.value || '';
                            const currentTruck = newRow.querySelector('[name="truck_number"]')?.value || '';
                            populateInlineTruckSelect(newRow, currentDate, currentTruck);
                        });
                    }
                });

            // Make all dropdowns in the new row searchable.
            makeRowSelectsSearchable(newRow);

            // Recalculate sticky column positions
            recalculateStickyColumns();

            // Show bulk Save/Cancel controls; keep Add Row enabled so more rows can be added
            showBulkEditControls();

            // Scroll to top
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });

        // Auto-fill pick/drop point when consignor/consignee is selected in inline edit rows.
        // When overwriteValue is false (e.g. on edit-mode entry), the saved point/address/truck
        // values are preserved — only the datalist of options is populated.
        function fetchAndFillLocations(row, name, type, { overwriteValue = true } = {}) {
            if (!name) return;

            fetch(`/customers/${encodeURIComponent(name)}/locations`)
                .then(res => res.json())
                .then(data => {
                    if (!data.locations) return;

                    const typeFilter = type === 'consignor' ? 'pickup' : 'dropoff';
                    const filtered = data.locations.filter(
                        loc => Array.isArray(loc.types) &&
                            loc.types.some(t => t.toLowerCase() === typeFilter)
                    );

                    const isPick = type === 'consignor';
                    const pointInput = row.querySelector(isPick ? '[name="pick_point"]' : '[name="drop_point"]');
                    const addressInput = row.querySelector(isPick ? '[name="pick_address"]' : '[name="drop_address"]');
                    const truckTypeSelect = row.querySelector(isPick ? '[name="pick_truck_type"]' : '[name="drop_truck_type"]');
                    const truckSizeSelect = row.querySelector(isPick ? '[name="pick_truck_size"]' : '[name="drop_truck_size"]');

                    // Populate the address datalist with every matching saved address
                    if (addressInput) {
                        const listId = addressInput.getAttribute('list');
                        const datalist = listId ? row.querySelector(`#${CSS.escape(listId)}`) : null;
                        if (datalist) {
                            datalist.innerHTML = '';
                            const seen = new Set();
                            filtered.forEach(loc => {
                                const addr = loc.address ?? '';
                                if (!addr || seen.has(addr)) return;
                                seen.add(addr);
                                const opt = document.createElement('option');
                                opt.value = addr;
                                datalist.appendChild(opt);
                            });
                        }
                    }

                    if (!overwriteValue) return;

                    if (filtered.length > 0) {
                        const loc = filtered[0];
                        if (pointInput) pointInput.value = loc.pickup_dropoff_point ?? loc.state ?? '';
                        if (addressInput) addressInput.value = loc.address ?? '';
                        if (truckTypeSelect && loc.truck_type) truckTypeSelect.value = loc.truck_type;
                        if (truckSizeSelect && loc.truck_size) truckSizeSelect.value = loc.truck_size;
                    } else {
                        if (pointInput) pointInput.value = '';
                        if (addressInput) addressInput.value = '';
                    }
                })
                .catch(err => console.error('Error fetching locations:', err));
        }

        tableBody.addEventListener('change', function(e) {
            const row = e.target.closest('.inline-edit-row');
            if (!row) return;

            if (e.target.matches('[name="consignor"]')) {
                fetchAndFillLocations(row, e.target.value, 'consignor');
            } else if (e.target.matches('[name="consignee"]')) {
                // "-" means no consignee/drop-off needed — auto-fill the drop-off
                // fields with "-" instead of looking up saved locations.
                if (e.target.value.trim() === '-') {
                    const dropPointInput = row.querySelector('[name="drop_point"]');
                    const dropAddressInput = row.querySelector('[name="drop_address"]');
                    if (dropPointInput) dropPointInput.value = '-';
                    if (dropAddressInput) dropAddressInput.value = '-';
                } else {
                    fetchAndFillLocations(row, e.target.value, 'consignee');
                }
            }
        });

        // Clear the red-box highlight as soon as the user edits a flagged field
        tableBody.addEventListener('input', function(e) {
            if (e.target.classList && e.target.classList.contains('field-error') && e.target.value.trim()) {
                e.target.classList.remove('field-error');
            }
            // Editing anything in a failed row clears its save-error flag.
            const failedRow = e.target.closest('.inline-edit-row.row-save-error');
            if (failedRow) clearRowSaveError(failedRow);
        });

        // Event delegation for save/cancel buttons
        tableBody.addEventListener('click', function(e) {
            // Save inline row (both add and edit)
            if (e.target.closest('.save-inline-row')) {
                const saveBtn = e.target.closest('.save-inline-row');
                // Guard against double-clicks creating duplicate rows.
                if (saveBtn.disabled) return;
                const row = e.target.closest('.inline-edit-row');
                const formData = new FormData();
                const editId = row.dataset.editId; // present only for edit rows
                const isEdit = !!editId;

                // Collect all input values
                row.querySelectorAll('input, select').forEach(input => {
                    if (input.name) {
                        if (input.type === 'checkbox') {
                            if (input.checked) {
                                formData.append(input.name, input.value);
                            }
                        } else {
                            formData.append(input.name, input.value);
                        }
                    }
                });

                // Validate required fields (highlights empty ones with a red box)
                const invalidFields = validateInlineRow(row);
                if (invalidFields.length) {
                    Swal.fire('Error', 'Please fill in all required fields (highlighted in red)', 'error');
                    return;
                }

                const expressSelect = row.querySelector('[name="express_mode"]');
                const newExpress = expressSelect && expressSelect.value === '1';
                const wasExpress = (row.dataset.expressMode || '0') === '1';
                const expressTurningOn = newExpress && (!isEdit || !wasExpress);

                const url = isEdit
                    ? `/consignment-order/${editId}/update-inline`
                    : "{{ route('consignment-order.store-inline') }}";

                const submitInline = (action) => {
                    formData.append('express_action', action);

                    // Lock the save button so a second click can't create a duplicate.
                    saveBtn.disabled = true;

                    Swal.fire({
                        title: 'Saving...',
                        allowOutsideClick: false,
                        didOpen: () => { Swal.showLoading(); }
                    });

                    fetch(url, {
                            method: "POST",
                            headers: { "X-CSRF-TOKEN": "{{ csrf_token() }}" },
                            body: formData
                        })
                        .then(res => res.json())
                        .then(data => {
                            if (data.success) {
                                Swal.fire({
                                    icon: 'success',
                                    title: isEdit ? 'Updated!' : 'Created!',
                                    text: data.message,
                                    timer: 2000,
                                    showConfirmButton: false
                                }).then(() => {
                                    location.reload();
                                });
                            } else {
                                Swal.fire('Error', data.message || 'Failed to save order', 'error');
                                addInlineRowBtn.disabled = false;
                                saveBtn.disabled = false;
                            }
                        })
                        .catch(err => {
                            console.error(err);
                            Swal.fire('Error', 'Failed to save order', 'error');
                            addInlineRowBtn.disabled = false;
                            saveBtn.disabled = false;
                        });
                };

                if (expressTurningOn) {
                    Swal.fire({
                        title: 'Confirm Express Mode',
                        html: '<b>Yes</b>: swap truck region day-by-day to match the express order.<br><b>No</b>: unassign all other orders on this truck from the load date forward and mark availability unavailable.',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Yes, swap',
                        cancelButtonText: 'No, unassign',
                        confirmButtonColor: '#198754',
                        cancelButtonColor: '#d33',
                    }).then(r => {
                        if (r.isConfirmed) {
                            submitInline('swap');
                        } else if (r.dismiss === 'cancel') {
                            submitInline('unassign');
                        }
                    });
                } else {
                    submitInline('swap');
                }
            }

            // Cancel inline row
            if (e.target.closest('.cancel-inline-row')) {
                const row = e.target.closest('.inline-edit-row');
                const editId = row.dataset.editId;

                Swal.fire({
                    title: 'Discard changes?',
                    text: editId ? 'Changes will be discarded' : 'This new row will be removed',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, discard',
                    cancelButtonText: 'No, keep editing'
                }).then((result) => {
                    if (result.isConfirmed) {
                        if (editId && row._originalHTML) {
                            // Restore original row
                            const restoredRow = document.createElement('tr');
                            restoredRow.className = row._originalClassName;
                            restoredRow.innerHTML = row._originalHTML;
                            // Copy all data attributes
                            for (const key in row._originalDataset) {
                                restoredRow.dataset[key] = row._originalDataset[key];
                            }
                            row.parentNode.replaceChild(restoredRow, row);
                            // Re-populate truck number dropdown for the restored row
                            populateTruckDropdown(restoredRow);
                            // Re-bind delete form
                            restoredRow.querySelectorAll('.delete-form').forEach(form => {
                                form.addEventListener('submit', handleDeleteForm);
                            });
                        } else {
                            row.remove();
                        }
                        // If no more inline rows remain, reset the toolbar controls
                        if (!document.querySelector('.inline-edit-row')) {
                            resetBulkEditControls();
                        }
                        recalculateStickyColumns();
                    }
                });
            }

            // Edit inline button
            if (e.target.closest('.edit-inline-btn')) {
                // Check if there's already an inline edit row (skip check during edit-all mode)
                if (!editAllMode && document.querySelector('.inline-edit-row')) {
                    Swal.fire('Warning', 'Please save or cancel the current row first', 'warning');
                    return;
                }

                const row = e.target.closest('.order-row');
                convertRowToEdit(row);

                // Disable add button while editing
                addInlineRowBtn.disabled = true;

                // Recalculate sticky columns
                recalculateStickyColumns();
            }
        });
        console.log('Button handlers registered');

        // Edit All button handler
        editAllBtn.addEventListener('click', function() {
            if (document.querySelector('.inline-edit-row')) {
                Swal.fire('Warning', 'Please save or cancel the current row first', 'warning');
                return;
            }

            const rows = tableBody.querySelectorAll('.order-row');
            if (rows.length === 0) {
                Swal.fire('Warning', 'No rows to edit', 'warning');
                return;
            }

            editAllMode = true;
            rows.forEach(row => convertRowToEdit(row));

            editAllBtn.classList.add('d-none');
            addInlineRowBtn.disabled = true;
            cancelAllBtn.classList.remove('d-none');
            saveAllBtn.classList.remove('d-none');

            recalculateStickyColumns();
        });

        // Cancel All button handler
        cancelAllBtn.addEventListener('click', function() {
            Swal.fire({
                title: 'Discard all changes?',
                text: 'All edits will be discarded',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, discard all',
                cancelButtonText: 'No, keep editing'
            }).then((result) => {
                if (result.isConfirmed) {
                    const editRows = tableBody.querySelectorAll('.inline-edit-row');
                    editRows.forEach(row => {
                        if (row.dataset.editId && row._originalHTML) {
                            // Existing row being edited — restore its original markup
                            const restoredRow = document.createElement('tr');
                            restoredRow.className = row._originalClassName;
                            restoredRow.innerHTML = row._originalHTML;
                            for (const key in row._originalDataset) {
                                restoredRow.dataset[key] = row._originalDataset[key];
                            }
                            row.parentNode.replaceChild(restoredRow, row);
                            populateTruckDropdown(restoredRow);
                            restoredRow.querySelectorAll('.delete-form').forEach(form => {
                                form.addEventListener('submit', handleDeleteForm);
                            });
                        } else {
                            // New (unsaved) row — just remove it
                            row.remove();
                        }
                    });

                    resetBulkEditControls();

                    recalculateStickyColumns();
                }
            });
        });

        // Save All button handler
        saveAllBtn.addEventListener('click', function() {
            // Guard against double-clicks re-submitting rows that already saved.
            if (saveAllBtn.disabled) return;

            const editRows = tableBody.querySelectorAll('.inline-edit-row');
            if (editRows.length === 0) {
                Swal.fire('Warning', 'No rows to save', 'warning');
                return;
            }

            // Validate all rows first (highlights every empty required field with a red box)
            let firstInvalidRow = null;
            editRows.forEach(row => {
                const invalidFields = validateInlineRow(row);
                if (invalidFields.length && !firstInvalidRow) firstInvalidRow = row;
            });
            if (firstInvalidRow) {
                Swal.fire('Error', 'Please fill in all required fields in every row (highlighted in red)', 'error');
                firstInvalidRow.scrollIntoView({ behavior: 'smooth', block: 'center' });
                return;
            }

            // Lock the button for the whole batch so it can't be fired twice.
            saveAllBtn.disabled = true;

            Swal.fire({
                title: 'Saving all rows...',
                allowOutsideClick: false,
                didOpen: () => { Swal.showLoading(); }
            });

            // Save rows ONE AT A TIME (not Promise.all). Firing them concurrently
            // let two rows grab the same consignment number before either
            // committed. Saving in sequence means each row's number is generated
            // after the previous row is already stored, keeping them unique.
            (async () => {
                const results = [];

                for (const row of editRows) {
                    // Clear any error flag from a previous Save All attempt.
                    clearRowSaveError(row);

                    const editId = row.dataset.editId; // present only for existing rows
                    const isEdit = !!editId;
                    const url = isEdit
                        ? `/consignment-order/${editId}/update-inline`
                        : "{{ route('consignment-order.store-inline') }}";
                    const formData = new FormData();

                    row.querySelectorAll('input, select').forEach(input => {
                        if (input.name) {
                            if (input.type === 'checkbox') {
                                if (input.checked) {
                                    formData.append(input.name, input.value);
                                }
                            } else {
                                formData.append(input.name, input.value);
                            }
                        }
                    });

                    // New rows go through the store endpoint, which expects an express action
                    if (!isEdit) formData.append('express_action', 'swap');

                    try {
                        const res = await fetch(url, {
                            method: "POST",
                            headers: {
                                "X-CSRF-TOKEN": "{{ csrf_token() }}",
                                // Ask Laravel to return validation errors as JSON (422)
                                // instead of a redirect, so we can show the reason.
                                "Accept": "application/json",
                                "X-Requested-With": "XMLHttpRequest"
                            },
                            body: formData
                        });
                        let data = {};
                        try { data = await res.json(); } catch (e) { data = {}; }
                        // A non-2xx (e.g. 422 validation) means the row did not save.
                        const success = res.ok && data.success !== false;
                        results.push({ row, data, success });
                    } catch (err) {
                        console.error(err);
                        results.push({ row, data: {}, success: false });
                    }
                }

                // Remove every row that saved so a later click can't recreate it.
                results.filter(r => r.success).forEach(r => r.row.remove());

                const failed = results.filter(r => !r.success);
                if (failed.length > 0) {
                    // Flag each failing row inline (red tint + reason tooltip) and
                    // scroll to the first one so the user sees what to fix.
                    failed.forEach(r => markRowSaveError(r.row, extractSaveError(r.data)));
                    failed[0].row.scrollIntoView({ behavior: 'smooth', block: 'center' });

                    // Re-enable so the user can retry only the still-failing rows.
                    saveAllBtn.disabled = false;
                    Swal.fire(
                        'Error',
                        `${failed.length} row(s) failed to save. The failed rows are highlighted in red — hover the ⚠ icon to see why.`,
                        'error'
                    );
                } else {
                    Swal.fire({
                        icon: 'success',
                        title: 'All rows saved!',
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => { location.reload(); });
                }
            })();
        });

        // Helper: (re)populate a display row's truck dropdown — e.g. after an edit is
        // cancelled. Delegates to populateRowSelect so the option list and the always-on
        // (enabled) behaviour stay identical to the initial load and the inline edit.
        function populateTruckDropdown(row) {
            const select = row.querySelector('.truck-number-select');
            if (!select) return;
            const loadDate = row.dataset.loadDate || '';

            if (loadDate && truckCacheByDate[loadDate]) {
                const c = truckCacheByDate[loadDate];
                populateRowSelect(row, c.trucks, c.subcons, c.temp_trucks || []);
            } else if (loadDate) {
                fetch(`/api/available-trucks?date=${loadDate}`)
                    .then(r => r.json())
                    .then(data => {
                        truckCacheByDate[loadDate] = data;
                        populateRowSelect(row, data.trucks, data.subcons, data.temp_trucks || []);
                    })
                    .catch(() => populateRowSelect(row, ALL_TRUCKS, ALL_SUBCONS, []));
            } else {
                populateRowSelect(row, ALL_TRUCKS, ALL_SUBCONS, []);
            }
        }

        // Helper: handle delete form submission
        function handleDeleteForm(e) {
            e.preventDefault();
            const form = e.currentTarget;
            const csn = form.querySelector('button[type="submit"]').getAttribute("data-number");

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
        }
    });
</script>

<style>
    /* Red-box highlight for required inline fields left empty on save */
    .field-error,
    .field-error:focus {
        border: 2px solid #dc3545 !important;
        box-shadow: 0 0 0 0.15rem rgba(220, 53, 69, 0.25) !important;
    }

    /* Red tint + left bar for an inline row that failed to save on the server */
    .inline-edit-row.row-save-error > td,
    .inline-edit-row.row-save-error > td.sticky-col {
        background-color: #f8d7da !important;
    }

    .inline-edit-row.row-save-error > td:first-child {
        box-shadow: inset 4px 0 0 0 #dc3545;
    }

    .row-error-icon {
        cursor: help;
        font-size: 0.9rem;
    }

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

    /* Frozen columns must sit ABOVE the scrollable cells' funnel icons (z-index 5)
       and resize handles (z-index 6); otherwise those icons bleed over the frozen
       columns while scrolling sideways. The open filter panel is fixed at z 1060. */
    table.table thead th.sticky-col {
        background: #f8f9fa !important;
        z-index: 7 !important;
    }

    table.table tbody td.sticky-col {
        background: #f8f9fa !important;
        z-index: 8 !important;
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

    /* Floating horizontal scrollbar pinned to the bottom of the viewport.
       Stays visible when the table's own scrollbar is scrolled out of view. */
    #floatingHScroll {
        position: fixed;
        bottom: 0;
        z-index: 1030;
        overflow-x: auto;
        overflow-y: hidden;
        height: 14px;
        display: none;
        background: rgba(255, 255, 255, 0.95);
        border-top: 1px solid #dee2e6;
        box-shadow: 0 -2px 6px rgba(0, 0, 0, 0.08);
        scrollbar-width: thin;
    }

    #floatingHScroll>div {
        height: 1px;
    }

    #floatingHScroll::-webkit-scrollbar {
        height: 12px;
        -webkit-appearance: none;
    }

    #floatingHScroll::-webkit-scrollbar-thumb {
        background: rgba(0, 0, 0, 0.4);
        border-radius: 6px;
    }

    #floatingHScroll::-webkit-scrollbar-track {
        background: rgba(0, 0, 0, 0.06);
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

    /* Remarks / billing remarks indicators */
    .remark-icon {
        font-size: 1.1em;
        cursor: help;
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

    /* Match the inline-edit truck dropdown: a standard form-select-sm that fills
       its cell (no fixed width), just with the closed value truncated tidily. */
    .truck-number-select {
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

    /* --- Adjustable column widths ---------------------------------------
       Only kicks in once the user resizes (or saved widths are restored),
       so the default page render is unchanged. Fixed layout lets columns
       shrink below their content, which then clips with an ellipsis. */
    #consignmentTable.resizable-active {
        table-layout: fixed;
    }

    #consignmentTable.resizable-active th,
    #consignmentTable.resizable-active td {
        overflow: hidden;
        text-overflow: ellipsis;
    }

    /* Drag handle on the right edge of every column header */
    .col-resize-handle {
        position: absolute;
        top: 0;
        bottom: 0;
        right: 0;
        width: 6px;
        cursor: col-resize;
        user-select: none;
        z-index: 6;
    }

    .col-resize-handle:hover,
    .col-resize-handle.resizing {
        background-color: #0d6efd;
    }

    body.col-resizing {
        cursor: col-resize;
        user-select: none;
    }

    /* --- Excel-style per-column value filter ---------------------------- */
    #consignmentTable thead th.has-col-filter {
        padding-right: 1.3rem !important;
    }

    .col-filter-btn {
        position: absolute;
        top: 1px;
        right: 8px;
        z-index: 5;
        border: none;
        background: transparent;
        padding: 0;
        line-height: 1;
        cursor: pointer;
        color: #b0b6bd;
        font-size: 0.72rem;
    }

    .col-filter-btn:hover,
    .col-filter-btn.filter-active {
        color: #0d6efd;
    }

    .col-filter-panel {
        position: fixed;
        z-index: 1060;
        width: 240px;
        background: #fff;
        border: 1px solid #ccc;
        border-radius: 4px;
        box-shadow: 0 6px 20px rgba(0, 0, 0, 0.18);
        padding: 8px;
        font-size: 0.8rem;
    }

    .col-filter-panel .cfp-search {
        margin-bottom: 6px;
    }

    .col-filter-panel .cfp-list {
        max-height: 240px;
        overflow-y: auto;
        border-top: 1px solid #eee;
        border-bottom: 1px solid #eee;
        padding: 4px 2px;
    }

    .col-filter-panel .cfp-item {
        display: flex;
        align-items: center;
        gap: 6px;
        margin: 0;
        padding: 2px;
        font-weight: normal;
        cursor: pointer;
    }

    .col-filter-panel .cfp-item span {
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .col-filter-panel .cfp-actions {
        display: flex;
        justify-content: space-between;
        gap: 6px;
        margin-top: 8px;
    }
</style>

{{-- Adjustable column widths: drag the right edge of any column header to resize.
     Widths are saved in the browser (localStorage) so they persist across visits. --}}
<script>
    document.addEventListener("DOMContentLoaded", function () {
        const table = document.getElementById("consignmentTable");
        if (!table || !table.tHead) return;

        const MIN_WIDTH = 40;
        const storageKey = "consignmentColWidths:v1";

        // Work out the starting column index of each header cell, accounting for
        // the two header rows and their colspan / rowspan.
        function buildColumnModel() {
            const grid = [];
            const cellStartCol = new Map();
            const rows = table.tHead.rows;
            for (let r = 0; r < rows.length; r++) {
                grid[r] = grid[r] || [];
                let c = 0;
                for (const cell of rows[r].cells) {
                    while (grid[r][c]) c++;
                    cellStartCol.set(cell, c);
                    for (let i = 0; i < cell.rowSpan; i++) {
                        const rr = r + i;
                        grid[rr] = grid[rr] || [];
                        for (let j = 0; j < cell.colSpan; j++) grid[rr][c + j] = true;
                    }
                    c += cell.colSpan;
                }
            }
            const totalCols = Math.max(0, ...grid.map((row) => row.length));
            return { cellStartCol, totalCols };
        }

        const { cellStartCol, totalCols } = buildColumnModel();
        if (totalCols === 0) return;

        let colgroup = null;
        let cols = [];

        // Keep the table's total width equal to the sum of the columns so it stays
        // horizontally scrollable under fixed layout (don't rely on max-content).
        function syncTableWidth() {
            if (!cols.length) return;
            const total = cols.reduce((sum, c) => sum + (parseFloat(c.style.width) || 0), 0);
            table.style.width = total + "px";
        }

        // Re-run the app's existing sticky-column offset logic so the fixed left
        // columns stay lined up after a resize.
        function recomputeStickyOffsets() {
            // Use the first *visible* body row — a filtered-out row has zero width.
            const firstBodyRow = Array.from(table.querySelectorAll("tbody tr")).find(
                (r) => r.offsetParent !== null
            );
            const headerRow = table.querySelector("thead tr");
            const cells = firstBodyRow
                ? firstBodyRow.querySelectorAll("td.sticky-col")
                : headerRow.querySelectorAll("th.sticky-col");
            let offset = 0;
            cells.forEach((cell, index) => {
                if (index > 0) {
                    document.documentElement.style.setProperty(`--col-${index}`, offset + "px");
                }
                offset += Math.round(cell.getBoundingClientRect().width);
            });
        }

        // Switch the table into resizable (fixed-layout) mode. Measure the current
        // natural column widths first and lock them into a colgroup so nothing
        // jumps when fixed layout takes over.
        function activate(initialWidths) {
            if (table.classList.contains("resizable-active")) return;

            const measured = new Array(totalCols).fill(null);
            for (const cell of table.tHead.querySelectorAll("th")) {
                if (cell.colSpan === 1) {
                    const idx = cellStartCol.get(cell);
                    if (measured[idx] == null) measured[idx] = cell.getBoundingClientRect().width;
                }
            }

            colgroup = document.createElement("colgroup");
            cols = [];
            for (let i = 0; i < totalCols; i++) {
                const col = document.createElement("col");
                const savedW = initialWidths && initialWidths[i] > 0 ? initialWidths[i] : null;
                const w = savedW || measured[i] || 80;
                col.style.width = Math.max(MIN_WIDTH, w) + "px";
                cols.push(col);
                colgroup.appendChild(col);
            }
            table.insertBefore(colgroup, table.firstChild);
            table.classList.add("resizable-active");
            syncTableWidth();
            recomputeStickyOffsets();
        }

        function saveWidths() {
            if (!cols.length) return;
            const widths = cols.map((c) => parseFloat(c.style.width) || 0);
            localStorage.setItem(storageKey, JSON.stringify(widths));
        }

        function loadWidths() {
            try {
                const raw = localStorage.getItem(storageKey);
                if (!raw) return null;
                const parsed = JSON.parse(raw);
                return Array.isArray(parsed) && parsed.length === totalCols ? parsed : null;
            } catch (e) {
                return null;
            }
        }

        // Restore saved widths on load.
        const savedWidths = loadWidths();
        if (savedWidths) activate(savedWidths);

        // Add one drag handle per column, on the lowest single-column header cell.
        const handleForCol = {};
        for (const cell of table.tHead.querySelectorAll("th")) {
            if (cell.colSpan === 1) handleForCol[cellStartCol.get(cell)] = cell; // later rows win
        }

        let active = null; // { colIndex, startX, startWidth, handle }

        Object.entries(handleForCol).forEach(([colIndex, cell]) => {
            const idx = parseInt(colIndex, 10);
            const handle = document.createElement("div");
            handle.className = "col-resize-handle";
            handle.title = "Drag to resize column";
            handle.addEventListener("mousedown", function (e) {
                e.preventDefault();
                e.stopPropagation();
                activate(null); // no-op if already active; builds the colgroup on first use
                active = {
                    colIndex: idx,
                    startX: e.clientX,
                    startWidth: cols[idx].getBoundingClientRect().width,
                    handle: handle,
                };
                handle.classList.add("resizing");
                document.body.classList.add("col-resizing");
            });
            // Don't let a click on the handle trigger the column's sort link.
            handle.addEventListener("click", (e) => e.stopPropagation());
            cell.appendChild(handle);
        });

        document.addEventListener("mousemove", function (e) {
            if (!active) return;
            const newWidth = Math.max(MIN_WIDTH, active.startWidth + (e.clientX - active.startX));
            cols[active.colIndex].style.width = newWidth + "px";
            syncTableWidth();
            recomputeStickyOffsets();
        });

        document.addEventListener("mouseup", function () {
            if (!active) return;
            active.handle.classList.remove("resizing");
            document.body.classList.remove("col-resizing");
            active = null;
            saveWidths();
        });

        const resetBtn = document.getElementById("resetColWidthsBtn");
        if (resetBtn) {
            resetBtn.addEventListener("click", function () {
                localStorage.removeItem(storageKey);
                if (colgroup) {
                    colgroup.remove();
                    colgroup = null;
                    cols = [];
                }
                table.style.width = "";
                table.classList.remove("resizable-active");
                // Recompute sticky offsets against the restored natural widths.
                requestAnimationFrame(recomputeStickyOffsets);
            });
        }

        // ================= Excel-style per-column value filters =================
        // Each listed column gets a funnel button. Clicking it opens a checklist of
        // the distinct values currently in that column; unticking a value hides the
        // rows that have it. Filters across columns combine (AND). Client-side only,
        // so it filters the rows on the current page.
        // Read the row's clean data-* attribute (the real value) rather than the
        // cell text, which can be word-wrapped / decorated with icons.
        const ds = (attr) => (r) => (r.dataset[attr] || "").trim() || "-";
        const FILTER_COLS = [
            { i: 2, label: "Pick Up Date", get: ds("loadDate") },
            { i: 3, label: "Consignor", get: ds("consignor") },
            { i: 4, label: "Pick Point", get: ds("pickPoint") },
            { i: 5, label: "Consignee", get: ds("consignee") },
            { i: 6, label: "Drop Point", get: ds("dropPoint") },
            { i: 7, label: "Pick Size", get: ds("pickTruckSize") },
            { i: 8, label: "Drop Size", get: ds("dropTruckSize") },
            { i: 9, label: "Pick Type", get: ds("pickTruckType") },
            { i: 10, label: "Drop Type", get: ds("dropTruckType") },
            { i: 11, label: "Pick Up Time" }, // formatted in the cell — use cell text
            { i: 14, label: "Pre-Pick", get: ds("prePick") },
            { i: 15, label: "Truck Number", get: ds("truckNumber") },
            { i: 16, label: "Remarks", get: ds("remarks") },
            { i: 17, label: "Billing Remarks", get: ds("billingRemark") },
            { i: 18, label: "Status", get: ds("status") },
            { i: 19, label: "Express", get: (r) => (r.dataset.expressMode === "1" ? "Yes" : "-") },
        ];
        const CFG_BY_INDEX = {};
        FILTER_COLS.forEach((c) => (CFG_BY_INDEX[c.i] = c));

        // One header cell per column (same rule as the resize handles).
        const thForCol = {};
        for (const cell of table.tHead.querySelectorAll("th")) {
            if (cell.colSpan === 1) thForCol[cellStartCol.get(cell)] = cell;
        }

        function orderRows() {
            return Array.from(table.querySelectorAll("tbody tr.order-row"));
        }

        function cellValue(row, cfg) {
            if (cfg.get) return cfg.get(row);
            const td = row.children[cfg.i];
            return td ? (td.textContent || "").replace(/\s+/g, " ").trim() : "";
        }

        const activeFilters = {}; // colIndex -> Set of allowed values
        let openPanel = null;

        function closePanel() {
            if (openPanel) {
                openPanel.remove();
                openPanel = null;
            }
        }

        function applyFilters() {
            const activeIdx = Object.keys(activeFilters).map(Number);
            orderRows().forEach((row) => {
                let show = true;
                for (const idx of activeIdx) {
                    if (!activeFilters[idx].has(cellValue(row, CFG_BY_INDEX[idx]))) {
                        show = false;
                        break;
                    }
                }
                row.style.display = show ? "" : "none";
            });
        }

        function markFunnel(cfg) {
            const th = thForCol[cfg.i];
            const btn = th && th.querySelector(".col-filter-btn");
            if (btn) btn.classList.toggle("filter-active", !!activeFilters[cfg.i]);
        }

        // --- Persist per-column filters (per user; survives refresh/nav/logout) ---
        const SAVED_COL_FILTERS = @json($planningColumnFilters ?: (object) []);
        let savePrefsTimer = null;
        function saveColumnFilters() {
            const payload = {};
            Object.keys(activeFilters).forEach((k) => {
                payload[k] = Array.from(activeFilters[k]);
            });
            clearTimeout(savePrefsTimer);
            savePrefsTimer = setTimeout(() => {
                fetch("{{ route('consignment-order.save-prefs') }}", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": "{{ csrf_token() }}",
                        "Accept": "application/json",
                    },
                    body: JSON.stringify({ columnFilters: payload }),
                }).catch(() => {});
            }, 400);
        }

        // Re-apply the user's saved column filters on load. Called AFTER the funnel
        // buttons are created so the active-filter highlight lands on them.
        function restoreColumnFilters() {
            if (!SAVED_COL_FILTERS || typeof SAVED_COL_FILTERS !== "object") return;
            Object.keys(SAVED_COL_FILTERS).forEach((k) => {
                const idx = Number(k);
                const vals = SAVED_COL_FILTERS[k];
                if (!CFG_BY_INDEX[idx] || !Array.isArray(vals) || !vals.length) return;
                activeFilters[idx] = new Set(vals);
                markFunnel(CFG_BY_INDEX[idx]);
            });
            applyFilters();
        }

        function buildPanel(cfg, anchorBtn) {
            closePanel();

            const values = Array.from(new Set(orderRows().map((r) => cellValue(r, cfg)))).sort((a, b) =>
                a.localeCompare(b, undefined, { numeric: true })
            );
            const allowed = activeFilters[cfg.i];

            const panel = document.createElement("div");
            panel.className = "col-filter-panel";
            panel.dataset.col = String(cfg.i);
            panel.innerHTML =
                '<input type="text" class="cfp-search form-control form-control-sm" placeholder="Search...">' +
                '<label class="cfp-item" style="font-weight:600;border-bottom:1px solid #eee;padding-bottom:4px;">' +
                '<input type="checkbox" class="cfp-all"><span>(Select all)</span></label>' +
                '<div class="cfp-list"></div>' +
                '<div class="cfp-actions">' +
                '<button type="button" class="btn btn-sm btn-outline-secondary cfp-clear">Clear filter</button>' +
                '<button type="button" class="btn btn-sm btn-primary cfp-close">Done</button>' +
                "</div>";

            const list = panel.querySelector(".cfp-list");
            values.forEach((v) => {
                const lbl = document.createElement("label");
                lbl.className = "cfp-item";
                const cb = document.createElement("input");
                cb.type = "checkbox";
                cb.className = "cfp-val";
                cb.checked = !allowed || allowed.has(v);
                cb.value = v;
                const span = document.createElement("span");
                span.textContent = v === "" ? "(Blank)" : v;
                span.title = span.textContent;
                lbl.appendChild(cb);
                lbl.appendChild(span);
                list.appendChild(lbl);
            });

            function syncAll() {
                const boxes = Array.from(list.querySelectorAll(".cfp-val"));
                const checked = boxes.filter((b) => b.checked).length;
                const all = panel.querySelector(".cfp-all");
                all.checked = checked === boxes.length;
                all.indeterminate = checked > 0 && checked < boxes.length;
            }

            function commit() {
                const boxes = Array.from(list.querySelectorAll(".cfp-val"));
                const checked = boxes.filter((b) => b.checked).map((b) => b.value);
                if (checked.length === boxes.length) {
                    delete activeFilters[cfg.i];
                } else {
                    activeFilters[cfg.i] = new Set(checked);
                }
                applyFilters();
                markFunnel(cfg);
                saveColumnFilters();
            }

            panel.querySelector(".cfp-search").addEventListener("input", function () {
                const q = this.value.toLowerCase();
                list.querySelectorAll(".cfp-item").forEach((it) => {
                    it.style.display = it.textContent.toLowerCase().includes(q) ? "" : "none";
                });
            });
            panel.querySelector(".cfp-all").addEventListener("change", function () {
                list.querySelectorAll(".cfp-item").forEach((it) => {
                    if (it.style.display !== "none") it.querySelector(".cfp-val").checked = this.checked;
                });
                commit();
            });
            list.addEventListener("change", function (e) {
                if (e.target.classList.contains("cfp-val")) {
                    commit();
                    syncAll();
                }
            });
            panel.querySelector(".cfp-clear").addEventListener("click", function () {
                list.querySelectorAll(".cfp-val").forEach((b) => (b.checked = true));
                commit();
                syncAll();
            });
            panel.querySelector(".cfp-close").addEventListener("click", closePanel);

            document.body.appendChild(panel);
            syncAll();

            // Position just under the funnel button, kept inside the viewport.
            const rect = anchorBtn.getBoundingClientRect();
            let left = Math.max(8, rect.right - panel.offsetWidth);
            let top = rect.bottom + 4;
            top = Math.max(8, Math.min(top, window.innerHeight - panel.offsetHeight - 8));
            panel.style.left = left + "px";
            panel.style.top = top + "px";

            openPanel = panel;
            panel.querySelector(".cfp-search").focus();
        }

        FILTER_COLS.forEach((cfg) => {
            const th = thForCol[cfg.i];
            if (!th) return;
            th.classList.add("has-col-filter");
            const btn = document.createElement("button");
            btn.type = "button";
            btn.className = "col-filter-btn";
            btn.title = "Filter " + cfg.label;
            btn.innerHTML = '<i class="bi bi-funnel-fill"></i>';
            btn.addEventListener("click", function (e) {
                e.preventDefault();
                e.stopPropagation();
                if (openPanel && openPanel.dataset.col === String(cfg.i)) {
                    closePanel();
                } else {
                    buildPanel(cfg, btn);
                }
            });
            btn.addEventListener("mousedown", (e) => e.stopPropagation());
            th.appendChild(btn);
        });

        // Funnel buttons now exist — re-apply any saved per-column filters.
        restoreColumnFilters();

        // Close the panel on outside click, Escape, table scroll, or window resize.
        document.addEventListener("mousedown", function (e) {
            if (openPanel && !openPanel.contains(e.target) && !e.target.closest(".col-filter-btn")) {
                closePanel();
            }
        });
        document.addEventListener("keydown", function (e) {
            if (e.key === "Escape") closePanel();
        });
        const scrollBottomEl = document.getElementById("tableScrollBottom");
        if (scrollBottomEl) scrollBottomEl.addEventListener("scroll", closePanel);
        window.addEventListener("resize", closePanel);

        const clearAllBtn = document.getElementById("clearColFiltersBtn");
        if (clearAllBtn) {
            clearAllBtn.addEventListener("click", function () {
                Object.keys(activeFilters).forEach((k) => delete activeFilters[k]);
                FILTER_COLS.forEach(markFunnel);
                applyFilters();
                saveColumnFilters();
                closePanel();
            });
        }
    });
</script>

@endsection
