@extends('component.layout')
@section('title', 'Driver Leave Plan')
@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center"></div>

                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center flex-wrap mb-3">
                        <!-- Search Form -->
                        <form class="d-flex" style="width: 50%;" id="filterForm">
                            <div class="input-group no-border w-100">
                                <input type="text" value="" class="form-control" placeholder="Search...">
                                <div class="input-group-append">
                                    <div class="input-group-text">
                                        <i class="nc-icon nc-zoom-split"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="input-group w-100 mx-3">
                                <button type="reset" class="btn btn-danger d-none" style="margin: 0;" id="clearBtn">
                                    <i class="bi bi-x"></i>
                                </button>
                            </div>
                        </form>

                        <!-- Buttons -->
                        <div class="d-flex gap-2">
                            <button class="btn btn-outline-primary"
                                style="border-radius: 0.2rem; display: inline-flex; align-items: center;">
                                <i class="bi bi-file-excel-fill" style="font-size: 20px; margin-right: 5px;"></i> Export
                            </button>

                            <!-- Create New -->
                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createModal"
                                style="border-radius: 0.2rem; display: inline-flex; align-items: center;">
                                <i class="bi bi-plus-circle" style="font-size: 20px; margin-right: 5px;"></i> Create New
                                Holiday
                            </button>
                        </div>
                    </div>


                    <div class="table-responsive">
                        <div class="calendar-wrapper">
                            <div id="calendar"></div>
                        </div>
                        {{-- <table class="table table-bordered table-hover align-middle">
                            <thead class="text-primary">
                                <tr>
                                    <th class="text-center">No</th>
                                    <th>Driver</th>
                                    <th>Start Date</th>
                                    <th>End Date</th>
                                    <th>Remarks</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($holidays as $index => $h)
                                    <tr>
                                        <td class="text-center">{{ $index + 1 }}</td>
                                        <td>{{ $h->driver->name ?? '-' }}
                                            @if ($h->driver->phone_my)
                                            ({{ $h->driver->phone_my }})
                                        @elseif ($h->driver->phone_sg)
                                            ({{ $h->driver->phone_sg }})
                                        @endif
                                        </td>
                                        <td>{{ $h->start_date }}</td>
                                        <td>{{ $h->end_date }}</td>
                                        <td>{{ $h->remarks }}</td>
                                        <td>
                                            <!-- Edit -->
                                            <button type="button" class="btn btn-info" data-bs-toggle="modal"
                                                data-bs-target="#editModal{{ $h->id }}">
                                                <i class="bi bi-pencil-square"></i>
                                            </button>

                                            <!-- Delete -->
                                            <form action="{{ route('driver-holidays.destroy', $h->id) }}" method="POST"
                                                class="d-inline delete-form">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-danger"
                                                    data-number="{{ $h->driver->name }}">
                                                    <i class="bi bi-trash-fill"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>

                                    <!-- Edit Modal -->
                                    <div class="modal fade" id="editModal{{ $h->id }}" tabindex="-1">
                                        <div class="modal-dialog modal-dialog-centered">
                                            <div class="modal-content">
                                                <form action="{{ route('driver-holidays.update', $h->id) }}" method="POST">
                                                    @csrf
                                                    @method('PUT')
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Edit Holiday</h5>
                                                        <button type="button" class="btn-close"
                                                            data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="mb-3">
                                                            <label>Driver</label>
                                                            <select name="driver_id" class="form-select" required>
                                                                <option value="">-- Select Driver --</option>
                                                                @foreach ($drivers as $driver)
                                                                    <option value="{{ $driver->id }}"
                                                                        {{ $h->driver_id == $driver->id ? 'selected' : '' }}>
                                                                        {{ $driver->name }}
                                                                        @if ($driver->phone_my)
                                                                            ({{ $driver->phone_my }})
                                                                        @elseif ($driver->phone_sg)
                                                                            ({{ $driver->phone_sg }})
                                                                        @endif
                                                                    </option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label>Start Date</label>
                                                            <input type="date" name="start_date"
                                                                value="{{ $h->start_date }}" class="form-control" required>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label>End Date</label>
                                                            <input type="date" name="end_date"
                                                                value="{{ $h->end_date }}" class="form-control" required>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label>Remarks</label>
                                                            <input type="text" name="remarks"
                                                                value="{{ $h->remarks }}" class="form-control">
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="submit" class="btn btn-primary">Update</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center">No driver holidays available.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table> --}}
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Reusable Edit Modal -->
    <div class="modal fade" id="editHolidayModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form id="editHolidayForm" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="modal-header">
                        <h5 class="modal-title">Edit Holiday</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body">
                        <div class="mb-3">
                            <label>Driver</label>
                            <select name="driver_id" class="form-select" required>
                                <option value="">-- Select Driver --</option>
                                @foreach ($drivers as $driver)
                                    <option value="{{ $driver->id }}">{{ $driver->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label>Start Date</label>
                            <input type="date" name="start_date" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label>End Date</label>
                            <input type="date" name="end_date" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label>Remarks</label>
                            <input type="text" name="remarks" class="form-control">
                        </div>
                    </div>

                    <div class="modal-footer d-flex ">
                        <button type="button" class="btn btn-danger" id="deleteHolidayBtn">Delete</button>
                        <button type="submit" class="btn btn-primary">Update</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Create Modal -->
    <div class="modal fade" id="createModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form action="{{ route('driver-holidays.store') }}" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Create Holiday</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label>Driver</label>
                            <select name="driver_id" class="form-select" required>
                                <option value="">-- Select Driver --</option>
                                @foreach ($drivers as $driver)
                                    <option value="{{ $driver->id }}">{{ $driver->name }}
                                        @if ($driver->phone_my)
                                            ({{ $driver->phone_my }})
                                        @elseif ($driver->phone_sg)
                                            ({{ $driver->phone_sg }})
                                        @endif
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label>Start Date</label>
                            <input type="date" name="start_date" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label>End Date</label>
                            <input type="date" name="end_date" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label>Remarks</label>
                            <input type="text" name="remarks" class="form-control">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-dark">Create</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- SweetAlert -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        @if (session('swal'))
            Swal.fire(@json(session('swal')));
        @endif

        // Delete confirm
        document.querySelectorAll('.delete-form').forEach(form => {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                let param = form.querySelector('button[type="submit"]').getAttribute("data-number");

                Swal.fire({
                    title: 'Are you sure?',
                    text: "This will delete the holiday record for '" + param + "'",
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
    </script>
@endsection
<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.4/index.global.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.4/index.global.min.js"></script>、<style>
    .calendar-wrapper {
        max-width: 1100px;
        margin: 20px auto;
        background: #ffffff;
        border-radius: 15px;
        padding: 25px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    }

    /* FullCalendar header beautify */
    .fc-toolbar {
        padding-bottom: 15px;
    }

    .fc-toolbar-title {
        font-size: 1.6rem !important;
        font-weight: 600;
        color: #333;
    }

    /* Buttons */
    .fc-button {
        border-radius: 8px !important;
        padding: 6px 12px !important;
        border: none !important;
        background: #3b82f6 !important;
        color: white !important;
        font-weight: 500 !important;
        transition: all .2s ease;
    }

    .fc-button:hover {
        background: #2563eb !important;
    }

    .fc-button-primary:not(:disabled).fc-button-active {
        background: #1d4ed8 !important;
    }

    /* Calendar border/spacing */
    .fc-scrollgrid {
        border-radius: 10px;
        overflow: hidden;
    }

    /* Day cells */
    .fc-daygrid-day {
        background: #fafafa;
    }

    /* Events appearance */
    .fc-event {
        border-radius: 6px !important;
        padding: 3px 6px;
        font-size: 0.85rem;
        font-weight: 500;
    }

    .fc .fc-button {
        margin: 0 4px !important;
    }
</style>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        let calendarEl = document.getElementById('calendar');

        let calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            height: 'auto',

            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek'
            },

            // Click on blank date → open CREATE modal
            dateClick: function(info) {
                document.querySelector('#createModal input[name="start_date"]').value = info
                    .dateStr;
                document.querySelector('#createModal input[name="end_date"]').value = info.dateStr;
                new bootstrap.Modal(document.getElementById('createModal')).show();
            },

            eventClick: function(info) {

                const deleteBtn = document.getElementById('deleteHolidayBtn');
                deleteBtn.onclick = function() {
                    Swal.fire({
                        title: 'Are you sure?',
                        text: "This will delete the holiday record!",
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#d33',
                        cancelButtonColor: '#3085d6',
                        confirmButtonText: 'Yes, delete it!'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            // Submit a DELETE request using a temporary form
                            const tempForm = document.createElement('form');
                            tempForm.method = 'POST';
                            tempForm.action = '/driver-holidays/' + event.id;
                            tempForm.innerHTML = '@csrf @method('DELETE')';
                            document.body.appendChild(tempForm);
                            tempForm.submit();
                        }
                    });
                };
                const event = info.event;
                const modal = document.getElementById('editHolidayModal');
                const form = document.getElementById('editHolidayForm');

                // Set the form action to the update route for this holiday (adjust if your route is different)
                form.action = '/driver-holidays/' + event.id;

                // Driver id (string) — select expects the same string value as option value
                const driverId = (event.extendedProps && event.extendedProps.driver_id) ? String(
                    event.extendedProps.driver_id) : '';
                modal.querySelector('select[name="driver_id"]').value = driverId;

                // Remarks
                modal.querySelector('input[name="remarks"]').value = (event.extendedProps && event
                    .extendedProps.remarks) ? event.extendedProps.remarks : '';

                // Start date
                if (event.start) {
                    let startDate = new Date(event.start);
                    startDate.setDate(startDate.getDate() + 1); // Add 1 day
                    modal.querySelector('input[name="start_date"]').value = startDate.toISOString()
                        .split('T')[0];
                } else {
                    modal.querySelector('input[name="start_date"]').value = '';
                }

                // End date: FullCalendar's end is exclusive. If end is missing (single day), use start.
                let endDate = null;
                if (event.end) {
                    endDate = new Date(event.end);
                } else if (event.start) {
                    endDate = new Date(event.start);
                }

                if (endDate) {
                    endDate.setDate(endDate.getDate());
                    modal.querySelector('input[name="end_date"]').value = endDate.toISOString()
                        .split('T')[0];
                } else {
                    modal.querySelector('input[name="end_date"]').value = '';
                }

                // Show modal
                new bootstrap.Modal(modal).show();
            },


            @php
                $colors = [
                    '#FF6B6B', // Vibrant red
                    '#FF922B', // Orange
                    '#FFC300', // Yellow gold
                    '#51CF66', // Green
                    '#339AF0', // Blue
                    '#845EF7', // Purple
                    '#FF5DA2', // Pink
                    '#00C2BA', // Aqua
                    '#F06595', // Rose
                    '#F6A623', // Amber
                ];
            @endphp
            events: [
                @foreach ($holidays as $h)
                    {
                        id: '{{ $h->id }}',
                        title: '{{ $h->driver->name }}',
                        start: '{{ $h->start_date }}',
                        end: '{{ \Carbon\Carbon::parse($h->end_date)->addDay()->format('Y-m-d') }}',
                        color: '{{ $colors[array_rand($colors)] }}',

                        // Pass extra data for modal
                        driver_id: '{{ $h->driver_id }}',
                        remarks: @json($h->remarks)
                    },
                @endforeach
            ],



        });

        calendar.render();
    });
</script>
