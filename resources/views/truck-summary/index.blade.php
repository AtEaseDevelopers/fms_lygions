@extends('component.layout')
@section('title', 'Truck Summary')
@section('content')
    <div class="container-fluid">
        <div class="row">

            <!-- Truck Cards -->
            <div class="col-md-3">
                <div class="d-flex justify-content-between align-items-center flex-wrap mb-3">
                    <form id="filterForm" method="GET" action="{{ route('truck-summary.index') }}"
                        class="d-flex gap-3 align-items-stretch" style="width:100%;">
                        <!-- Date Filter (optional future use) -->
                        <div class="input-group">
                            <input type="date" name="date" class="form-control" value="{{ $selectedDate }}"
                                autocomplete="off" onchange="document.getElementById('filterForm').submit()">
                        </div>

                        <!-- Truck Group Dropdown -->
                        <div class="input-group">
                            <select id="logName" name="group" class="form-control"
                                onchange="document.getElementById('filterForm').submit()">
                                <option value="" {{ $selectedGroup ? '' : 'selected' }}>All Truck Groups</option>
                                @foreach ($trucks_grp as $grp)
                                    <option value="{{ $grp }}" {{ $selectedGroup == $grp ? 'selected' : '' }}>
                                        {{ $grp }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </form>


                </div>
                <h5 class="fw-bold">Truck Details</h5>

                @php
                    $sorted = collect($trucks)->filter(fn($t) => ($t['used'] ?? 0) > 0)->sortBy('used');

                    // Calculate totals
                    $totalUsed = $sorted->sum('used');
                    $totalSpace = $sorted->sum('space');
                    $overallUtilization = $totalSpace > 0 ? ($totalUsed / $totalSpace) * 100 : 0;
                    $totalUnused = $totalSpace - $totalUsed;
                    $totalColor = $overallUtilization >= 90 ? 'danger' : ($overallUtilization >= 70 ? 'warning' : 'success');
                @endphp

                <!-- Total Summary Card -->
                <div class="card mb-3 p-3 bg-light">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <strong>Capacity:</strong>
                            <strong class="text-{{ $totalColor }}">{{ number_format(max(0, $totalUnused), 2) }}</strong> /
                            {{ number_format($totalSpace, 2) }}
                        </div>
                        {{-- <div class="text-end">
                            <span class="badge bg-primary fs-6">{{ round($overallUtilization) }}% Utilized</span>
                        </div> --}}
                    </div>
                </div>


                @foreach ($sorted as $truck)
                    @php
                        $space = (float) ($truck['space'] ?? 0);
                        $balance = (float) ($truck['balance_capacity'] ?? 0);

                        // Used space = total - remaining
                        $used = max(0, $space - $balance);

                        // Utilization = used / total * 100
                        $utilization = $space > 0 ? ($used / $space) * 100 : 0;

                        // Match consignment color rules
                        $color = $utilization >= 90 ? 'danger' : ($utilization >= 70 ? 'warning' : 'success');
                    @endphp

                    <div class="card mb-3 p-3 truck-card" data-truck="{{ $truck['number'] }}">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="d-flex flex-column">
                                <!-- Truck Number -->
                                <h5 class="mb-2 fw-bold">{{ $truck['number'] }}</h5>

                                <!-- Capacity Info -->
                                <div>
                                    Capacity:
                                    <strong class="text-{{ $color }}">
                                        {{ number_format(max(0, $truck['balance_capacity']), 2) }}
                                    </strong> /
                                    {{ number_format($truck['space'], 0) }}
                                </div>
                            </div>

                            <div class="d-flex flex-column align-items-center text-end">
                                <div class="small mb-1">{{ $truck['group'] ?? '-' }}</div>

                                <i class="fa fa-truck fa-3x text-{{ $color }}"></i>
                                <div class="fw-bold text-{{ $color }}">{{ round($utilization) }}%</div>
                            </div>
                        </div>
                    </div>
                @endforeach




            </div>

            <!-- CSN Info -->
            <div class="col-md-9">
                <div class="d-flex justify-content-between align-items-center flex-wrap mb-3">
                    <form class="d-flex gap-3 align-items-stretch" style="width:100%;" id="filterForm">

                        <!-- Search Input -->
                        <div class="input-group no-border w-100">
                            <input type="text" class="form-control" placeholder="Search...">
                            <div class="input-group-append">
                                <span class="input-group-text">
                                    <i class="nc-icon nc-zoom-split"></i>
                                </span>
                            </div>
                        </div>
                        <div class="input-group">
                            <input type="date" class="form-control" placeholder="Filter Date Range" autocomplete="off">

                            </span>
                        </div>
                        <div class="input-group">
                            <select id="logName" name="logName" class="form-control">
                                <option value="A" selected disabled>Select Consignor</option>
                                <option value="A">A</option>
                                <option value="A">B</option>
                                <option value="A">B</option>
                            </select>
                        </div>
                        <div class="input-group">
                            <select id="logName" name="logName" class="form-control">
                                <option value="A" selected disabled>Select Consignee</option>
                                <option value="A">A</option>
                                <option value="A">B</option>
                                <option value="A">B</option>
                            </select>
                        </div>
                        <div class="input-group w-100">
                            <button type="reset" class="btn btn-danger d-none" style="margin: 0;" id="clearBtn"> <i
                                    class="bi bi-x"></i> </button>
                        </div>
                    </form>


                </div>
                <div id="csn-container">

                    <!-- JS will inject CSN info here -->
                </div>
            </div>
        </div>
    </div>

    <style>
        .truck-card.active {
            border: 2px solid #007bff;
            box-shadow: 0 0 5px rgba(0, 123, 255, 0.5);
        }

        .truck-card .card-body div>div {
            line-height: 1.8;
            /* or 2 for more spacing */
        }
    </style>

    <script>
        const csnData = @json($csns);

        function renderCSN(truckNumber) {
            const csns = csnData[truckNumber] || [];
            const container = document.getElementById('csn-container');
            container.innerHTML = '';

            if (csns.length === 0) {
                container.innerHTML = '<p class="text-muted">No CSNs available for this truck.</p>';
                return;
            }

            csns.forEach(csn => {
                container.innerHTML += `
            <div class="card mb-3 csn-card">
                <div class="card-header fw-bold">
                    CSN No.: ${csn.number}
                </div>
                <div class="card-body row g-3">
                    <div class="col-md-3"><label class="form-label">Load Date:</label><input type="text" class="form-control" value="${csn.load_date}" readonly></div>
                    <div class="col-md-3"><label class="form-label">Consignment No.:</label><input type="text" class="form-control" value="${csn.consignment_no}" readonly></div>
                    <div class="col-md-3"><label class="form-label">Consignor:</label><input type="text" class="form-control" value="${csn.consignor}" readonly></div>
                    <div class="col-md-3"><label class="form-label">Pick Point:</label><input type="text" class="form-control" value="${csn.pick_point}" readonly></div>
                    <div class="col-md-3"><label class="form-label">Consignee:</label><input type="text" class="form-control" value="${csn.consignee}" readonly></div>
                    <div class="col-md-3"><label class="form-label">Drop Point:</label><input type="text" class="form-control" value="${csn.drop_point}" readonly></div>
                    <div class="col-md-3"><label class="form-label">Pick Truck Size:</label><input type="text" class="form-control" value="${csn.pick_truck_size}" readonly></div>
                    <div class="col-md-3"><label class="form-label">Drop Truck Size:</label><input type="text" class="form-control" value="${csn.drop_truck_size}" readonly></div>
                    <div class="col-md-3"><label class="form-label">Remarks:</label><input type="text" class="form-control" value="${csn.remarks}" readonly></div>
                    <div class="col-md-3"><label class="form-label">Pick Truck Type:</label><input type="text" class="form-control" value="${csn.pick_truck_type}" readonly></div>
                    <div class="col-md-3"><label class="form-label">Drop Truck Type:</label><input type="text" class="form-control" value="${csn.drop_truck_type}" readonly></div>
                    <div class="col-md-3"><label class="form-label">Truck Number:</label><input type="text" class="form-control" value="${csn.truck_number}" readonly></div>
                    <div class="col-md-3"><label class="form-label">Status:</label><input type="text" class="form-control" value="${csn.status}" readonly></div>
                    <div class="col-md-2"><label class="form-label">Pick Time:</label><input type="text" class="form-control" value="${csn.pick_time}" readonly></div>
                    <div class="col-md-2"><label class="form-label">Quantity:</label><input type="text" class="form-control" value="${csn.quantity}" readonly></div>
                    <div class="col-md-2"><label class="form-label">Unit:</label><input type="text" class="form-control" value="${csn.unit}" readonly></div>
                    <div class="col-md-2"><label class="form-label">Pre-Pick:</label><input type="text" class="form-control" value="${csn.pre_pick}" readonly></div>
                    <div class="col-md-2"><label class="form-label">Space Usage:</label><input type="text" class="form-control" value="${csn.space_usage}" readonly></div>
                </div>
            </div>
        `;
            });
        }

        document.addEventListener('DOMContentLoaded', function() {
            const firstTruckCard = document.querySelector('.truck-card');
            if (firstTruckCard) {
                renderCSN(firstTruckCard.dataset.truck);
                firstTruckCard.classList.add('border-primary');
            }

            // Handle click on truck cards
            document.querySelectorAll('.truck-card').forEach(card => {
                card.addEventListener('click', () => {
                    document.querySelectorAll('.truck-card').forEach(c => c.classList.remove(
                        'active'));
                    card.classList.add('active');
                    const truckNumber = card.dataset.truck;
                    renderCSN(truckNumber);
                });
            });
        });
    </script>
@endsection
