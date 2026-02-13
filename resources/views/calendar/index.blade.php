@extends('component.layout')
@section('title', 'Truck Capacity')
@section('content')
    @php
        $dateColumnCount = count($dates) * 2;
    @endphp
    @if (session('swal'))
        <script>
            Swal.fire({
                icon: "{{ session('swal.icon') }}",
                title: "{{ session('swal.title') }}",
                text: "{{ session('swal.text') }}",
                confirmButtonColor: '#3085d6',
            });
        </script>
    @endif


    <div class="container-fluid mt-3">
        <div class="d-flex justify-content-between align-items-center">
            {{-- <h3>Truck Availability</h3> --}}
        </div>

        <div class="d-flex justify-content-between align-items-center flex-wrap mb-3">
            <form class="d-flex gap-3 align-items-stretch" style="width: 70%;" id="filterForm" method="GET"
                action="{{ route('calendar.index') }}">
                @csrf

                <!-- Date Range -->
                <div class="input-group">
                    <input type="text" id="filter_daterange" name="filter_daterange" class="form-control"
                        placeholder="Filter Date Range" autocomplete="off">
                    <span class="input-group-text">
                        <i class="nc-icon nc-calendar-60"></i>
                    </span>
                </div>

                <!-- Search -->
                <div class="input-group no-border w-100">
                    <input type="text" id="search" name="search" class="form-control" placeholder="Search...">
                    <div class="input-group-append">
                        <button type="button" id="searchBtn" class="input-group-text">
                            <i class="nc-icon nc-zoom-split"></i>
                        </button>
                    </div>
                </div>

                <!-- Truck Type -->
                <div class="input-group">
                    <select id="logName" name="logName" class="form-control">
                        <option value="" selected disabled>Select Truck Type</option>
                        <option value="SNL40">Truck Type: SNL 40#</option>
                        <option value="20FT">20 footer & below</option>
                    </select>
                </div>

                <!-- Country -->
                <div class="input-group">
                    <select id="country" name="country" class="form-control">
                        <option value="" selected disabled>Select Country</option>
                        <option value="KL">Kuala Lumpur</option>
                        <option value="SG">Singapore</option>
                    </select>
                </div>

                <!-- Clear Button -->
                <div class="input-group w-100">
                    <button type="reset" class="btn btn-danger d-none" id="clearBtn">
                        <i class="bi bi-x"></i>
                    </button>
                </div>
            </form>


            <div class="d-flex gap-2">
                <!-- Toggle Switch Container -->
                <div class="form-check form-switch d-flex align-items-center">
                    <label class="slider-toggle mb-0">
                        <input type="checkbox" id="toggle14Days" {{ $days == 14 ? 'checked' : '' }}>
                        <span class="slider-text">{{ $days == 14 ? '14 Days' : '7 Days' }}</span>
                        <span class="slider-knob"></span>
                    </label>

                </div>

                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#availabilityModal"
                    style="border-radius: 0.2rem; display: inline-flex; align-items: center;">
                    <i class="bi bi-calendar-plus-fill" style="font-size: 20px; margin-right: 5px;"></i>
                    Create New Availability
                </button>
            </div>
        </div>



        {{-- <div class="mt-3 d-flex gap-4">
            <span class="badge bg-danger">Balance</span>
            <span class="badge bg-success">Origin</span>
            <span class="badge bg-primary text-dark">Consignor</span>

        </div>
        <div class="mt-3 d-flex gap-4 mb-3">
            <span><span class="badge" style="background:#ffe135;">&nbsp;&nbsp;</span> Available</span>
            <span><span class="badge" style="background:#6cff3f;">&nbsp;&nbsp;</span> Occupied</span>
            <span><span class="badge" style="background:#ff6666;">&nbsp;&nbsp;</span> Off Day/Maintenance</span>
        </div> --}}
        <div class="d-flex justify-content-center mb-3">
            <div class="d-flex align-items-center gap-3">
                <button class="btn btn-outline-secondary" id="prevBtn">
                    <i class="bi bi-chevron-left"></i> Previous
                </button>

                <h5 class="mb-0 fw-bold">
                    {{ $start->format('d M Y') }} -
                    {{ \Carbon\Carbon::parse($dates->last()['date'])->format('d M Y') }}
                </h5>

                <button class="btn btn-outline-secondary" id="nextBtn">
                    Next <i class="bi bi-chevron-right"></i>
                </button>
            </div>
        </div>


        <div class="table-responsive">
            <table class="table text-left table-bordered " style="table-layout: fixed;">
                <thead>
                    <tr class="text-center">
                        <th rowspan="2">Truck Num</th>
                        <th rowspan="2">
                            Loc.</th>
                        @foreach ($dates as $date)
                            @php
                                // --- Compute utilization rate for MY and SG ---
                                $myUsed = $date['MY']['balance'] ?? 0;
                                $sgUsed = $date['SG']['balance'] ?? 0;
                                $totalCapacity = ($date['MY']['origin'] ?? 0) + ($date['SG']['origin'] ?? 0);

                                $myRate = ($date['MY']['origin'] ?? 0) > 0 ? ($myUsed / ($date['MY']['origin'] ?? 0)) * 100 : 0;
                                $sgRate = ($date['SG']['origin'] ?? 0) > 0 ? ($sgUsed / ($date['SG']['origin'] ?? 0)) * 100 : 0;

                                // --- Determine colors ---
                                $myColor =
                                    $myRate > 70
                                        ? 'text-danger fw-bold'
                                        : ($myRate > 30
                                            ? 'text-warning fw-bold'
                                            : 'text-success fw-bold');
                                $sgColor =
                                    $sgRate > 70
                                        ? 'text-danger fw-bold'
                                        : ($sgRate > 30
                                            ? 'text-warning fw-bold'
                                            : 'text-success fw-bold');
                            @endphp
                            <th>
                                <div>
                                    <span
                                        class="{{ ($date['MY']['balance'] ?? 0) > ($date['MY']['origin'] ?? 0) ? 'text-danger' : '' }} {{ $myColor }}">
                                        {{ number_format(($date['MY']['origin'] ?? 0) - ($date['MY']['balance'] ?? 0), 1) }}
                                        <span style="color: black">/
                                            {{ number_format($date['MY']['origin'] ?? 0, 1) }}</span>

                                    </span>
                                </div>
                                <div hidden>
                                    <span
                                        class="{{ ($date['SG']['balance'] ?? 0) > ($date['SG']['origin'] ?? 0) ? 'text-danger' : '' }} {{ $sgColor }}">
                                        {{ number_format(($date['SG']['origin'] ?? 0) - ($date['SG']['balance'] ?? 0), 1) }}
                                        <span style="color: black">/
                                            {{ number_format($date['SG']['origin'] ?? 0, 1) }}</span>
                                    </span>
                                </div>
                                <div>
                                    <strong>{{ \Carbon\Carbon::parse($date['date'])->format('D') }}</strong>
                                </div>
                                <div>{{ \Carbon\Carbon::parse($date['date'])->format('j/n') }}</div>
                            </th>
                        @endforeach

                    </tr>
                </thead>

                <tbody>

                    @foreach ($trucks as $truck)
                        {{-- MY row --}}
                        <tr>
                            <td rowspan="2" class="fs-5 align-middle"><strong>{{ $truck['number'] }}</strong></td>
                            <td><strong>MY</strong></td>
                            @foreach ($calendarMatrix[$truck['number']] as $dayStatuses)
                                @php
                                    $status = $dayStatuses['MY']['status'];
                                    $used_capacity = $dayStatuses['MY']['used_capacity'] ?? 0;
                                    $total_capacity = $dayStatuses['MY']['total_capacity'] ?? 0;
                                    $consignors = $dayStatuses['MY']['consignors'];
                                    $dateOnly = \Carbon\Carbon::parse($dates[$loop->index]['date'])->format('Y-m-d');

                                    $cellStyle = match ($status) {
                                        'off-day' => 'background-color: #c3c2c2;',
                                        'available' => 'background-color: #ffffff;',
                                        'occupied' => 'background-color: #f7c6c7;',
                                        'maintenance' => 'background-color: #c3c2c2;',
                                        'empty' => 'background-color: #c3c2c2;',
                                        default => 'background-color: #c3c2c2;',
                                    };
                                @endphp
                                <td class="p-2 availability-cell" style="{{ $cellStyle }}"
                                    data-truck="{{ $truck['number'] }}" data-location="MY" data-date="{{ $dateOnly }}"
                                    data-status="{{ $status }}"
                                    data-has-consignors="{{ $consignors->isNotEmpty() ? 'true' : 'false' }}">
                                    @if ($consignors->isNotEmpty())
                                        @php
                                            $count = $consignors->count();
                                            $driverName = $dayStatuses['MY']['driver_name'] ?? null;

                                            // Start tooltip
                                            $tooltip = '';

                                            if (!empty($driverName)) {
                                                $tooltip .= '<div class="fw-bold mb-1">' . e($driverName) . '</div>';
                                            }

                                            if ($count > 0) {
                                                $tooltip .= '<ul class="mb-0 ps-3">';
                                                foreach ($consignors as $c) {
                                                    $tooltip .= '<li>' . e($c) . '</li>';
                                                }
                                                $tooltip .= '</ul>';
                                            }
                                        @endphp
                                        <div class="mt-1 text-center consignor-info" data-bs-toggle="tooltip"
                                            data-bs-html="true" data-bs-placement="top" title="{{ $tooltip }}">
                                            <span class="badge bg-primary text-dark mb-1">
                                                {{ number_format($used_capacity, 1) }}/{{ number_format($total_capacity, 1) }}
                                            </span>
                                            <br>
                                            <span class="badge bg-primary text-dark mb-0">
                                                {{ $count }} consignor{{ $count > 1 ? 's' : '' }}
                                            </span>
                                        </div>
                                    @endif
                                    @if (in_array($status, ['off-day', 'maintenance']))
                                        <div class="mt-1 text-center">
                                            <span
                                                class="badge
                                                    @if ($status === 'off-day') bg-secondary
                                                    @else bg-warning text-dark @endif mb-1">
                                                {{ ucfirst($status) }}
                                            </span>
                                        </div>
                                    @endif
                                </td>
                            @endforeach
                        </tr>

                        {{-- SG row --}}
                        <tr>
                            <td><strong>SG</strong></td>
                            @foreach ($calendarMatrix[$truck['number']] as $dayStatuses)
                                @php
                                    $status = $dayStatuses['SG']['status'];
                                    $used_capacity = $dayStatuses['SG']['used_capacity'] ?? 0;
                                    $total_capacity = $dayStatuses['SG']['total_capacity'] ?? 0;
                                    $consignors = $dayStatuses['SG']['consignors'];
                                    $dateOnly = \Carbon\Carbon::parse($dates[$loop->index]['date'])->format('Y-m-d');

                                    $cellStyle = match ($status) {
                                        'off-day' => 'background-color: #c3c2c2;',
                                        'available' => 'background-color: #ffffff;',
                                        'occupied' => 'background-color: #d1ecf1;',
                                        'maintenance' => 'background-color: #c3c2c2;',
                                        'empty' => 'background-color: #c3c2c2;',
                                        default => 'background-color: #c3c2c2;',
                                    };
                                @endphp
                                <td class="p-2 availability-cell" style="{{ $cellStyle }}"
                                    data-truck="{{ $truck['number'] }}" data-location="SG"
                                    data-date="{{ $dateOnly }}" data-status="{{ $status }}"
                                    data-has-consignors="{{ $consignors->isNotEmpty() ? 'true' : 'false' }}">
                                    @if ($consignors->isNotEmpty())
                                        @php
                                            $count = $consignors->count();
                                            $driverName = $dayStatuses['SG']['driver_name'] ?? null;

                                            // Start tooltip
                                            $tooltip = '';

                                            if (!empty($driverName)) {
                                                $tooltip .= '<div class="fw-bold mb-1">' . e($driverName) . '</div>';
                                            }

                                            if ($count > 0) {
                                                $tooltip .= '<ul class="mb-0 ps-3">';
                                                foreach ($consignors as $c) {
                                                    $tooltip .= '<li>' . e($c) . '</li>';
                                                }
                                                $tooltip .= '</ul>';
                                            }
                                        @endphp
                                        <div class="mt-1 text-center consignor-info" data-bs-toggle="tooltip"
                                            data-bs-html="true" data-bs-placement="top" title="{{ $tooltip }}">
                                            <span class="badge bg-primary text-dark mb-1">
                                                {{ number_format($used_capacity, 1) }}/{{ number_format($total_capacity, 1) }}
                                            </span>
                                            <br>
                                            <span class="badge bg-primary text-dark mb-0">
                                                {{ $count }} consignor{{ $count > 1 ? 's' : '' }}
                                            </span>
                                        </div>
                                    @endif
                                    @if (in_array($status, ['off-day', 'maintenance']))
                                        <div class="mt-1 text-center">
                                            <span
                                                class="badge
                                                        @if ($status === 'off-day') bg-secondary
                                                        @else bg-warning text-dark @endif mb-1">
                                                {{ ucfirst($status) }}
                                            </span>
                                        </div>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @endforeach


                    @if ($totalVisibleTrucks === 0)
                        <tr>
                            <td colspan="{{ count($dates) + 2 }}" class="text-center py-4 text-muted">
                                No truck activities found for this date range.
                            </td>
                        </tr>
                    @endif
                </tbody>



            </table>

            <div class="container mt-4">
                <div class="row g-3 justify-content-center">

                    <!-- MY Total Capacity -->
                    <div class="col-12 col-md-2">
                        <div class="card shadow-sm border-0 rounded-3 p-3 position-relative hover-scale"
                            style="background-color: #ffffff;">
                            <div>
                                <div class="fw-bold fs-4 text-dark">{{ number_format($totalMyCapacity, 1) }}</div>
                                <div class="small">MY Total Capacity</div>
                            </div>
                            <i class="bi bi-truck fs-2 position-absolute top-0 end-0 m-2" style="color: #f7c6c7;"></i>
                        </div>
                    </div>

                    <!-- MY Used -->
                    <div class="col-12 col-md-2">
                        <div class="card shadow-sm border-0 rounded-3 p-3 position-relative hover-scale"
                            style="background-color: #ffffff">
                            <div>
                                <div class="fw-bold fs-4">{{ number_format($totalMyCapacity - $totalMyUsed, 1) }}</div>
                                <div class="small">MY Unused</div>
                            </div>
                            <i class="bi bi-speedometer2 fs-2 position-absolute top-0 end-0 m-2"
                                style="color: #f7c6c7;"></i>
                        </div>
                    </div>

                    <!-- MY Utilization -->
                    <div class="col-12 col-md-2">
                        @php
                            $myUtilizationColor = match (true) {
                                $myUtilization < 30 => 'text-danger',
                                $myUtilization < 70 => 'text-warning',
                                default => 'text-success',
                            };
                        @endphp
                        <div class="card shadow-sm border-0 rounded-3 p-3 position-relative hover-scale"
                            style="background-color: #ffffff;">
                            <div>
                                <div class="fw-bold fs-4 {{ $myUtilizationColor }}">
                                    {{ number_format($myUtilization, 2) }}%</div>
                                <div class="small">MY Utilization</div>
                            </div>
                            <i class="bi bi-percent fs-2 position-absolute top-0 end-0 m-2" style="color: #f7c6c7;"></i>
                        </div>
                    </div>

                    <!-- SG Total Capacity -->
                    {{-- <div class="col-12 col-md-2">
                        <div class="card shadow-sm border-0 rounded-3 p-3 position-relative hover-scale"
                            style="background-color: #ffffff;">
                            <div>
                                <div class="fw-bold fs-4 text-dark">{{ number_format($totalSgCapacity, 1) }}</div>
                                <div class="small">SG Total Capacity</div>
                            </div>
                            <i class="bi bi-truck fs-2 position-absolute top-0 end-0 m-2" style="color: #ace5ef"></i>
                        </div>
                    </div> --}}

                    <!-- SG Used -->
                    {{-- <div class="col-12 col-md-2">
                        <div class="card shadow-sm border-0 rounded-3 p-3 position-relative hover-scale"
                            style="background-color: #ffffff;">
                            <div>
                                <div class="fw-bold fs-4">{{ number_format($totalSgUsed, 1) }}</div>
                                <div class="small">SG Used</div>
                            </div>
                            <i class="bi bi-speedometer2 fs-2 position-absolute top-0 end-0 m-2 "
                                style="color: #ace5ef"></i>
                        </div>
                    </div> --}}

                    <!-- SG Utilization -->
                    {{-- <div class="col-12 col-md-2">
                        @php
                            $sgUtilizationColor = match (true) {
                                $sgUtilization > 70 => 'text-danger',
                                $sgUtilization > 30 => 'text-warning',
                                default => 'text-success',
                            };
                        @endphp
                        <div class="card shadow-sm border-0 rounded-3 p-3 position-relative hover-scale"
                            style="background-color: #ffffff;">
                            <div>
                                <div class="fw-bold fs-4 {{ $sgUtilizationColor }}">
                                    {{ number_format($sgUtilization, 2) }}%</div>
                                <div class="small">SG Utilization</div>
                            </div>
                            <i class="bi bi-percent fs-2 position-absolute top-0 end-0 m-2" style="color: #ace5ef"></i>
                        </div>
                    </div> --}}

                </div>
            </div>


        </div>

    </div>
    <!-- Modal -->

    <!-- Info + Edit Modal -->
    <div class="modal fade" id="cellInfoModal" tabindex="-1" aria-labelledby="cellInfoLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="cellInfoLabel">Truck Planning Day Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="cellInfoContent" class="p-2 text-center">
                        <div class="spinner-border" role="status"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <div class="modal fade" id="availabilityModal" tabindex="-1" aria-labelledby="availabilityModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <!-- Header -->
                <div class="modal-header">
                    <h5 class="modal-title" id="availabilityModalLabel">
                        <i class="bi bi-pencil-square me-2"></i> Driver Availability
                    </h5>
                    <button type="button" class="btn-close text-danger" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>

                <!-- Body -->
                <div class="modal-body">
                    <form id="availabilityForm" method="POST" action="{{ route('calendar.store') }}">
                        @csrf

                        <div class="mb-3">
                            <label class="form-label fw-bold">Status:</label>
                            <select class="form-select" name="status" id="status" required>
                                <option value="" disabled selected>-- Select Status --</option>
                                <option value="available">Available</option>
                                <option value="off-day">Off day</option>
                                <option value="maintenance">Maintenance</option>
                            </select>
                        </div>

                        <div class="mb-3" id="date-container">
                            <label class="form-label fw-bold">Date:</label>
                            <input type="date" name="date" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold" id="location-label">Location:</label>
                            <select class="form-select" name="location" required>
                                <option value="" disabled selected>-- Select Location --</option>
                                <option value="MY">MY</option>
                                <option value="SG">SG</option>
                            </select>
                        </div>

                        <!--<div class="mb-3">
                                                                                                                    <label class="form-label fw-bold">Truck Type:</label>
                                                                                                                    <select class="form-select" name="truck_type" required>
                                                                                                                    <option value="">-- Select Truck Type --</option>
                                                                                                                    <option>SNL 20#</option>
                                                                                                                    <option>SNL 40#</option>
                                                                                                                    <option>20 footer & below</option>
                                                                                                                    </select>
                                                                                                                    </div>-->

                        <div class="mb-3">
                            <label class="form-label fw-bold">Truck Team:</label>
                            <select class="form-select" name="team" id="teamSelect">
                                <option value="" selected>-- Filter by Team (optional) --</option>
                                <option value="A">A</option>
                                <option value="B">B</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Trucks / Subcons:</label>
                            <input type="text" id="truckSearch" class="form-control mb-2"
                                placeholder="Search Truck/Subcon Number...">

                            <div id="truckCheckboxContainer" class="border rounded p-2"
                                style="max-height: 220px; overflow-y: auto; background-color: #f8f9fa;">
                                @foreach ($trucks_select as $truck)
                                    <div class="form-check truck-item" data-team="{{ $truck['team'] }}"
                                        data-number="{{ strtolower($truck['number']) }}"
                                        style="display:flex; align-items:center; gap:0.5rem;">
                                        <input class="form-check-input" type="checkbox" name="truck_numbers[]"
                                            id="truck_{{ $truck['id'] }}" value="{{ $truck['number'] }}">
                                        <label class="form-check-label mb-0" for="truck_{{ $truck['id'] }}">
                                            {{ $truck['number'] }}
                                            <span
                                                class="badge bg-secondary ms-1">{{ ucfirst($truck['source'] ?? 'Truck') }}</span>
                                            @if (!empty($truck['team']))
                                                <small class="text-muted">Team {{ $truck['team'] }}</small>
                                            @endif
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                            <small id="selectedCount" class="text-muted mb-1 d-block">0 selected</small>

                            <div id="selectedList" class="mt-2 text-muted" style="font-size: 0.9rem;">
                                <!-- Selected truck numbers will appear here -->
                            </div>
                        </div>


                        <!-- Footer (inside form) -->
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">CANCEL</button>
                            <button type="submit" class="btn btn-primary">SUBMIT</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>


@endsection
<style>
    .tooltip-inner {
        max-width: 400px !important;
        white-space: nowrap;
    }

    .availability-cell {
        cursor: default;
        transition: background-color 0.2s ease;
    }

    .availability-cell[data-status="off-day"],
    .availability-cell[data-status="maintenance"] {
        cursor: pointer;
    }

    .availability-cell[data-status="off-day"]:hover,
    .availability-cell[data-status="maintenance"]:hover {
        filter: brightness(0.9);
    }

    /* Force tooltip to stick to the top of the cell */
    .consignor-info {
        position: relative;
    }

    .bs-tooltip-top .tooltip-arrow {
        bottom: 0;
    }

    .tooltip-inner {
        max-width: 400px !important;
        white-space: nowrap;
    }

    .availability-cell {
        cursor: default;
        transition: background-color 0.2s ease;
    }

    .availability-cell[data-status="off-day"],
    .availability-cell[data-status="maintenance"] {
        cursor: pointer;
    }

    .availability-cell[data-status="off-day"]:hover,
    .availability-cell[data-status="maintenance"]:hover {
        filter: brightness(0.9);
    }

    /* Custom tooltip styling */
    .custom-consignor-tooltip {
        background-color: rgba(0, 0, 0, 0.9);
        color: white;
        padding: 8px 12px;
        border-radius: 4px;
        font-size: 13px;
        pointer-events: none;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
        white-space: nowrap;
        max-width: 400px;
    }

    .custom-consignor-tooltip ul {
        margin: 0;
        padding-left: 20px;
        list-style: disc;
        text-align: left;
    }

    .custom-consignor-tooltip li {
        margin: 2px 0;
    }

    .hover-scale:hover {
        transform: scale(1.05);
        transition: 0.3s;
    }

    #truckCheckboxContainer .form-check-input {
        flex-shrink: 0;
        /* Prevent shrinking inside flex */
        width: 1.25em;
        height: 1.25em;
    }

    #truckCheckboxContainer .form-check-label {
        flex-grow: 1;
        /* Label takes remaining space */
    }
</style>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/moment@2.29.4/moment.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/moment@2.29.4/moment.min.js"></script>

<script>
      function handleFormSubmit(event, form) {
        event.preventDefault();

        const formData = new FormData(form);

        console.log('=== Form Data Being Sent ===');
        for (let [key, value] of formData.entries()) {
            console.log(key, ':', value);
        }

        fetch("{{ route('availability.updateStatus') }}", {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: formData
            })
            .then(async res => {
                const text = await res.text();
                console.log('=== Response Status ===', res.status);
                console.log('=== Response Text ===', text);

                try {
                    const json = JSON.parse(text);
                    if (!res.ok) {
                        console.error('Server error:', json);
                        Swal.fire('Error', json.message || 'Update failed', 'error');
                        return;
                    }
                    return json;
                } catch (e) {
                    console.error('Invalid JSON response:', text);
                    Swal.fire('Error', 'Unexpected server response', 'error');
                }
            })
            .then(data => {
                console.log('=== Final Data ===', data);
                if (data?.success) {
                    Swal.fire({
                        title: 'Success!',
                        text: 'Availability updated successfully',
                        icon: 'success',
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => {
                        window.location.reload();
                    });
                }
            })
            .catch(err => {
                console.error('Fetch error:', err);
                Swal.fire('Error', 'Network or server error: ' + err.message, 'error');
            });
    }

    function handleDeleteAvailability(truck, date) {
        Swal.fire({
            title: 'Delete Availability?',
            text: `Are you sure you want to delete the availability for ${truck} on ${date}?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, delete it!',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                fetch("{{ route('availability.delete') }}", {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json',
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            truck: truck,
                            date: date
                        })
                    })
                    .then(async res => {
                        const json = await res.json();
                        if (!res.ok) {
                            throw new Error(json.message || 'Delete failed');
                        }
                        return json;
                    })
                    .then(data => {
                        if (data?.success) {
                            Swal.fire({
                                title: 'Deleted!',
                                text: 'Availability has been deleted.',
                                icon: 'success',
                                timer: 1500,
                                showConfirmButton: false
                            }).then(() => {
                                window.location.reload();
                            });
                        }
                    })
                    .catch(err => {
                        console.error('Delete error:', err);
                        Swal.fire('Error', err.message || 'Failed to delete availability', 'error');
                    });
            }
        });
    }
    document.addEventListener('DOMContentLoaded', function() {
        const teamSelect = document.getElementById('teamSelect');
        const searchInput = document.getElementById('truckSearch');
        const truckItems = document.querySelectorAll('.truck-item');
        const selectedCountLabel = document.getElementById('selectedCount');
        const selectedList = document.getElementById('selectedList');
        const selectedTrucks = [];

        function updateSelectedCount() {
            const checkedBoxes = document.querySelectorAll('.truck-item input[type="checkbox"]:checked');
            selectedCountLabel.textContent = `${checkedBoxes.length} selected`;

            // Add newly checked trucks to selectedTrucks
            checkedBoxes.forEach(cb => {
                if (!selectedTrucks.includes(cb.value)) {
                    selectedTrucks.push(cb.value);
                }
            });

            // Remove unchecked trucks from selectedTrucks
            selectedTrucks.forEach((val, index) => {
                if (![...checkedBoxes].some(cb => cb.value === val)) {
                    selectedTrucks.splice(index, 1);
                }
            });

            // Build ordered list
            if (selectedTrucks.length > 0) {
                const ol = document.createElement('ol');
                selectedTrucks.forEach(val => {
                    const li = document.createElement('li');
                    li.textContent = val;
                    ol.appendChild(li);
                });
                selectedList.innerHTML = '';
                selectedList.appendChild(ol);
            } else {
                selectedList.innerHTML = '';
            }
        }

        function updateLabelColors() {
            const selectedTeam = teamSelect.value.toLowerCase();

            truckItems.forEach(item => {
                const checkbox = item.querySelector('input[type="checkbox"]');
                const label = item.querySelector('label');
                const itemTeam = (item.dataset.team || '').toLowerCase();

                if (checkbox.checked) {
                    // Keep manually selected trucks highlighted
                    label.style.color = '#0d6efd';
                    label.style.fontWeight = '600';
                    item.style.opacity = '1';
                } else if (selectedTeam && itemTeam === selectedTeam) {
                    // Auto-select trucks in selected team
                    checkbox.checked = true;
                    label.style.color = '#0d6efd';
                    label.style.fontWeight = '400';
                    item.style.opacity = '1';
                } else if (selectedTeam && itemTeam !== selectedTeam) {
                    // Not in selected team, de-emphasize visually but don’t uncheck
                    label.style.color = 'inherit';
                    label.style.fontWeight = '400';
                    item.style.opacity = '0.6';
                } else {
                    // No team selected, reset style
                    label.style.color = 'inherit';
                    label.style.fontWeight = '400';
                    item.style.opacity = '1';
                }
            });
        }


        // Checkbox change
        truckItems.forEach(item => {
            const checkbox = item.querySelector('input[type="checkbox"]');
            checkbox.addEventListener('change', function() {
                updateSelectedCount();
                updateLabelColors();
            });
        });

        // Team filter
        teamSelect.addEventListener('change', function() {
            updateLabelColors();
            updateSelectedCount();
        });

        // Search filter
        searchInput.addEventListener('input', function() {
            const query = this.value.toLowerCase();
            truckItems.forEach(item => {
                const number = item.dataset.number || '';
                item.style.display = number.includes(query) ? 'flex' : 'none';
            });
        });

        // Initialize
        updateSelectedCount();
        updateLabelColors();

        $(document).on('click', '.availability-cell', function(e) {
            const $cell = $(this);
            const status = $cell.data('status');
            const truck = $cell.data('truck');
            const location = $cell.data('location');
            const date = $cell.data('date');
            const hasConsignors = $cell.data('has-consignors') === true || $cell.data(
                'has-consignors') === 'true';

            if (['off-day', 'maintenance'].includes(status)) {
                // Delete popup
                Swal.fire({
                    title: 'Delete this availability?',
                    text: `${truck} (${location}) on ${date}`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, delete it',
                    cancelButtonText: 'Cancel',
                    confirmButtonColor: '#d33',
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: "{{ url('calendar') }}/" + encodeURIComponent(truck) +
                                '/' + encodeURIComponent(location) + '/' +
                                encodeURIComponent(date),
                            type: 'DELETE',
                            data: {
                                _token: '{{ csrf_token() }}'
                            },
                            success: function(res) {
                                Swal.fire('Deleted!', res.message ||
                                        'Availability removed.', 'success')
                                    .then(() => window.location.reload());
                            },
                            error: function() {
                                Swal.fire('Error', 'Failed to delete availability.',
                                    'error');
                            }
                        });
                    }
                });
            } else if (hasConsignors || ['available'].includes(status)) {
                // Normal cell modal
                const modal = new bootstrap.Modal(document.getElementById('cellInfoModal'));
                const content = document.getElementById('cellInfoContent');

                content.innerHTML =
                    '<div class="p-4 text-center"><div class="spinner-border" role="status"></div></div>';
                modal.show();

                const params = new URLSearchParams({
                    truck,
                    date,
                    location
                });
                fetch(`/calendar/cell-details?${params.toString()}`)
                    .then(res => res.text())
                    .then(html => content.innerHTML = html)
                    .catch(err => content.innerHTML =
                        '<div class="p-3 text-danger">Failed to load details.</div>');
            }
        });

        const modal = new bootstrap.Modal(document.getElementById('cellInfoModal'));
        const content = document.getElementById('cellInfoContent');

        $('.consignor-info').each(function() {
            const $element = $(this);
            const tooltipContent = $element.attr('title');

            // Remove title to prevent default tooltip
            $element.removeAttr('title');

            // Create custom tooltip on hover
            $element.on('mouseenter', function(e) {
                const $tooltip = $('<div class="custom-consignor-tooltip">' +
                    tooltipContent +
                    '</div>');
                $('body').append($tooltip);

                const cellOffset = $(this).closest('.availability-cell').offset();
                const cellWidth = $(this).closest('.availability-cell').outerWidth();
                const tooltipWidth = $tooltip.outerWidth();

                // Center the tooltip above the cell and shift right by adding offset
                const leftPosition = cellOffset.left + (cellWidth / 2) - (tooltipWidth /
                        2) +
                    100; // Add 50px to shift right

                $tooltip.css({
                    position: 'absolute',
                    top: cellOffset.top - $tooltip.outerHeight() + 15,
                    left: leftPosition,
                    zIndex: 9999
                });
            });

            $element.on('mouseleave', function() {
                $('.custom-consignor-tooltip').remove();
            });
        });


        $(document).ready(function() {
            $('#status').on('change', function() {
                const label = $('#location-label');
                const dateContainer = $('#date-container');

                if ($(this).val() === 'available') {
                    // Change label text
                    label.text('First Location:');

                    dateContainer.html(`
                <label class="form-label fw-bold">Date Range:</label>
                <input type="text" name="date_range" id="date_range" class="form-control" required>
            `);

                    $('#date_range').daterangepicker({
                        locale: {
                            format: 'YYYY-MM-DD',
                            separator: ' to '
                        }
                    });
                } else {
                    // Revert back to single date picker
                    label.text('Location:');
                    dateContainer.html(`
                <label class="form-label fw-bold">Date:</label>
                <input type="date" name="date" class="form-control" required>
            `);
                }
            });
        });

        const toggle = document.getElementById('toggle14Days');

        // Handle 7/14 days toggle
        toggle.addEventListener('change', function() {
            const days = this.checked ? 14 : 7;
            const url = new URL(window.location.href);
            url.searchParams.set('days', days);
            window.location.href = url.toString();
        });

        // Handle Prev/Next navigation
        const prevBtn = document.getElementById('prevBtn');
        const nextBtn = document.getElementById('nextBtn');

        prevBtn.addEventListener('click', function() {
            navigateTo('{{ $prevStart }}');
        });

        nextBtn.addEventListener('click', function() {
            navigateTo('{{ $nextStart }}');
        });

        function navigateTo(startDate) {
            const url = new URL(window.location.href);
            url.searchParams.set('start_date', startDate);
            window.location.href = url.toString();
        }
    });
    $('#status').on('change', function() {
        if ($(this).val() === 'available') {
            $('#first-location').show();
        } else {
            $('#first-location').hide().find('select').val('');
        }
    });


    $(function() {
        $('#filter_daterange').daterangepicker({
            autoUpdateInput: false,
            locale: {
                cancelLabel: 'Clear'
            },
            maxSpan: {
                days: 14
            }, // limit range to 14 days
        });

        $('#filter_daterange').on('apply.daterangepicker', function(ev, picker) {
            const start = picker.startDate.format('YYYY-MM-DD');
            const end = picker.endDate.format('YYYY-MM-DD');
            $(this).val(start + ' to ' + end);

            // Redirect or filter calendar via GET parameters
            window.location.href = `?start_date=${start}&end_date=${end}`;
        });

        $('#filter_daterange').on('cancel.daterangepicker', function() {
            $(this).val('');
            window.location.href = `?`;
        });
    });
</script>
