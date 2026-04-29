@extends('component.layout')
@section('title', 'Driver Leave Plan')
@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="card-title">Driver Leave Plan</h4>
                    <form method="GET" class="d-flex align-items-center gap-2">
                        @foreach (request()->except(['per_page', 'page']) as $key => $value)
                            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                        @endforeach
                        <label for="per_page" class="mb-0">Show</label>
                        <select name="per_page" id="per_page" class="form-select" onchange="this.form.submit()">
                            @foreach ([5, 10, 25, 50] as $limit)
                                <option value="{{ $limit }}"
                                    {{ request('per_page', 10) == $limit ? 'selected' : '' }}>{{ $limit }}</option>
                            @endforeach
                        </select>
                        <span class="mb-0">entries</span>
                    </form>
                </div>

                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center flex-wrap mb-3">
                        <!-- Search Form -->
                        <form method="GET" action="{{ route('driver-holidays.index') }}" class="d-flex" style="width: 50%;" id="filterForm">
                            <div class="input-group no-border w-100">
                                <input type="text" name="search" value="{{ request('search') }}" class="form-control"
                                    placeholder="Search...">
                                <div class="input-group-append">
                                    <div class="input-group-text">
                                        <i class="nc-icon nc-zoom-split"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="input-group w-100 mx-3">
                                @if (request('search'))
                                    <a href="{{ route('driver-holidays.index') }}" class="btn btn-danger" style="margin: 0;">
                                        <i class="bi bi-x"></i>
                                    </a>
                                @endif
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
                        <table class="table table-striped table-bordered align-middle">
                            <thead>
                                <tr>
                                    <th class="text-center">No</th>
                                    <th>Driver</th>
                                    <th>
                                        <a class="text-dark text-decoration-none"
                                            href="{{ route('driver-holidays.index', array_merge(request()->query(), ['sort_by' => 'start_date', 'sort_order' => request('sort_order') === 'asc' && request('sort_by') === 'start_date' ? 'desc' : 'asc'])) }}">
                                            Start Date
                                            @if (request('sort_by', 'start_date') == 'start_date')
                                                <i class="bi bi-caret-{{ request('sort_order', 'desc') == 'asc' ? 'up' : 'down' }}-fill"></i>
                                            @endif
                                        </a>
                                    </th>
                                    <th>
                                        <a class="text-dark text-decoration-none"
                                            href="{{ route('driver-holidays.index', array_merge(request()->query(), ['sort_by' => 'end_date', 'sort_order' => request('sort_order') === 'asc' && request('sort_by') === 'end_date' ? 'desc' : 'asc'])) }}">
                                            End Date
                                            @if (request('sort_by') == 'end_date')
                                                <i class="bi bi-caret-{{ request('sort_order') == 'asc' ? 'up' : 'down' }}-fill"></i>
                                            @endif
                                        </a>
                                    </th>
                                    <th>Remarks</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($holidays as $h)
                                    <tr>
                                        <td class="text-center">
                                            {{ $loop->iteration + ($holidays->currentPage() - 1) * $holidays->perPage() }}
                                        </td>
                                        <td>
                                            {{ $h->driver->name ?? '-' }}
                                            @if ($h->driver?->phone_my)
                                                ({{ $h->driver->phone_my }})
                                            @elseif ($h->driver?->phone_sg)
                                                ({{ $h->driver->phone_sg }})
                                            @endif
                                        </td>
                                        <td>{{ $h->start_date }}</td>
                                        <td>{{ $h->end_date }}</td>
                                        <td>{{ $h->remarks }}</td>
                                        <td>
                                            <button type="button" class="btn btn-info" data-bs-toggle="modal"
                                                data-bs-target="#editModal{{ $h->id }}">
                                                <i class="bi bi-pencil-square"></i>
                                            </button>

                                            <form action="{{ route('driver-holidays.destroy', $h->id) }}" method="POST"
                                                class="d-inline delete-form">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-danger"
                                                    data-number="{{ $h->driver->name ?? '' }}">
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
                        </table>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mt-4">
                        <div>
                            @if ($holidays->total() > 0)
                                Showing {{ $holidays->firstItem() }} to {{ $holidays->lastItem() }} of
                                {{ $holidays->total() }} entries
                            @else
                                Showing 0 entries
                            @endif
                        </div>

                        <div>
                            {{ $holidays->appends(request()->except('page'))->links('pagination::bootstrap-5') }}
                        </div>
                    </div>
                </div>
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
