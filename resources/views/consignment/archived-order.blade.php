@extends('component.layout')
@section('title', 'Archived Consignment Order')
@section('content')

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
                            <!-- Left Buttons -->
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-primary" data-bs-toggle="modal"
                                    data-bs-target="#addCsnModal"
                                    style="border-radius: 0.2rem; display: inline-flex; align-items: center;" hidden>
                                    <i class="bi bi-clipboard2-plus-fill me-1" style="font-size: 20px;"></i>
                                    Create New Consignment Order
                                </button>
                                <button type="button" class="btn btn-outline-warning"
                                    style="border-radius: 0.2rem; display: inline-flex; align-items: center;" hidden>
                                    <i class="bi bi-archive me-1" style="font-size: 20px;"></i>
                                    View Archived CSN
                                </button>
                            </div>


                        </div>

                    </div>
                    <div id="tableScrollTop" style="overflow-x:auto; overflow-y:hidden;"></div>
                    <div id="tableScrollBottom" style="overflow-x:auto;">
                        <table class="table table-sm table-striped table-bordered align-middle table-nowrap text-center"
                            style="font-size: 0.85rem; border-collapse: collapse;">
                            <thead>
                                <tr>
                                    <th class="sticky-col"></th>
                                    <th class="sticky-col">No</th>
                                    <th class="sticky-col">
                                        Load Date
                                    </th>
                                    <th class="sticky-col">Consignment Order No.</th>
                                    <th class="sticky-col">Consignor</th>
                                    <th class="sticky-col">Pick Point</th>
                                    <th class="sticky-col">Consignee</th>
                                    <th class="sticky-col">Drop Point</th>
                                    <th>Pick Truck</th>
                                    <th>Drop Truck</th>
                                    <th>Pick Time</th>
                                    <th>Quantity</th>
                                    <th>Unit</th>
                                    <th>Pre-Pick</th>
                                    <th>Truck Type</th>
                                    <th>Truck Number</th>
                                    <th>Remarks</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>

                            <tbody>
                                @forelse ($consignments as $index => $order)
                                    @php
                                        // Convert model to array for editing modal
                                        $orderArray = $order->toArray();
                                        $orderArray['index'] = $index;

                                        // Handle JSON fields safely
                                        $quantities = is_string($order->quantity)
                                            ? json_decode($order->quantity, true)
                                            : $order->quantity ?? [];
                                        $units = is_string($order->unit)
                                            ? json_decode($order->unit, true)
                                            : $order->unit ?? [];
                                    @endphp
                                    <tr>
                                        <td class="sticky-col">
                                            <div class="d-flex align-items-center justify-content-center">
                                                <input type="checkbox">
                                            </div>
                                        </td>
                                        <td class="sticky-col">{{ $loop->iteration }}</td>
                                        <td class="sticky-col">{{ $order->load_date }}</td>
                                        <td class="sticky-col">{{ $order->consignment_no ?? '-' }}</td>
                                        <td class="sticky-col">{{ $order->consignor ?? '-' }}</td>
                                        <td class="sticky-col">{{ $order->pick_point ?? '-' }}</td>
                                        <td class="sticky-col">{{ $order->consignee ?? '-' }}</td>
                                        <td class="sticky-col">{{ $order->drop_point ?? '-' }}</td>
                                        <td>{{ $order->pick_truck ?? '-' }}</td>
                                        <td>{{ $order->drop_truck ?? '-' }}</td>
                                        <td>{{ $order->pick_time ?? '-' }}</td>

                                        {{-- Quantity --}}
                                        <td>
                                            @forelse ($quantities as $qty)
                                                {{ $qty }}<br>
                                            @empty
                                                -
                                            @endforelse
                                        </td>

                                        {{-- Unit --}}
                                        <td>
                                            @forelse ($units as $unit)
                                                {{ $unit }}<br>
                                            @empty
                                                -
                                            @endforelse
                                        </td>

                                        <td>{{ $order->pre_pick ?? '-' }}</td>

                                        {{-- Truck Type --}}
                                        <td>
                                            <select class="form-select form-select-sm auto-width">
                                                <option value="">-</option>
                                                @foreach (['SNL 20#', 'SNL 40#', 'Sub-Con'] as $type)
                                                    <option value="{{ $type }}"
                                                        {{ $order->truck_type == $type ? 'selected' : '' }}>
                                                        {{ $type }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </td>

                                        {{-- Truck Number --}}
                                        <td>
                                            <select class="form-select form-select-sm auto-width">
                                                <option value="">-</option>
                                                @foreach (['VKY 1827', 'VHY 7839', 'SC-29-0'] as $truck_number)
                                                    <option value="{{ $truck_number }}"
                                                        {{ $order->truck_number == $truck_number ? 'selected' : '' }}>
                                                        {{ $truck_number }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </td>

                                        <td>{{ $order->remarks ?? '-' }}</td>

                                        {{-- Status --}}
                                        <td
                                            class="@if ($order->status == 'Completed') bg-success text-white
                       @elseif($order->status == 'Planning') bg-warning text-dark
                       @else bg-secondary text-white @endif">
                                            {{ $order->status }}
                                        </td>

                                        {{-- Actions --}}
                                        <td>
                                            <div class="d-flex align-items-center justify-content-center gap-2">
                                                <button type="button" class="btn btn-info" data-bs-toggle="modal"
                                                    data-bs-target="#editModal{{ $index }}">
                                                    <i class="bi bi-pencil-square"></i>
                                                </button>
                                            </div>

                                            {{-- Pass array to edit modal --}}
                                            @include('consignment.edit-order', ['order' => $orderArray])
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="19" class="text-center">No archived consignment orders found.</td>
                                    </tr>
                                @endforelse
                            </tbody>

                        </table>
                    </div>


                </div>
            </div>
        </div>



    </div>
    <!-- Modal -->
    <div class="modal fade" id="addCsnModal" tabindex="-1" aria-labelledby="csnModalLabel" aria-hidden="true">
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
    </div>
@endsection
<style>
    .truck-card.active {
        border: 2px solid #007bff;
        box-shadow: 0 0 5px rgba(0, 123, 255, 0.5);
    }

    .truck-card .card-body div>div {
        line-height: 1.8;
        /* or 2 for more spacing */
    }

    .table-nowrap td,
    .table-nowrap th {
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 200px;
        /* Optional: limit max width */
    }

    .table-responsive {
        overflow-x: auto;
    }

    table.table td,
    table.table th {
        padding: 0.25rem 0.4rem !important;
        /* You can make this even smaller if needed */
    }
</style>
