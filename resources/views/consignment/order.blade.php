@extends('component.layout')
@section('title', 'Truck Planning')
@section('content')

    @php
        use App\Models\Truck;
        use App\Models\Subcon;
        $trucks = Truck::select('number', 'chassis_type', 'size')->get();
        $subcons = Subcon::select('truck_no', 'chassis_type', 'size')->get();
        $currentPage = $consignments->currentPage();
        $lastPage = $consignments->lastPage();
        $query = request()->query();
        unset($query['page']);
        unset($query['per_page']);
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
                            </div>

                            <div class="input-group no-border w-100">
                                <input type="text" class="form-control" placeholder="Search..." name="search">
                                <div class="input-group-append">
                                    <span class="input-group-text"><i class="nc-icon nc-zoom-split"></i></span>
                                </div>
                            </div>

                            <div class="input-group">
                                <select name="status" id="statusFilter" class="form-select text-white">
                                    <option value="" disabled {{ request('status') ? '' : 'selected' }}
                                        style="background-color: #ffffff">Select Status
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
                                    <option value="" disabled selected>Truck Type</option>
                                    <option value="40">40 ft</option>
                                    <option value="20">20 ft</option>
                                </select>
                            </div>

                            <div class="input-group">
                                <select name="truck_number" id="truckNumber" class="form-select">
                                    <option value="" disabled selected>Truck Number</option>
                                    @foreach ($trucks_no as $truck)
                                        <option value="{{ $truck }}">{{ $truck }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="input-group" style="width: 5%">
                                <button type="reset" class="btn btn-danger d-none" style="margin: 0;" id="clearBtn">
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
                    const consignmentId = row.dataset
                        .id; // <-- Make sure your <tr> has data-id="{{ $order->id }}"
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

            const firstRow = table.querySelector("thead tr");
            if (!firstRow) return;

            const cells = firstRow.querySelectorAll("th");
            let offset = 0;

            for (let i = 0; i < Math.min(7, cells.length); i++) {
                const width = Math.round(cells[i].getBoundingClientRect().width);

                if (i > 0) {
                    document.documentElement.style.setProperty(`--col-${i}`, offset + "px");
                }

                offset += width;
            }
        }, 100);

        // Initialize daterangepicker
        const daterangeInput = $('#filter_daterange');
        const filterForm = $('#filterForm');
        const clearBtn = $('#clearBtn');

        daterangeInput.daterangepicker({
            autoUpdateInput: false,
            locale: {
                cancelLabel: 'Clear',
                format: 'YYYY-MM-DD'
            }
        });

        daterangeInput.on('apply.daterangepicker', function(ev, picker) {
            $(this).val(picker.startDate.format('YYYY-MM-DD') + ' to ' + picker.endDate.format(
                'YYYY-MM-DD'));
            filterForm.submit();
        });

        daterangeInput.on('cancel.daterangepicker', function() {
            $(this).val('');
            filterForm.submit();
        });

        $('#statusFilter, #truckType, #truckNumber, input[name="search"]').on('change keyup', function() {
            filterForm.submit();
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
    });
</script>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
<script src="https://cdn.jsdelivr.net/npm/moment@2.29.4/moment.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
<style>
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
        font-size: 0.65rem!important;
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
