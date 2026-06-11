@extends('component.layout')
@section('title', 'Archived Truck Planning')
@section('back_button')
    <a href="{{ route('consignment-order.index') }}" class="btn btn-outline-secondary btn-sm" style="margin-right: 8px; border-radius: 50%; width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center; padding: 0;">
        <i class="bi bi-arrow-left"></i>
    </a>
@endsection
@section('content')
    @php
        use App\Models\Truck;
        use App\Models\Subcon;
        use App\Models\Customer;
        use App\Models\DraftCustomer;
        use App\Models\Unit;

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

    <div class="row">
        <!-- Main Table Section -->
        <div class="">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center flex-wrap mb-3">
                        <form class="d-flex gap-3 align-items-stretch" id="filterForm" style="width: 100%;">
                            <div class="input-group">
                                <input type="text" name="filter_daterange" class="form-control" id="filter_daterange"
                                    placeholder="Filter Date Range" autocomplete="off">
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
                                        style="background-color: #fbc658 ">
                                        Planning</option>
                                    <option value="completed"
                                        {{ request('status') == 'completed' ? 'selected' : '' }}style="background-color: #28a745">
                                        Completed</option>
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
                                    <option value="VK 1827">VK 1827</option>
                                    <option value="ABC 1234">ABC 1234</option>
                                </select>
                            </div>

                            <div class="input-group w-10">
                                <button type="reset" class="btn btn-danger d-none" style="margin: 0;" id="clearBtn">
                                    <i class="bi bi-x"></i>
                                </button>
                            </div>
                        </form>


                        <div class="d-flex justify-content-between align-items-center w-100">
                        </div>

                    </div>
                    <div id="tableScrollTop" style="overflow-x:auto; overflow-y:hidden;"></div>
                    <div id="tableScrollBottom" style="overflow-x:auto;">
                        <table class="table table-sm table-striped table-bordered align-middle table-nowrap"
                            style="font-size: 0.75rem; border-collapse: collapse;">
                            <thead class="">
                                <tr>
                                    <th class="sticky-col" rowspan="2">
                                        <input type="checkbox">
                                    </th>
                                    <th class="sticky-col" rowspan="2">No</th>
                                    <th class="sticky-col" rowspan="2">
                                        <a class="text-dark text-decoration-none"
                                            href="{{ route('archived-consignment-order.index', array_merge(request()->query(), ['sort_by' => 'load_date', 'sort_order' => request('sort_order') === 'asc' && request('sort_by') === 'load_date' ? 'desc' : 'asc'])) }}">
                                            <span class="d-inline-block" style="white-space: normal;">
                                                Pick Up Date</span>
                                            @if (request('sort_by') == 'load_date')
                                                <i
                                                    class="bi bi-caret-{{ request('sort_order') == 'asc' ? 'up' : 'down' }}-fill"></i>
                                            @endif
                                        </a>
                                    </th>
                                    <th class="sticky-col" rowspan="2">
                                        <a class="text-dark text-decoration-none"
                                            href="{{ route('archived-consignment-order.index', array_merge(request()->query(), ['sort_by' => 'consignor', 'sort_order' => request('sort_order') === 'asc' && request('sort_by') === 'consignor' ? 'desc' : 'asc'])) }}">
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
                                            href="{{ route('archived-consignment-order.index', array_merge(request()->query(), ['sort_by' => 'consignee', 'sort_order' => request('sort_order') === 'asc' && request('sort_by') === 'consignee' ? 'desc' : 'asc'])) }}">
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
                                    <th rowspan="2">
                                        <a class="text-dark text-decoration-none"
                                            href="{{ route('archived-consignment-order.index', array_merge(request()->query(), ['sort_by' => 'truck_number', 'sort_order' => request('sort_order') === 'asc' && request('sort_by') === 'truck_number' ? 'desc' : 'asc'])) }}">
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
                                            href="{{ route('archived-consignment-order.index', array_merge(request()->query(), ['sort_by' => 'remarks', 'sort_order' => request('sort_order') === 'asc' && request('sort_by') === 'remarks' ? 'desc' : 'asc'])) }}">
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
                                            href="{{ route('archived-consignment-order.index', array_merge(request()->query(), ['sort_by' => 'status', 'sort_order' => request('sort_order') === 'asc' && request('sort_by') === 'status' ? 'desc' : 'asc'])) }}">
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
                                        </td>
                                        <td class="sticky-col">
                                            @php
                                                $consignorDisplay = '-';
                                                if (!empty($order['consignor'])) {
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
                                            @forelse ($qtyArr as $qty)
                                                {{ $qty }}<br>
                                            @empty
                                                -
                                            @endforelse
                                        </td>

                                        <td>
                                            @forelse ($unitArr as $unit)
                                                {{ $unit }}<br>
                                            @empty
                                                -
                                            @endforelse
                                        </td>

                                        <td>{{ $order['pre_pick'] ?? '-' }}</td>

                                        <td>
                                            <div class="d-flex align-items-center gap-1">
                                                <select class="form-select form-select-sm truck-number-select" disabled>
                                                    <option value=""></option>
                                                </select>
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
                                                            <div style="flex: 1; min-width: 0;">
                                                                <h6 class="dropdown-header px-0 pt-0">Pick Details</h6>
                                                                <div class="mb-1" style="word-wrap: break-word;"><strong>Point:</strong> {{ $order['pick_point'] ?? '-' }}</div>
                                                                <div class="mb-1" style="word-wrap: break-word;"><strong>Address:</strong> {{ $order['pick_address'] ?? '-' }}</div>
                                                                <div class="mb-1" style="word-wrap: break-word;"><strong>Truck Size:</strong> {{ $order['pick_truck_size'] ?? '-' }}</div>
                                                                <div style="word-wrap: break-word;"><strong>Truck Type:</strong> {{ $order['pick_truck_type'] ?? '-' }}</div>
                                                            </div>
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
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="21" class="text-center">No archived consignment orders found.</td>
                                    </tr>
                                @endforelse
                            </tbody>

                        </table>
                    </div>


                </div>
            </div>
        </div>



    </div>
@endsection

<script>
    document.addEventListener('DOMContentLoaded', function() {
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

        const tableBody = document.querySelector('table.table tbody');
        if (!tableBody) return;

        // Truck data
        const ALL_TRUCKS = @json($trucks);
        const ALL_SUBCONS = @json($subcons);
        const truckCacheByDate = {};

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

        function stripTruckSuffix(value) {
            return (value || '').toString().replace(/\s*\((?:Temp|Subcon)\)\s*$/i, '');
        }

        function isValidTruck(truck, pickType, pickSize, dropType, dropSize) {
            if (!truck.chassis_type) return false;
            const tType = normalize(truck.chassis_type);
            const tSize = normalize(truck.size);

            let matchPick = false;
            let matchDrop = false;

            if (pickType) {
                matchPick = tType === pickType;
                if (matchPick && tSize && pickSize) {
                    matchPick = tSize === pickSize;
                }
            }
            if (dropType) {
                matchDrop = tType === dropType;
                if (matchDrop && tSize && dropSize) {
                    matchDrop = tSize === dropSize;
                }
            }
            return matchPick || matchDrop;
        }

        function populateRowSelect(row, trucks, subcons, tempTrucks) {
            const pickType = normalize(row.dataset.pickType);
            const pickSize = normalize(row.dataset.pickSize);
            const dropType = normalize(row.dataset.dropType);
            const dropSize = normalize(row.dataset.dropSize);
            const selectedTruck = stripTruckSuffix(row.dataset.selectedTruck);

            const select = row.querySelector('.truck-number-select');
            if (!select) return;

            const hasCriteria = !!pickType || !!dropType;

            select.innerHTML = '<option value="">-</option>';
            select.disabled = true;

            if (hasCriteria) {
                trucks
                    .filter(t => isValidTruck(t, pickType, pickSize, dropType, dropSize))
                    .forEach(t => {
                        const opt = document.createElement('option');
                        opt.value = t.number;
                        opt.textContent = t.number;
                        if (t.number === selectedTruck) opt.selected = true;
                        select.appendChild(opt);
                    });

                subcons
                    .filter(s => isValidTruck(s, pickType, pickSize, dropType, dropSize))
                    .forEach(s => {
                        const opt = document.createElement('option');
                        opt.value = s.truck_no;
                        opt.textContent = s.truck_no + ' (Subcon)';
                        if (s.truck_no === selectedTruck) opt.selected = true;
                        select.appendChild(opt);
                    });
            }

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

            if (selectedTruck && !select.querySelector(`option[value="${selectedTruck}"]`)) {
                const opt = document.createElement('option');
                opt.value = selectedTruck;
                opt.textContent = selectedTruck;
                opt.selected = true;
                select.insertBefore(opt, select.options[1] || null);
            }
        }

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

        // Set sticky column positions
        setTimeout(function() {
            const table = document.querySelector("table.table");
            if (!table) return;

            const firstRow = table.querySelector("tbody tr:first-child");
            const headerRow = table.querySelector("thead tr");

            if (!firstRow && !headerRow) return;

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

        // Add/Remove inline quantity-unit rows
        document.addEventListener("click", function(e) {
            const addBtn = e.target.closest(".addInlineQtyRow");
            if (addBtn) {
                const container = addBtn.closest(".inline-qty-unit-container");
                const row = addBtn.closest(".inline-qty-unit-row");
                const clone = row.cloneNode(true);
                clone.querySelectorAll("input").forEach(input => input.value = "");
                clone.querySelectorAll("select").forEach(select => select.selectedIndex = 0);
                container.appendChild(clone);
                container.querySelectorAll(".removeInlineQtyRow").forEach(btn => btn.style.display = "");
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

        const CONSIGNORS = @json($consignors ?? []);
        const CONSIGNEES = @json($consignees ?? []);
        const TRUCK_GROUPS = @json($truckGroups ?? []);
        const UNITS = @json($units ?? []);

        function escapeAttr(str) {
            return (str || '').replace(/&/g, '&amp;').replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
                .replace(/`/g, '&#96;').replace(/\$/g, '&#36;');
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

            function sizeOptions(selected) {
                return `<option value=""${!selected ? ' selected' : ''}>-</option>
                        <option value="Small"${selected === 'Small' ? ' selected' : ''}>Small</option>
                        <option value="Any"${selected === 'Any' ? ' selected' : ''}>Any</option>`;
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
                    <input type="date" class="form-control form-control-sm" name="load_date" value="${escapeAttr(loadDate)}" required>
                </td>
                <td class="sticky-col">
                    <input name="consignor" list="edit_consignor_list_${id}" class="form-control form-control-sm"
                           placeholder="Consignor" value="${escapeAttr(consignor)}" required>
                    <datalist id="edit_consignor_list_${id}">
                        ${consignorOptions}
                    </datalist>
                </td>
                <td class="sticky-col">
                    <input type="text" class="form-control form-control-sm" name="pick_point" placeholder="Pick Point" value="${escapeAttr(pickPoint)}" required>
                    <input type="text" class="form-control form-control-sm mt-1" name="pick_address"
                           list="pick_address_list_${id}" placeholder="Pick Address" value="${escapeAttr(pickAddress)}">
                    <datalist id="pick_address_list_${id}"></datalist>
                </td>
                <td class="sticky-col">
                    <input name="consignee" list="edit_consignee_list_${id}" class="form-control form-control-sm"
                           placeholder="Consignee" value="${escapeAttr(consignee)}" required>
                    <datalist id="edit_consignee_list_${id}">
                        ${consigneeOptions}
                    </datalist>
                </td>
                <td class="sticky-col">
                    <input type="text" class="form-control form-control-sm" name="drop_point" placeholder="Drop Point" value="${escapeAttr(dropPoint)}" required>
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

            populateInlineTruckSelect(row, loadDate, truckNumber);

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

            if (consignor) fetchAndFillLocations(row, consignor, 'consignor', { overwriteValue: false });
            if (consignee) fetchAndFillLocations(row, consignee, 'consignee', { overwriteValue: false });

            return row;
        }

        function populateInlineTruckSelect(row, date, selectedTruck) {
            const select = row.querySelector('[name="truck_number"]');
            if (!select) return;

            selectedTruck = stripTruckSuffix(selectedTruck);

            const norm = v => (v || '').toString().trim().toLowerCase();
            const pickType = norm(row.querySelector('[name="pick_truck_type"]')?.value);
            const pickSize = norm(row.querySelector('[name="pick_truck_size"]')?.value);
            const dropType = norm(row.querySelector('[name="drop_truck_type"]')?.value);
            const dropSize = norm(row.querySelector('[name="drop_truck_size"]')?.value);

            function isValid(t) {
                const tType = norm(t.chassis_type);
                const tSize = norm(t.size);

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
                if (selectedTruck && !select.querySelector(`option[value="${selectedTruck}"]`)) {
                    const opt = document.createElement('option');
                    opt.value = selectedTruck;
                    opt.textContent = selectedTruck;
                    opt.selected = true;
                    select.insertBefore(opt, select.options[1] || null);
                }
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

                const firstRow = table.querySelector("tbody tr:not(.inline-edit-row)");
                const headerRow = table.querySelector("thead tr");

                if (!firstRow && !headerRow) return;

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
        }

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
                fetchAndFillLocations(row, e.target.value, 'consignee');
            }
        });

        function populateTruckDropdown(row) {
            const pickType = normalize(row.dataset.pickType);
            const pickSize = normalize(row.dataset.pickSize);
            const dropType = normalize(row.dataset.dropType);
            const dropSize = normalize(row.dataset.dropSize);
            const selectedTruck = stripTruckSuffix(row.dataset.selectedTruck);
            const loadDate = row.dataset.loadDate || '';

            const select = row.querySelector('.truck-number-select');
            if (!select) return;

            const hasPickCriteria = !!pickType;
            const hasDropCriteria = !!dropType;

            if (!hasPickCriteria && !hasDropCriteria) {
                select.innerHTML = '<option value="">-</option>';
                return;
            }

            function fillSelect(trucks, subcons, tempTrucks) {
                select.innerHTML = '<option value="">-</option>';
                select.disabled = true;

                trucks
                    .filter(t => isValidTruck(t, pickType, pickSize, dropType, dropSize))
                    .forEach(t => {
                        const opt = document.createElement('option');
                        opt.value = t.number;
                        opt.textContent = t.number;
                        if (t.number === selectedTruck) opt.selected = true;
                        select.appendChild(opt);
                    });

                subcons
                    .filter(s => isValidTruck(s, pickType, pickSize, dropType, dropSize))
                    .forEach(s => {
                        const opt = document.createElement('option');
                        opt.value = s.truck_no;
                        opt.textContent = s.truck_no + ' (Subcon)';
                        if (s.truck_no === selectedTruck) opt.selected = true;
                        select.appendChild(opt);
                    });

                (tempTrucks || []).forEach(t => {
                    const opt = document.createElement('option');
                    opt.value = t.truck_no;
                    opt.textContent = (t.subcon_id && t.subcon_truck_no)
                        ? t.subcon_truck_no + ' (Subcon)'
                        : t.truck_no + ' (Temp)';
                    if (t.truck_no === selectedTruck) opt.selected = true;
                    select.appendChild(opt);
                });

                if (selectedTruck && !select.querySelector(`option[value="${selectedTruck}"]`)) {
                    const opt = document.createElement('option');
                    opt.value = selectedTruck;
                    opt.textContent = selectedTruck;
                    opt.selected = true;
                    select.insertBefore(opt, select.options[1] || null);
                }
            }

            if (loadDate && truckCacheByDate[loadDate]) {
                const cached = truckCacheByDate[loadDate];
                fillSelect(cached.trucks, cached.subcons, cached.temp_trucks || []);
            } else if (loadDate) {
                fetch(`/api/available-trucks?date=${loadDate}`)
                    .then(r => r.json())
                    .then(data => {
                        truckCacheByDate[loadDate] = data;
                        fillSelect(data.trucks, data.subcons, data.temp_trucks || []);
                    })
                    .catch(() => fillSelect(ALL_TRUCKS, ALL_SUBCONS, []));
            } else {
                fillSelect(ALL_TRUCKS, ALL_SUBCONS, []);
            }
        }

        // Save / Cancel / Edit-inline click delegate
        tableBody.addEventListener('click', function(e) {
            // Save inline row
            if (e.target.closest('.save-inline-row')) {
                const row = e.target.closest('.inline-edit-row');
                const formData = new FormData();
                const editId = row.dataset.editId;

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

                const loadDate = row.querySelector('[name="load_date"]').value;
                const consignor = row.querySelector('[name="consignor"]').value;
                const consignee = row.querySelector('[name="consignee"]').value;
                const pickPoint = row.querySelector('[name="pick_point"]').value;
                const dropPoint = row.querySelector('[name="drop_point"]').value;

                if (!loadDate || !consignor || !consignee || !pickPoint || !dropPoint) {
                    Swal.fire('Error', 'Please fill in all required fields', 'error');
                    return;
                }

                const expressSelect = row.querySelector('[name="express_mode"]');
                const newExpress = expressSelect && expressSelect.value === '1';
                const wasExpress = (row.dataset.expressMode || '0') === '1';
                const expressTurningOn = newExpress && !wasExpress;

                const submitInline = (action) => {
                    formData.append('express_action', action);

                    Swal.fire({
                        title: 'Saving...',
                        allowOutsideClick: false,
                        didOpen: () => { Swal.showLoading(); }
                    });

                    fetch(`/consignment-order/${editId}/update-inline`, {
                            method: "POST",
                            headers: { "X-CSRF-TOKEN": "{{ csrf_token() }}" },
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
                                    location.reload();
                                });
                            } else {
                                Swal.fire('Error', data.message || 'Failed to save order', 'error');
                            }
                        })
                        .catch(err => {
                            console.error(err);
                            Swal.fire('Error', 'Failed to save order', 'error');
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
                    text: 'Changes will be discarded',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, discard',
                    cancelButtonText: 'No, keep editing'
                }).then((result) => {
                    if (result.isConfirmed) {
                        if (editId && row._originalHTML) {
                            const restoredRow = document.createElement('tr');
                            restoredRow.className = row._originalClassName;
                            restoredRow.innerHTML = row._originalHTML;
                            for (const key in row._originalDataset) {
                                restoredRow.dataset[key] = row._originalDataset[key];
                            }
                            row.parentNode.replaceChild(restoredRow, row);
                            populateTruckDropdown(restoredRow);
                        } else {
                            row.remove();
                        }
                        recalculateStickyColumns();
                    }
                });
            }

            // Edit inline button
            if (e.target.closest('.edit-inline-btn')) {
                if (document.querySelector('.inline-edit-row')) {
                    Swal.fire('Warning', 'Please save or cancel the current row first', 'warning');
                    return;
                }

                const row = e.target.closest('.order-row');
                convertRowToEdit(row);
                recalculateStickyColumns();
            }
        });
    });
</script>

<style>
    .truck-card.active {
        border: 2px solid #007bff;
        box-shadow: 0 0 5px rgba(0, 123, 255, 0.5);
    }

    .truck-card .card-body div>div {
        line-height: 1.8;
    }

    .table-nowrap td,
    .table-nowrap th {
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 200px;
    }

    .table-responsive {
        overflow-x: auto;
    }

    table.table td,
    table.table th {
        padding: 0.25rem 0.4rem !important;
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

    #tableScrollBottom {
        overflow-x: auto;
        overflow-y: hidden;
    }
</style>
