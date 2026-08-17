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
                <input type="hidden" name="days" value="{{ $days }}">
                <input type="hidden" name="layout" value="{{ $layout ?? 'horizontal' }}">

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


            <div class="d-flex gap-2 ms-auto justify-content-end align-items-center">
                <!-- Toggle Switch Container -->
                <div class="form-check form-switch d-flex align-items-center">
                    <label class="slider-toggle mb-0">
                        <input type="checkbox" id="toggle14Days" {{ $days == 14 ? 'checked' : '' }}>
                        <span class="slider-text">{{ $days == 14 ? '14 Days' : '7 Days' }}</span>
                        <span class="slider-knob"></span>
                    </label>

                </div>

                @if ($days === 7)
                    <div class="form-check form-switch d-flex align-items-center">
                        <label class="slider-toggle mb-0">
                            <input type="checkbox" id="toggleVertical" {{ ($layout ?? 'horizontal') === 'vertical' ? 'checked' : '' }}>
                            <span class="slider-text">{{ ($layout ?? 'horizontal') === 'vertical' ? 'Vertical' : 'Horizontal' }}</span>
                            <span class="slider-knob"></span>
                        </label>
                    </div>
                @endif

                <button type="button" class="btn btn-outline-danger" id="toggleSelectMode"
                    style="border-radius: 0.2rem; display: inline-flex; align-items: center;">
                    <i class="bi bi-check2-square" style="font-size: 20px; margin-right: 5px;"></i>
                    Select to Delete
                </button>

                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#availabilityModal"
                    style="border-radius: 0.2rem; display: inline-flex; align-items: center;">
                    <i class="bi bi-calendar-plus-fill" style="font-size: 20px; margin-right: 5px;"></i>
                    Create New Availability
                </button>
            </div>
        </div>

        {{-- Floating action bar shown while in multi-select delete mode --}}
        <div id="bulkDeleteBar"
            class="d-none align-items-center gap-3 shadow position-fixed bottom-0 start-50 translate-middle-x mb-4 px-4 py-2 bg-white border rounded-pill"
            style="z-index: 1080;">
            <span class="fw-bold" id="bulkSelectedCount">0 selected</span>
            <button type="button" class="btn btn-danger btn-sm" id="bulkDeleteBtn" disabled>
                <i class="bi bi-trash-fill me-1"></i> Delete Selected
            </button>
            <button type="button" class="btn btn-outline-secondary btn-sm" id="bulkCancelBtn">Cancel</button>
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

        {{-- Colour legend: base states + special arrangements --}}
        <div class="d-flex flex-wrap justify-content-center gap-3 mb-3 small">
            @php
                $legendBase = [
                    ['label' => 'Available (MY)', 'color' => '#9ec5fe', 'border' => false],
                    ['label' => 'Available (SG)', 'color' => '#f1aeb5', 'border' => false],
                    ['label' => 'Off / Weekend', 'color' => '#c3c2c2', 'border' => false],
                    ['label' => 'Assigned (MY)', 'color' => '#f7c6c7', 'border' => false],
                    ['label' => 'Assigned (SG)', 'color' => '#d1ecf1', 'border' => false],
                ];
            @endphp
            @foreach ($legendBase as $item)
                <span class="d-inline-flex align-items-center gap-1">
                    <span style="display:inline-block; width:16px; height:16px; border-radius:3px;
                        background-color:{{ $item['color'] }}; @if ($item['border']) border:1px solid #adb5bd; @endif"></span>
                    {{ $item['label'] }}
                </span>
            @endforeach
            @foreach (\App\Models\Availability::arrangementProfiles() as $key => $profile)
                @continue(in_array($key, ['off-day', 'maintenance']))
                <span class="d-inline-flex align-items-center gap-1">
                    <span style="display:inline-block; width:16px; height:16px; border-radius:3px;
                        background-color:{{ $profile['color'] }};"></span>
                    {{ $profile['label'] }}
                </span>
            @endforeach
            <span class="d-inline-flex align-items-center gap-1">
                <span class="badge bg-danger" style="font-size:0.65rem;">On Leave</span>
                Driver Leave
            </span>
        </div>


        <div class="table-responsive">
            <table class="table text-left table-bordered " style="table-layout: fixed;">
                <thead>
                    @if (($layout ?? 'horizontal') === 'vertical')
                        <tr class="text-center small">
                            <th class="text-end">Lorries avail.</th>
                            @foreach ($dates as $date)
                                @php
                                    $truckHeaderKey = \Carbon\Carbon::parse($date['date'])->format('Y-m-d');
                                    $truckHeaderAvail = $availableTruckCounts[$truckHeaderKey] ?? ['MY' => 0, 'SG' => 0];
                                @endphp
                                <th colspan="2"
                                    title="MY {{ $truckHeaderAvail['MY'] }}, SG {{ $truckHeaderAvail['SG'] }} lorries available">
                                    <span class="fw-bold me-2" style="color:#0d6efd;">
                                        <i class="bi bi-truck"></i> MY {{ $truckHeaderAvail['MY'] }}
                                    </span>
                                    <span class="fw-bold" style="color:#dc3545;">
                                        <i class="bi bi-truck"></i> SG {{ $truckHeaderAvail['SG'] }}
                                    </span>
                                </th>
                            @endforeach
                        </tr>
                        <tr class="text-center small">
                            <th rowspan="2" class="align-middle">Truck Num</th>
                            @foreach ($dates as $date)
                                @php
                                    $truckHeaderKey = \Carbon\Carbon::parse($date['date'])->format('Y-m-d');
                                    $truckHeaderUsed = (float) ($truckDateMatrix[$truckHeaderKey]['used_capacity'] ?? 0);
                                @endphp
                                <th colspan="2">
                                    <div class="text-danger fw-bold">{{ number_format($truckHeaderUsed, 1) }}</div>
                                    <div><strong>{{ \Carbon\Carbon::parse($date['date'])->format('D') }}</strong></div>
                                    <div>{{ \Carbon\Carbon::parse($date['date'])->format('j/n') }}</div>
                                </th>
                            @endforeach
                        </tr>
                        <tr class="text-center small">
                            @foreach ($dates as $date)
                                @php
                                    $myUsed = $date['MY']['balance'] ?? 0;
                                    $sgUsed = $date['SG']['balance'] ?? 0;
                                    $myRate = ($date['MY']['origin'] ?? 0) > 0 ? ($myUsed / ($date['MY']['origin'] ?? 0)) * 100 : 0;
                                    $sgRate = ($date['SG']['origin'] ?? 0) > 0 ? ($sgUsed / ($date['SG']['origin'] ?? 0)) * 100 : 0;
                                    $myColor = $myRate > 70
                                        ? 'text-danger fw-bold'
                                        : ($myRate > 30 ? 'text-warning fw-bold' : 'text-success fw-bold');
                                    $sgColor = $sgRate > 70
                                        ? 'text-danger fw-bold'
                                        : ($sgRate > 30 ? 'text-warning fw-bold' : 'text-success fw-bold');
                                @endphp
                                <th>
                                    <div><strong>MY</strong></div>
                                    <span
                                        class="{{ ($date['MY']['balance'] ?? 0) > ($date['MY']['origin'] ?? 0) ? 'text-danger' : '' }} {{ $myColor }}">
                                        {{ number_format(($date['MY']['origin'] ?? 0) - ($date['MY']['balance'] ?? 0), 1) }}
                                        <span style="color: black">/
                                            {{ number_format($date['MY']['origin'] ?? 0, 1) }}</span>
                                    </span>
                                </th>
                                <th>
                                    <div><strong>SG</strong></div>
                                    <span
                                        class="{{ ($date['SG']['balance'] ?? 0) > ($date['SG']['origin'] ?? 0) ? 'text-danger' : '' }} {{ $sgColor }}">
                                        {{ number_format(($date['SG']['origin'] ?? 0) - ($date['SG']['balance'] ?? 0), 1) }}
                                        <span style="color: black">/
                                            {{ number_format($date['SG']['origin'] ?? 0, 1) }}</span>
                                    </span>
                                </th>
                            @endforeach
                        </tr>
                    @else
                        <tr class="text-center small">
                            <th colspan="2" class="text-end">Lorries avail.</th>
                            @foreach ($dates as $date)
                                @php
                                    $truckHeaderKey = \Carbon\Carbon::parse($date['date'])->format('Y-m-d');
                                    $truckHeaderAvail = $availableTruckCounts[$truckHeaderKey] ?? ['MY' => 0, 'SG' => 0];
                                @endphp
                                <th title="MY {{ $truckHeaderAvail['MY'] }}, SG {{ $truckHeaderAvail['SG'] }} lorries available">
                                    <div class="fw-bold" style="color:#0d6efd;">
                                        <i class="bi bi-truck"></i> MY {{ $truckHeaderAvail['MY'] }}
                                    </div>
                                    <div class="fw-bold" style="color:#dc3545;">
                                        <i class="bi bi-truck"></i> SG {{ $truckHeaderAvail['SG'] }}
                                    </div>
                                </th>
                            @endforeach
                        </tr>
                        <tr class="text-center small">
                            <th colspan="2"></th>
                            @foreach ($dates as $date)
                                @php
                                    $truckHeaderKey = \Carbon\Carbon::parse($date['date'])->format('Y-m-d');
                                    $truckHeaderUsed = (float) ($truckDateMatrix[$truckHeaderKey]['used_capacity'] ?? 0);
                                @endphp
                                <th class="text-danger fw-bold">
                                    {{ number_format($truckHeaderUsed, 1) }}
                                </th>
                            @endforeach
                        </tr>
                        <tr class="text-center">
                            <th rowspan="2">Truck Num</th>
                            <th rowspan="2">
                                Loc.</th>
                            @foreach ($dates as $date)
                                @php
                                    $myUsed = $date['MY']['balance'] ?? 0;
                                    $sgUsed = $date['SG']['balance'] ?? 0;
                                    $totalCapacity = ($date['MY']['origin'] ?? 0) + ($date['SG']['origin'] ?? 0);

                                    $myRate = ($date['MY']['origin'] ?? 0) > 0 ? ($myUsed / ($date['MY']['origin'] ?? 0)) * 100 : 0;
                                    $sgRate = ($date['SG']['origin'] ?? 0) > 0 ? ($sgUsed / ($date['SG']['origin'] ?? 0)) * 100 : 0;

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
                    @endif
                </thead>

                <tbody>

                    @if (($layout ?? 'horizontal') === 'vertical')
                        @foreach ($trucks as $truck)
                            <tr>
                                <td class="fs-5 align-middle"><strong>{{ $truck['number'] }}</strong></td>
                                @foreach ($calendarMatrix[$truck['number']] as $dayStatuses)
                                    @php
                                        $dateOnly = \Carbon\Carbon::parse($dates[$loop->index]['date'])->format('Y-m-d');
                                    @endphp
                                    @include('calendar._cell', [
                                        'cellData' => $dayStatuses['MY'],
                                        'truckNumber' => $truck['number'],
                                        'location' => 'MY',
                                        'dateOnly' => $dateOnly,
                                    ])
                                    @include('calendar._cell', [
                                        'cellData' => $dayStatuses['SG'],
                                        'truckNumber' => $truck['number'],
                                        'location' => 'SG',
                                        'dateOnly' => $dateOnly,
                                    ])
                                @endforeach
                            </tr>
                        @endforeach
                    @else
                        @foreach ($trucks as $truck)
                            {{-- MY row --}}
                            <tr>
                                <td rowspan="2" class="fs-5 align-middle"><strong>{{ $truck['number'] }}</strong></td>
                                <td><strong>MY</strong></td>
                                @foreach ($calendarMatrix[$truck['number']] as $dayStatuses)
                                    @php
                                        $dateOnly = \Carbon\Carbon::parse($dates[$loop->index]['date'])->format('Y-m-d');
                                    @endphp
                                    @include('calendar._cell', [
                                        'cellData' => $dayStatuses['MY'],
                                        'truckNumber' => $truck['number'],
                                        'location' => 'MY',
                                        'dateOnly' => $dateOnly,
                                    ])
                                @endforeach
                            </tr>

                            {{-- SG row --}}
                            <tr>
                                <td><strong>SG</strong></td>
                                @foreach ($calendarMatrix[$truck['number']] as $dayStatuses)
                                    @php
                                        $dateOnly = \Carbon\Carbon::parse($dates[$loop->index]['date'])->format('Y-m-d');
                                    @endphp
                                    @include('calendar._cell', [
                                        'cellData' => $dayStatuses['SG'],
                                        'truckNumber' => $truck['number'],
                                        'location' => 'SG',
                                        'dateOnly' => $dateOnly,
                                    ])
                                @endforeach
                            </tr>
                        @endforeach
                    @endif


                    @if ($totalVisibleTrucks === 0)
                        <tr>
                            <td colspan="{{ ($layout ?? 'horizontal') === 'vertical' ? count($dates) * 2 + 1 : count($dates) + 2 }}" class="text-center py-4 text-muted">
                                No truck activities found for this date range.
                            </td>
                        </tr>
                    @endif
                </tbody>



            </table>

        </div>

        {{-- Subcon Capacity (Temporary Trucks) --}}
        <div class="mt-4">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h5 class="mb-0 fw-bold">Subcon Capacity (Temporary Trucks)</h5>
                <small class="text-muted">Add temporary subcons via the <em>Create New Availability</em> form above.</small>
            </div>

            <div class="table-responsive">
                <table class="table text-left table-bordered" style="table-layout: fixed;">
                    <thead>
                        <tr class="text-center small">
                            <th colspan="3"></th>
                            @foreach ($dates as $date)
                                @php
                                    $tdHeaderKey = \Carbon\Carbon::parse($date['date'])->format('Y-m-d');
                                    $tdHeaderUsed = (float) ($tempDateMatrix[$tdHeaderKey]['used_capacity'] ?? 0);
                                @endphp
                                <th class="text-muted fw-normal">{{ number_format($tdHeaderUsed, 1) }}</th>
                            @endforeach
                        </tr>
                        <tr class="text-center">
                            <th style="width: 80px;">Loc.</th>
                            <th style="width: 100px;">Type</th>
                            <th style="width: 80px;">Size</th>
                            @foreach ($dates as $date)
                                @php
                                    $tdKey = \Carbon\Carbon::parse($date['date'])->format('Y-m-d');
                                    $td = $tempDateMatrix[$tdKey] ?? ['total_capacity' => 0, 'used_capacity' => 0];
                                    $tdTotal = (float) ($td['total_capacity'] ?? 0);
                                    $tdUsed = (float) ($td['used_capacity'] ?? 0);
                                    $tdRemaining = max(0, $tdTotal - $tdUsed);
                                    $tdRate = $tdTotal > 0 ? ($tdUsed / $tdTotal) * 100 : 0;
                                    $tdColor =
                                        $tdRate > 70
                                            ? 'text-danger fw-bold'
                                            : ($tdRate > 30
                                                ? 'text-warning fw-bold'
                                                : 'text-success fw-bold');
                                @endphp
                                <th>
                                    <div>
                                        <span class="{{ $tdColor }}">
                                            {{ number_format($tdRemaining, 1) }}
                                        </span>
                                    </div>
                                    <div><strong>{{ \Carbon\Carbon::parse($date['date'])->format('D') }}</strong></div>
                                    <div>{{ \Carbon\Carbon::parse($date['date'])->format('j/n') }}</div>
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @if (empty($tempMatrix))
                            <tr>
                                <td colspan="{{ count($dates) + 3 }}" class="text-center py-4 text-muted">
                                    No temporary subcon trucks for this date range.
                                </td>
                            </tr>
                        @else
                            @foreach ($tempMatrix as $label => $byRow)
                                @foreach ($byRow as $rowKey => $entry)
                                    @php $loc = $entry['location']; @endphp
                                    <tr>
                                        <td class="align-middle"><strong>{{ $loc }}</strong></td>
                                        <td class="align-middle">{{ $entry['meta']['chassis_type'] }}</td>
                                        <td class="align-middle">{{ $entry['meta']['size'] }}</td>
                                        @foreach ($dates as $date)
                                            @php
                                                $dateOnly = \Carbon\Carbon::parse($date['date'])->format('Y-m-d');
                                                $cell = $entry['cells'][$dateOnly] ?? null;
                                                $hasCell = (bool) $cell;
                                                $cellId = $cell['id'] ?? null;
                                                $count = $cell['consignment_count'] ?? 0;
                                                $consignors = $cell['consignors'] ?? collect();
                                                $cellSubconName = $cell['subcon_name'] ?? null;
                                                $hasSubcon = !empty($cell['subcon_id']);

                                                if ($hasCell) {
                                                    $bg = $loc === 'SG'
                                                        ? ($count > 0 ? '#d1ecf1' : '#f1aeb5')
                                                        : ($count > 0 ? '#f7c6c7' : '#9ec5fe');
                                                } else {
                                                    $bg = '#c3c2c2';
                                                }
                                            @endphp
                                            <td class="p-2 temp-truck-cell"
                                                style="background-color: {{ $bg }};"
                                                data-temp-id="{{ $cellId }}"
                                                data-has-cell="{{ $hasCell ? 'true' : 'false' }}">
                                                @if ($hasCell)
                                                    @if ($count > 0)
                                                        @php
                                                            $tempTooltip = '';
                                                            if ($consignors->isNotEmpty()) {
                                                                $tempTooltip .= '<ul class="mb-0 ps-3">';
                                                                foreach ($consignors as $cn) {
                                                                    $tempTooltip .= '<li>' . e($cn) . '</li>';
                                                                }
                                                                $tempTooltip .= '</ul>';
                                                            }
                                                        @endphp
                                                        <span class="badge bg-primary text-dark consignor-info"
                                                            data-bs-toggle="tooltip" data-bs-html="true"
                                                            data-bs-placement="top" title="{{ $tempTooltip }}">
                                                            {{ $count }} consignor{{ $count > 1 ? 's' : '' }}
                                                        </span>
                                                    @endif
                                                    @if ($hasSubcon)
                                                        <div class="mt-1 small">
                                                            <span class="badge bg-success">Subcon</span>
                                                            <span class="text-muted">{{ $cellSubconName }}</span>
                                                        </div>
                                                    @endif
                                                @endif
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            @endforeach
                        @endif
                    </tbody>
                </table>
            </div>
        </div>

        <div class="container mt-4">
            <div class="row g-3 justify-content-center">

                <!-- Truck Total -->
                <div class="col-12 col-md-2">
                    <div class="card shadow-sm border-0 rounded-3 p-3 position-relative hover-scale"
                        style="background-color: #ffffff;">
                        <div>
                            <div class="fw-bold fs-4 text-dark">{{ number_format($totalMyCapacity, 1) }}</div>
                            <div class="small">Truck Total</div>
                        </div>
                        <i class="bi bi-truck fs-2 position-absolute top-0 end-0 m-2" style="color: #f7c6c7;"></i>
                    </div>
                </div>

                <!-- Truck Unused -->
                <div class="col-12 col-md-2">
                    <div class="card shadow-sm border-0 rounded-3 p-3 position-relative hover-scale"
                        style="background-color: #ffffff">
                        <div>
                            <div class="fw-bold fs-4">{{ number_format($totalMyCapacity - $totalMyUsed, 1) }}</div>
                            <div class="small">Truck Unused</div>
                        </div>
                        <i class="bi bi-speedometer2 fs-2 position-absolute top-0 end-0 m-2"
                            style="color: #f7c6c7;"></i>
                    </div>
                </div>

                <!-- Truck Utilization -->
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
                            <div class="small">Truck Utilization</div>
                        </div>
                        <i class="bi bi-percent fs-2 position-absolute top-0 end-0 m-2" style="color: #f7c6c7;"></i>
                    </div>
                </div>

                <!-- Temp Subcon Total Capacity -->
                <div class="col-12 col-md-2">
                    <div class="card shadow-sm border-0 rounded-3 p-3 position-relative hover-scale"
                        style="background-color: #ffffff;">
                        <div>
                            <div class="fw-bold fs-4 text-dark">{{ number_format($tempTotalMy, 1) }}</div>
                            <div class="small">Temp Subcon Total</div>
                        </div>
                        <i class="bi bi-truck fs-2 position-absolute top-0 end-0 m-2" style="color: #c7e8b8;"></i>
                    </div>
                </div>

                <!-- Temp Subcon Unused -->
                <div class="col-12 col-md-2">
                    <div class="card shadow-sm border-0 rounded-3 p-3 position-relative hover-scale"
                        style="background-color: #ffffff">
                        <div>
                            <div class="fw-bold fs-4">{{ number_format(max(0, $tempTotalMy - $tempUsedMy), 1) }}</div>
                            <div class="small">Temp Subcon Unused</div>
                        </div>
                        <i class="bi bi-speedometer2 fs-2 position-absolute top-0 end-0 m-2"
                            style="color: #c7e8b8;"></i>
                    </div>
                </div>

                <!-- Temp Subcon Utilization -->
                <div class="col-12 col-md-2">
                    @php
                        $tempUtilColor = match (true) {
                            $tempUtilizationMy < 30 => 'text-success',
                            $tempUtilizationMy < 70 => 'text-warning',
                            default => 'text-danger',
                        };
                    @endphp
                    <div class="card shadow-sm border-0 rounded-3 p-3 position-relative hover-scale"
                        style="background-color: #ffffff;">
                        <div>
                            <div class="fw-bold fs-4 {{ $tempUtilColor }}">
                                {{ number_format($tempUtilizationMy, 2) }}%</div>
                            <div class="small">Temp Subcon Utilization</div>
                        </div>
                        <i class="bi bi-percent fs-2 position-absolute top-0 end-0 m-2" style="color: #c7e8b8;"></i>
                    </div>
                </div>

                <!-- Combined Total -->
                <div class="col-12 col-md-2">
                    <div class="card shadow-sm border-0 rounded-3 p-3 position-relative hover-scale"
                        style="background-color: #ffffff;">
                        <div>
                            <div class="fw-bold fs-4 text-dark">{{ number_format($combinedTotal, 1) }}</div>
                            <div class="small">Combined Total</div>
                        </div>
                        <i class="bi bi-bar-chart fs-2 position-absolute top-0 end-0 m-2" style="color: #d6c7e8;"></i>
                    </div>
                </div>

                <!-- Combined Unused -->
                <div class="col-12 col-md-2">
                    <div class="card shadow-sm border-0 rounded-3 p-3 position-relative hover-scale"
                        style="background-color: #ffffff;">
                        <div>
                            <div class="fw-bold fs-4">{{ number_format(max(0, $combinedTotal - $combinedUsed), 1) }}</div>
                            <div class="small">Combined Unused</div>
                        </div>
                        <i class="bi bi-speedometer2 fs-2 position-absolute top-0 end-0 m-2" style="color: #d6c7e8;"></i>
                    </div>
                </div>

                <!-- Combined Utilization -->
                <div class="col-12 col-md-2">
                    @php
                        $combinedUtilColor = match (true) {
                            $combinedUtilization < 30 => 'text-success',
                            $combinedUtilization < 70 => 'text-warning',
                            default => 'text-danger',
                        };
                    @endphp
                    <div class="card shadow-sm border-0 rounded-3 p-3 position-relative hover-scale"
                        style="background-color: #ffffff;">
                        <div>
                            <div class="fw-bold fs-4 {{ $combinedUtilColor }}">
                                {{ number_format($combinedUtilization, 2) }}%</div>
                            <div class="small">Combined Utilization</div>
                        </div>
                        <i class="bi bi-percent fs-2 position-absolute top-0 end-0 m-2" style="color: #d6c7e8;"></i>
                    </div>
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
        <div class="modal-dialog modal-dialog-centered modal-xl">
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

                        <div class="row g-2">
                            <!-- Column 1: Basic -->
                            <div class="col-lg-4">
                                <div class="border rounded p-3 h-100" style="background:#fafafa;">
                                    <h6 class="mb-3 fw-bold text-uppercase text-muted" style="font-size:0.8rem; letter-spacing:0.5px;">
                                        <i class="bi bi-info-circle me-1"></i> Basic
                                    </h6>

                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Status:</label>
                                        <select class="form-select" name="status" id="status" required>
                                            <option value="" disabled selected>-- Select Status --</option>
                                            <option value="available">Available</option>
                                            <option value="off-day">Off day</option>
                                            <option value="maintenance">Maintenance</option>
                                            <optgroup label="Special Arrangements">
                                                <option value="driver-leave">Driver Leave</option>
                                                <option value="holiday">Holiday</option>
                                                <option value="breakdown">Breakdown</option>
                                                <option value="express">Express</option>
                                                <option value="inspection">Inspection</option>
                                                <option value="saturday-loading">Saturday Loading (KL)</option>
                                                <option value="saturday-unloading">Saturday Unloading (SG)</option>
                                            </optgroup>
                                        </select>
                                    </div>

                                    <div class="mb-3" id="date-container">
                                        <label class="form-label fw-bold">Date:</label>
                                        <input type="text" name="arr_date_range" id="arr_date_range" class="form-control" placeholder="Pick a day or drag a range" autocomplete="off" required>
                                        <small class="text-muted">Pick one day, or drag to select a range (e.g. driver on leave for several days).</small>
                                    </div>

                                    <div class="mb-0">
                                        <label class="form-label fw-bold" id="location-label">Location:</label>
                                        <select class="form-select" name="location" required>
                                            <option value="" disabled selected>-- Select Location --</option>
                                            <option value="MY">MY</option>
                                            <option value="SG">SG</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- Column 2: Truck -->
                            <div class="col-lg-4">
                                <div class="border rounded p-3 h-100" style="background:#fafafa;">
                                    <h6 class="mb-3 fw-bold text-uppercase text-muted" style="font-size:0.8rem; letter-spacing:0.5px;">
                                        <i class="bi bi-truck me-1"></i> Truck
                                    </h6>

                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Truck Team:</label>
                                        <select class="form-select" name="team" id="teamSelect">
                                            <option value="" selected>-- Filter by Team (optional) --</option>
                                            <option value="MY">MY Team</option>
                                            <option value="SG">SG Team</option>
                                        </select>
                                    </div>
                                    <div class="mb-0">
                                        <label class="form-label fw-bold">Trucks:</label>
                                        <input type="text" id="truckSearch" class="form-control mb-2"
                                            placeholder="Search Truck Number...">

                                        <div class="mb-1">
                                            <input type="checkbox" id="selectAllTrucks" style="width:1.25em; height:1.25em; cursor:pointer; vertical-align:middle;">
                                            <label for="selectAllTrucks" style="cursor:pointer; vertical-align:middle; font-weight:600;">Select All</label>
                                        </div>
                                        <div id="truckCheckboxContainer" class="border rounded p-2"
                                            style="max-height: 220px; overflow-y: auto; background-color: #fff; display: grid; grid-template-columns: 1fr 1fr;">
                                            @foreach ($trucks_select as $truck)
                                                @continue(($truck['source'] ?? 'truck') !== 'truck')
                                                <div class="truck-item" data-team="{{ $truck['team'] }}"
                                                    data-number="{{ strtolower($truck['number']) }}"
                                                    style="display:flex; align-items:center; gap:0.5rem; padding: 2px 4px;">
                                                    <input type="checkbox" name="truck_numbers[]"
                                                        id="truck_{{ $truck['id'] }}" value="{{ $truck['number'] }}"
                                                        style="width:1.25em; height:1.25em; flex-shrink:0; cursor:pointer;">
                                                    <label class="mb-0" for="truck_{{ $truck['id'] }}" style="cursor:pointer; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                                                        {{ $truck['number'] }}
                                                        @if (!empty($truck['team']))
                                                            <small class="text-muted ms-1">{{ $truck['team'] }} Team</small>
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
                                </div>
                            </div>

                            <!-- Column 3: Subcons -->
                            <div class="col-lg-4">
                                <div id="tempSubconSection" class="border rounded p-3 h-100" style="background:#fafafa;">
                                    <h6 class="mb-1 fw-bold text-uppercase text-muted" style="font-size:0.8rem; letter-spacing:0.5px;">
                                        <i class="bi bi-people me-1"></i> Subcons (Temporary)
                                    </h6>
                                    <small class="text-muted d-block mb-3">Reserve placeholders by type/size; assign a real subcon later from the calendar.</small>

                                    <div id="tempSubconDateWrap" class="mb-3" style="display:none;">
                                        <label class="form-label fw-bold">Temp Subcon Date Range:</label>
                                        <input type="text" name="date_range" id="temp_date_range" class="form-control">
                                        <small class="text-muted">Only used for the temporary subcons below.</small>
                                    </div>

                                    <div id="tempSubconRows">
                                <div class="temp-subcon-row border rounded p-2 mb-2 position-relative" style="background:#fff; padding-right:32px !important;">
                                    <button type="button" class="btn btn-sm btn-link text-danger removeTempRow p-0"
                                        style="position:absolute; top:4px; right:8px; display:none; font-size:1.4rem; line-height:1; text-decoration:none; font-weight:bold;"
                                        aria-label="Remove row">&times;</button>
                                    <div class="row g-2 align-items-end">
                                        <div class="col-md-5">
                                            <label class="form-label">Truck Type</label>
                                            <select class="form-select form-select-sm temp-type" name="temp_chassis_type[]">
                                                <option value="">-- None --</option>
                                                <option value="any">Any</option>
                                                <option value="curtain">Curtain</option>
                                                <option value="open">Open</option>
                                                <option value="box">Box</option>
                                                <option value="tailgate">Tailgate</option>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Size</label>
                                            <select class="form-select form-select-sm temp-size" name="temp_size[]">
                                                <option value="">-- None --</option>
                                                <option value="Any">Any</option>
                                                <option value="Small">Small</option>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">Quantity</label>
                                            <input type="number" class="form-control form-control-sm temp-qty"
                                                name="temp_qty[]" min="0" max="20" value="0">
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label">Labels &amp; Floor Space</label>
                                            <div class="tempLabelInputs d-flex flex-column gap-1">
                                                {{-- Inputs rendered by JS based on quantity --}}
                                            </div>
                                            <small class="text-muted">Labels default to X1, X2, X3… continuing across rows — editable. Unique per (date, location). Floor space is per label (m²) and feeds the temp calendar's daily capacity total; leave blank to skip.</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                                    <button type="button" class="btn btn-sm btn-outline-primary addTempRow">
                                        + Add another type
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Footer (inside form) -->
                        <div class="modal-footer border-0">
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
        overflow: hidden;
    }

    /* Keep cell content (badges) inside the fixed-width cell instead of
       spilling over into neighbouring columns. */
    .availability-cell .badge {
        max-width: 100%;
        white-space: normal;
        word-break: break-word;
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
        width: 1.25em;
        height: 1.25em;
        float: none !important;
        position: static !important;
        margin-left: 0 !important;
        margin-top: 0 !important;
    }

    #truckCheckboxContainer .form-check-label {
        flex-grow: 1;
        min-width: 0;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    #truckCheckboxContainer .form-check {
        padding-left: 0 !important;
        min-width: 0;
    }

    /* Special-arrangement cells: click to delete, subtle hover cue */
    .arrangement-cell { cursor: pointer; }
    .arrangement-cell:hover { filter: brightness(0.92); }

    .availability-cell[draggable="true"] { cursor: grab; }
    .availability-cell.dragging { opacity: 0.4; }
    .availability-cell.drop-target-valid   { outline: 3px dashed #198754; outline-offset: -3px; }
    .availability-cell.drop-target-invalid { outline: 3px dashed #dc3545; outline-offset: -3px; cursor: not-allowed; }

    /* Multi-select delete mode: only 'available' cells are selectable */
    body.bulk-select-mode .availability-cell[data-status="available"] {
        cursor: pointer;
        outline: 1px dashed #adb5bd;
        outline-offset: -2px;
    }
    body.bulk-select-mode .availability-cell[data-status="available"]:hover {
        outline: 2px dashed #0d6efd;
        outline-offset: -2px;
    }
    .availability-cell.bulk-selected {
        outline: 3px solid #dc3545 !important;
        outline-offset: -3px;
        box-shadow: inset 0 0 0 9999px rgba(220, 53, 69, 0.18);
        position: relative;
    }
    .availability-cell.bulk-selected::after {
        content: "\2713";
        position: absolute;
        top: 2px;
        left: 4px;
        font-weight: bold;
        color: #dc3545;
        line-height: 1;
    }
    #bulkDeleteBar { gap: 1rem; }
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
        // Availability statuses that render as a deletable "special arrangement" cell.
        const ARRANGEMENT_STATUSES = ['off-day', 'maintenance', 'holiday', 'breakdown',
            'express', 'inspection', 'saturday-loading', 'saturday-unloading'];
        const teamSelect = document.getElementById('teamSelect');
        const searchInput = document.getElementById('truckSearch');
        const truckItems = document.querySelectorAll('.truck-item');
        const selectedCountLabel = document.getElementById('selectedCount');
        const selectedList = document.getElementById('selectedList');
        const selectedTrucks = [];

        function updateSelectedCount() {
            const checkedBoxes = document.querySelectorAll('.truck-item input[type="checkbox"]:checked');
            selectedCountLabel.textContent = `${checkedBoxes.length} selected`;

            // Rebuild selectedTrucks from currently checked boxes
            const checkedValues = [...checkedBoxes].map(cb => cb.value);
            selectedTrucks.length = 0;
            checkedValues.forEach(val => selectedTrucks.push(val));

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

        // Select All
        const selectAllCheckbox = document.getElementById('selectAllTrucks');
        selectAllCheckbox.addEventListener('change', function() {
            const isChecked = this.checked;
            truckItems.forEach(item => {
                if (item.style.display !== 'none') {
                    item.querySelector('input[type="checkbox"]').checked = isChecked;
                }
            });
            updateSelectedCount();
            updateLabelColors();
        });

        // Uncheck "Select All" when modal opens
        document.getElementById('availabilityModal').addEventListener('show.bs.modal', function() {
            selectAllCheckbox.checked = false;
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

        // --- Drag-and-drop: move all consignments from one cell to another ---
        let dragState = null;
        let suppressClickUntil = 0;

        // Bind BEFORE the modal-opening click handler so we can swallow the synthetic
        // click that follows a drop. stopImmediatePropagation is required because the
        // modal handler is also delegated on the same selector.
        $(document).on('click', '.availability-cell', function(e) {
            if (Date.now() < suppressClickUntil) {
                e.stopImmediatePropagation();
                return false;
            }
        });

        // --- Multi-select bulk delete of 'available' cells ---
        const bulkSelected = new Map(); // key -> {truck, location, date}
        const bulkBar = document.getElementById('bulkDeleteBar');
        const bulkCountLabel = document.getElementById('bulkSelectedCount');
        const bulkDeleteBtn = document.getElementById('bulkDeleteBtn');
        const cellKey = (t, l, d) => `${t}|${l}|${d}`;

        function isSelectMode() {
            return document.body.classList.contains('bulk-select-mode');
        }

        function refreshBulkBar() {
            const n = bulkSelected.size;
            bulkCountLabel.textContent = `${n} selected`;
            bulkDeleteBtn.disabled = n === 0;
        }

        function clearBulkSelection() {
            bulkSelected.clear();
            $('.availability-cell.bulk-selected').removeClass('bulk-selected');
            refreshBulkBar();
        }

        function setSelectMode(on) {
            document.body.classList.toggle('bulk-select-mode', on);
            bulkBar.classList.toggle('d-none', !on);
            bulkBar.classList.toggle('d-flex', on);
            const btn = document.getElementById('toggleSelectMode');
            btn.classList.toggle('btn-danger', on);
            btn.classList.toggle('btn-outline-danger', !on);
            if (!on) clearBulkSelection();
        }

        document.getElementById('toggleSelectMode').addEventListener('click', function() {
            setSelectMode(!isSelectMode());
        });
        document.getElementById('bulkCancelBtn').addEventListener('click', function() {
            setSelectMode(false);
        });

        // Intercept clicks on 'available' cells while in select mode (bound before the
        // modal-opening handler so the modal never fires during selection).
        $(document).on('click', '.availability-cell', function(e) {
            if (!isSelectMode()) return;
            // While selecting, no cell should open its modal or single-delete popup.
            e.stopImmediatePropagation();
            const $cell = $(this);
            if ($cell.data('status') !== 'available') return; // only available cells selectable
            const truck = String($cell.data('truck'));
            const location = String($cell.data('location'));
            const date = String($cell.data('date'));
            const key = cellKey(truck, location, date);
            if (bulkSelected.has(key)) {
                bulkSelected.delete(key);
                $cell.removeClass('bulk-selected');
            } else {
                bulkSelected.set(key, { truck, location, date });
                $cell.addClass('bulk-selected');
            }
            refreshBulkBar();
        });

        bulkDeleteBtn.addEventListener('click', function() {
            if (bulkSelected.size === 0) return;
            const items = Array.from(bulkSelected.values());
            Swal.fire({
                title: 'Delete selected availability?',
                text: `${items.length} available record(s) will be permanently deleted.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, delete them!',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (!result.isConfirmed) return;
                fetch("{{ route('availability.bulk-delete') }}", {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json',
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({ items })
                    })
                    .then(async res => {
                        const json = await res.json();
                        if (!res.ok) throw new Error(json.message || 'Delete failed');
                        return json;
                    })
                    .then(data => {
                        if (data?.success) {
                            Swal.fire({
                                title: 'Deleted!',
                                text: data.message || 'Selected availability has been deleted.',
                                icon: 'success',
                                timer: 1500,
                                showConfirmButton: false
                            }).then(() => window.location.reload());
                        }
                    })
                    .catch(err => {
                        console.error('Bulk delete error:', err);
                        Swal.fire('Error', err.message || 'Failed to delete availability', 'error');
                    });
            });
        });
        // --- end multi-select bulk delete ---

        $(document).on('dragstart', '.availability-cell[draggable="true"]', function(e) {
            // In bulk-select mode a click should select, not start a drag.
            if (document.body.classList.contains('bulk-select-mode')) {
                e.preventDefault();
                return;
            }
            const $c = $(this);
            // Two drag kinds: an occupied cell moves its consignments; a white
            // 'available' cell (no consignors) moves the availability record itself.
            let kind = null;
            if ($c.attr('data-has-consignors') === 'true') {
                kind = 'consignment';
            } else if ($c.attr('data-status') === 'available') {
                kind = 'availability';
            }
            if (!kind) { e.preventDefault(); return; }
            dragState = {
                kind,
                truck:    $c.attr('data-truck'),
                date:     $c.attr('data-date'),
                location: $c.attr('data-location'),
                el:       this,
            };
            e.originalEvent.dataTransfer.effectAllowed = 'move';
            e.originalEvent.dataTransfer.setData('text/plain', 'move'); // Firefox needs a payload
            $c.addClass('dragging');
        });

        $(document).on('dragover', '.availability-cell', function(e) {
            if (!dragState) return;
            e.preventDefault();
            e.originalEvent.dataTransfer.dropEffect = 'move';
            const status = $(this).attr('data-status');
            const targetTruck = $(this).attr('data-truck');
            const targetLoc = $(this).attr('data-location');
            const targetHasCons = $(this).attr('data-has-consignors') === 'true';
            const crossTruck = targetTruck !== dragState.truck;
            const crossLoc   = targetLoc !== dragState.location;
            let invalid;
            if (dragState.kind === 'availability') {
                // Availability can only be relocated onto a free (empty) cell of the
                // same truck + location.
                invalid = (this === dragState.el)
                    || crossTruck
                    || crossLoc
                    || targetHasCons
                    || status !== 'empty';
            } else {
                invalid = (this === dragState.el)
                    || ARRANGEMENT_STATUSES.includes(status)
                    || crossTruck
                    || crossLoc;
            }
            $(this).toggleClass('drop-target-valid', !invalid)
                   .toggleClass('drop-target-invalid', invalid);
        });

        $(document).on('dragleave', '.availability-cell', function() {
            $(this).removeClass('drop-target-valid drop-target-invalid');
        });

        $(document).on('dragend', '.availability-cell', function() {
            $('.availability-cell').removeClass('dragging drop-target-valid drop-target-invalid');
            suppressClickUntil = Date.now() + 300;
            dragState = null;
        });

        $(document).on('drop', '.availability-cell', function(e) {
            e.preventDefault();
            if (!dragState) return;
            const src = { truck: dragState.truck, date: dragState.date, location: dragState.location };
            const tgt = {
                truck:    $(this).attr('data-truck'),
                date:     $(this).attr('data-date'),
                location: $(this).attr('data-location'),
            };
            if (src.truck === tgt.truck && src.date === tgt.date && src.location === tgt.location) return;
            if (src.truck !== tgt.truck) {
                Swal.fire({
                    icon: 'info',
                    title: 'Different truck',
                    text: 'Drag-drop can only reassign within the same truck. Use the cell modal to move consignments between trucks.',
                });
                return;
            }
            if (src.location !== tgt.location) {
                Swal.fire({
                    icon: 'info',
                    title: 'Different location',
                    text: 'Drag-drop can only shift dates. Use the cell modal to change MY/SG.',
                });
                return;
            }

            // Posts the drop to `route`, shows a success toast and reloads.
            const postMove = (route, payload, successTitle) => {
                fetch(route, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify(payload),
                }).then(async res => {
                    const body = await res.json().catch(() => ({}));
                    if (!res.ok) throw new Error(body.message || 'Move failed.');
                    await Swal.fire({
                        icon: 'success',
                        title: successTitle,
                        text: body.message,
                        timer: 1200,
                        showConfirmButton: false,
                    });
                    window.location.reload();
                }).catch(err => Swal.fire({ icon: 'error', title: 'Cannot move', text: err.message }));
            };

            if (dragState.kind === 'availability') {
                const targetHasCons = $(this).attr('data-has-consignors') === 'true';
                if (targetHasCons || $(this).attr('data-status') !== 'empty') {
                    Swal.fire({
                        icon: 'info',
                        title: 'Cell not empty',
                        text: 'Availability can only be moved onto an empty cell.',
                    });
                    return;
                }
                Swal.fire({
                    title: 'Move availability?',
                    html: `From <b>${src.truck}</b> ${src.date} ${src.location}<br>to <b>${tgt.truck}</b> ${tgt.date} ${tgt.location}`,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Move',
                }).then(r => {
                    if (!r.isConfirmed) return;
                    postMove("{{ route('calendar.move-availability') }}", {
                        source_truck: src.truck, source_date: src.date, source_location: src.location,
                        target_truck: tgt.truck, target_date: tgt.date, target_location: tgt.location,
                    }, 'Moved');
                });
                return;
            }

            Swal.fire({
                title: 'Move consignments?',
                html: `From <b>${src.truck}</b> ${src.date} ${src.location}<br>to <b>${tgt.truck}</b> ${tgt.date} ${tgt.location}`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Move',
            }).then(r => {
                if (!r.isConfirmed) return;
                postMove("{{ route('calendar.move-cell') }}", {
                    source_truck: src.truck, source_date: src.date, source_location: src.location,
                    target_truck: tgt.truck, target_date: tgt.date, target_location: tgt.location,
                }, 'Moved');
            });
        });
        // --- end drag-and-drop ---

        $(document).on('click', '.availability-cell', function(e) {
            const $cell = $(this);
            const status = $cell.data('status');
            const truck = $cell.data('truck');
            const location = $cell.data('location');
            const date = $cell.data('date');
            const hasConsignors = $cell.data('has-consignors') === true || $cell.data(
                'has-consignors') === 'true';

            if (ARRANGEMENT_STATUSES.includes(status)) {
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

                const $parentCell = $(this).closest('.availability-cell, .temp-truck-cell');
                const cellOffset = $parentCell.offset();
                const cellWidth = $parentCell.outerWidth();
                const tooltipWidth = $tooltip.outerWidth();
                if (!cellOffset) return;

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


        // Attach a single-or-range date picker to the arrangement date field.
        // One day = pick the same start/end; a range covers several days (e.g. leave).
        function initArrangementRange() {
            const $inp = $('#arr_date_range');
            if (!$inp.length) return;
            $inp.daterangepicker({
                autoUpdateInput: false,
                locale: {
                    format: 'YYYY-MM-DD',
                    separator: ' to ',
                    cancelLabel: 'Clear'
                }
            });
            $inp.on('apply.daterangepicker', function(ev, picker) {
                const start = picker.startDate.format('YYYY-MM-DD');
                const end = picker.endDate.format('YYYY-MM-DD');
                $(this).val(start === end ? start : start + ' to ' + end);
            });
            $inp.on('cancel.daterangepicker', function() {
                $(this).val('');
            });
        }

        $(document).ready(function() {
            // Temp subcons keep their own date range (unchanged). Initialise once.
            $('#temp_date_range').daterangepicker({
                autoUpdateInput: false,
                locale: {
                    format: 'YYYY-MM-DD',
                    separator: ' to ',
                    cancelLabel: 'Clear'
                }
            });
            $('#temp_date_range').on('apply.daterangepicker', function(ev, picker) {
                $(this).val(picker.startDate.format('YYYY-MM-DD') + ' to ' + picker.endDate.format('YYYY-MM-DD'));
            });
            $('#temp_date_range').on('cancel.daterangepicker', function() {
                $(this).val('');
            });

            // Arrangement date field starts as a single-or-range picker.
            initArrangementRange();

            $('#status').on('change', function() {
                const label = $('#location-label');
                const dateContainer = $('#date-container');

                if ($(this).val() === 'available') {
                    // Availability is created per-month for weekdays only, alternating MY/SG.
                    label.text('First Location:');

                    dateContainer.html(`
                <label class="form-label fw-bold">Availability Month:</label>
                <input type="month" name="month" id="avail_month" class="form-control" required>
                <small class="text-muted">All weekdays in the month are marked available, alternating MY/SG from the first location.</small>
            `);

                    // Temp subcons need their own date range only when creating availability.
                    $('#tempSubconDateWrap').show();
                } else {
                    // Revert back to the single-or-range date picker.
                    label.text('Location:');
                    dateContainer.html(`
                <label class="form-label fw-bold">Date:</label>
                <input type="text" name="arr_date_range" id="arr_date_range" class="form-control" placeholder="Pick a day or drag a range" autocomplete="off" required>
                <small class="text-muted">Pick one day, or drag to select a range (e.g. driver on leave for several days).</small>
            `);
                    initArrangementRange();
                    $('#tempSubconDateWrap').hide();
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

        // Handle Horizontal/Vertical MY-SG layout toggle (7-day mode only)
        document.getElementById('toggleVertical')?.addEventListener('change', function() {
            const url = new URL(window.location.href);
            url.searchParams.set('layout', this.checked ? 'vertical' : 'horizontal');
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

            const url = new URL(window.location.href);
            url.searchParams.set('start_date', start);
            url.searchParams.set('end_date', end);
            window.location.href = url.toString();
        });

        $('#filter_daterange').on('cancel.daterangepicker', function() {
            $(this).val('');
            const current = new URL(window.location.href);
            const url = new URL(current.pathname, current.origin);
            const days = current.searchParams.get('days');
            const layout = current.searchParams.get('layout');
            if (days) url.searchParams.set('days', days);
            if (layout) url.searchParams.set('layout', layout);
            window.location.href = url.toString();
        });
    });

    // ========== Temporary Subcons (merged into Availability modal) ==========
    $(function() {
        const $rowsContainer = $('#tempSubconRows');

        function renderAllLabels() {
            let offset = 0;
            $rowsContainer.find('.temp-subcon-row').each(function(rowIdx) {
                const $row = $(this);
                const $qty = $row.find('.temp-qty');
                const $labels = $row.find('.tempLabelInputs');
                const qty = Math.max(0, Math.min(20, parseInt($qty.val(), 10) || 0));

                const existingLabels = $labels.find('input.temp-label-input').map(function() {
                    return $(this).val();
                }).get();
                const existingFs = $labels.find('input.temp-fs-input').map(function() {
                    return $(this).val();
                }).get();

                $labels.empty();
                for (let i = 0; i < qty; i++) {
                    const defaultLabel = existingLabels[i] && existingLabels[i].trim() !== '' ?
                        existingLabels[i] :
                        ('X' + (offset + i + 1));
                    const $pair = $('<div>', {
                        class: 'd-flex gap-2 align-items-center',
                    });
                    const $labelInput = $('<input>', {
                        type: 'text',
                        name: 'labels[' + rowIdx + '][]',
                        class: 'form-control form-control-sm temp-label-input',
                        placeholder: 'Label ' + (offset + i + 1),
                        value: defaultLabel,
                        maxlength: 50,
                        required: true,
                        style: 'width: 110px;',
                    });
                    const $fsInput = $('<input>', {
                        type: 'number',
                        name: 'floor_space[' + rowIdx + '][]',
                        class: 'form-control form-control-sm temp-fs-input',
                        placeholder: 'm²',
                        value: existingFs[i] != null ? existingFs[i] : '',
                        min: '0',
                        step: '0.01',
                        style: 'width: 110px;',
                    });
                    $pair.append($labelInput).append($fsInput);
                    $labels.append($pair);
                }
                offset += qty;
            });
        }

        function reindexRowNames() {
            $rowsContainer.find('.temp-subcon-row').each(function(rowIdx) {
                $(this).find('.tempLabelInputs input.temp-label-input').each(function() {
                    $(this).attr('name', 'labels[' + rowIdx + '][]');
                });
                $(this).find('.tempLabelInputs input.temp-fs-input').each(function() {
                    $(this).attr('name', 'floor_space[' + rowIdx + '][]');
                });
            });
        }

        function syncRemoveButtons() {
            const $rows = $rowsContainer.find('.temp-subcon-row');
            const showRemove = $rows.length > 1;
            $rows.find('.removeTempRow').css('display', showRemove ? '' : 'none');
        }

        // Qty change → re-render all labels (because numbering is continuous)
        $rowsContainer.on('input change', '.temp-qty', renderAllLabels);

        // Add a new row
        $('#tempSubconSection').on('click', '.addTempRow', function() {
            const $first = $rowsContainer.find('.temp-subcon-row').first();
            const $clone = $first.clone();
            $clone.find('select').prop('selectedIndex', 0);
            $clone.find('.temp-qty').val(0);
            $clone.find('.tempLabelInputs').empty();
            $rowsContainer.append($clone);
            reindexRowNames();
            renderAllLabels();
            syncRemoveButtons();
        });

        // Remove a row
        $rowsContainer.on('click', '.removeTempRow', function() {
            const $rows = $rowsContainer.find('.temp-subcon-row');
            if ($rows.length <= 1) return;
            $(this).closest('.temp-subcon-row').remove();
            reindexRowNames();
            renderAllLabels();
            syncRemoveButtons();
        });

        $('#availabilityModal').on('show.bs.modal', function() {
            renderAllLabels();
            syncRemoveButtons();
        });

        // Click on a temp-truck cell → open detail modal (reuse #cellInfoModal)
        $(document).on('click', '.temp-truck-cell', function() {
            const $cell = $(this);
            if ($cell.data('has-cell') !== true && $cell.data('has-cell') !== 'true') return;

            const tempId = $cell.data('temp-id');
            if (!tempId) return;

            const modal = new bootstrap.Modal(document.getElementById('cellInfoModal'));
            const content = document.getElementById('cellInfoContent');
            content.innerHTML =
                '<div class="p-4 text-center"><div class="spinner-border" role="status"></div></div>';
            modal.show();

            const params = new URLSearchParams({ temp_truck_id: tempId });
            fetch(`/calendar/cell-details?${params.toString()}`)
                .then(res => res.text())
                .then(html => content.innerHTML = html)
                .catch(() => content.innerHTML =
                    '<div class="p-3 text-danger">Failed to load details.</div>');
        });

        // Assign / Unassign subcon for a temp truck (delegated — partial is loaded via innerHTML)
        function postAssignSubcon(tempId, subconId) {
            fetch(`/calendar/temp-trucks/${encodeURIComponent(tempId)}/assign-subcon`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ subcon_id: subconId || null }),
            })
            .then(async (res) => {
                const data = await res.json().catch(() => ({}));
                if (!res.ok || !data.ok) {
                    throw new Error(data.message || 'Assignment failed');
                }
                return data;
            })
            .then((data) => {
                Swal.fire({
                    title: 'Saved',
                    text: data.message || 'Subcon assignment updated.',
                    icon: 'success',
                    timer: 1400,
                    showConfirmButton: false,
                }).then(() => window.location.reload());
            })
            .catch((err) => {
                Swal.fire('Error', err.message || 'Assignment failed', 'error');
            });
        }

        $(document).on('submit', '#assignSubconForm', function(e) {
            e.preventDefault();
            const root = document.getElementById('tempCellDetails');
            if (!root) return;
            const tempId = root.dataset.tempId;
            const subconId = $('#assignSubconSelect').val();
            if (!subconId) {
                Swal.fire('Select a subcon', 'Please pick a subcon first.', 'info');
                return;
            }
            postAssignSubcon(tempId, subconId);
        });

        $(document).on('click', '#unassignSubconBtn', function() {
            const root = document.getElementById('tempCellDetails');
            if (!root) return;
            const tempId = root.dataset.tempId;
            Swal.fire({
                title: 'Unassign subcon?',
                text: 'The label will keep its current value but lose the subcon link.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, unassign',
                confirmButtonColor: '#d33',
            }).then((r) => {
                if (r.isConfirmed) postAssignSubcon(tempId, null);
            });
        });
    });
</script>
