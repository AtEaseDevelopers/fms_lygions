@extends('component.layout')
@section('title', 'Driver')
@section('content')

    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="card-title">Driver</h4>
                    <form method="GET" class="d-flex align-items-center gap-2">
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
                        <form class="d-flex" style="width: 50%;" id="filterForm">
                            <div class="input-group no-border w-100 ">
                                <input type="text" name="search" value="{{ request('search') }}" class="form-control"
                                    placeholder="Search...">
                                <div class="input-group-append">
                                    <div class="input-group-text">
                                        <i class="nc-icon nc-zoom-split"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="input-group w-100 mx-3">
                                <button type="reset" class="btn btn-danger d-none" id="clearBtn">
                                    <i class="bi bi-x"></i>
                                </button>
                            </div>
                        </form>

                        <!-- Buttons -->
                        <div class="d-flex gap-2">
                            <button id="driver-sync-btn" class="btn btn-outline-success"
                                style="border-radius: 0.2rem; display: inline-flex; align-items: center;">
                                <i class="nc-icon nc-refresh-69" style="font-size: 20px; margin-right: 5px;"></i>
                                Sync</button>
                            <button class="btn btn-outline-primary"
                                style="border-radius: 0.2rem; display: inline-flex; align-items: center;">
                                <i class="bi bi-file-excel-fill" style="font-size: 20px; margin-right: 5px;"></i> Export
                            </button>
                            <a href="{{ route('driver.create') }}" class="btn btn-primary"
                                style="border-radius: 0.2rem; display: inline-flex; align-items: center;">
                                <i class="bi bi-person-add" style="font-size: 20px; margin-right: 5px;"></i> Create New
                                Driver
                            </a>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table id="driverTable" class="table table-striped table-bordered">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>
                                        <a class="text-dark text-decoration-none"
                                            href="{{ route('driver.index', array_merge(request()->query(), ['sort_by' => 'name', 'sort_order' => request('sort_order') === 'asc' && request('sort_by') === 'name' ? 'desc' : 'asc'])) }}">
                                            Name
                                            @if (request('sort_by') == 'name')
                                                <i
                                                    class="bi bi-caret-{{ request('sort_order') == 'asc' ? 'up' : 'down' }}-fill"></i>
                                            @endif
                                        </a>
                                    </th>
                                    <th>
                                        <a class="text-dark text-decoration-none"
                                            href="{{ route('driver.index', array_merge(request()->query(), ['sort_by' => 'group', 'sort_order' => request('sort_order') === 'asc' && request('sort_by') === 'group' ? 'desc' : 'asc'])) }}">
                                            Group
                                            @if (request('sort_by') == 'group')
                                                <i
                                                    class="bi bi-caret-{{ request('sort_order') == 'asc' ? 'up' : 'down' }}-fill"></i>
                                            @endif
                                        </a>
                                    </th>
                                    <th>
                                        <a class="text-dark text-decoration-none"
                                            href="{{ route('driver.index', array_merge(request()->query(), ['sort_by' => 'id_number', 'sort_order' => request('sort_order') === 'asc' && request('sort_by') === 'id_number' ? 'desc' : 'asc'])) }}">
                                            ID Number
                                            @if (request('sort_by') == 'id_number')
                                                <i
                                                    class="bi bi-caret-{{ request('sort_order') == 'asc' ? 'up' : 'down' }}-fill"></i>
                                            @endif
                                        </a>
                                    </th>
                                    <th>Phone Number (MY)</th>
                                    <th>Phone Number (SG)</th>
                                    <th>Lorry Assigned</th>
                                    <th>Note</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($drivers as $index => $driver)
                                    <tr>
                                        <td class="text-center">
                                            {{ $loop->iteration + ($drivers->currentPage() - 1) * $drivers->perPage() }}
                                        </td>
                                        <td>{{ $driver->name }}</td>
                                        <td>{{ $driver->group }}</td>
                                        <td>{{ $driver->id_number }}</td>
                                        <td>{{ $driver->phone_my }}</td>
                                        <td>{{ $driver->phone_sg }}</td>
                                        <td>{{ $driver->truck }}</td>
                                        <td>{{ $driver->note }}</td>
                                        <td>
                                            <button type="button" class="btn btn-info" data-bs-toggle="modal"
                                                data-bs-target="#editModal{{ $driver->id }}">
                                                <i class="bi bi-pencil-square"></i>
                                            </button>

                                            <form action="{{ route('driver.destroy', $driver->id) }}" method="POST"
                                                class="d-inline delete-form">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-danger"
                                                    data-number="{{ $driver->name }}">
                                                    <i class="bi bi-trash-fill"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>

                                    <!-- Edit Modal -->
                                    <div class="modal fade" id="editModal{{ $driver->id }}" tabindex="-1">
                                        <div class="modal-dialog modal-dialog-centered">
                                            <div class="modal-content">
                                                <form action="{{ route('driver.update', $driver->id) }}" method="POST">
                                                    @csrf
                                                    @method('PUT')
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Edit Driver</h5>
                                                        <button type="button" class="btn-close"
                                                            data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="mb-3">
                                                            <label>Name</label>
                                                            <input type="text" name="name"
                                                                value="{{ $driver->name }}" class="form-control">
                                                        </div>
                                                        <div class="mb-3">
                                                            <label>Group</label>
                                                            <input type="text" name="group"
                                                                value="{{ $driver->group }}" class="form-control">
                                                        </div>
                                                        <div class="mb-3">
                                                            <label>ID Number</label>
                                                            <input type="text" name="id_number"
                                                                value="{{ $driver->id_number }}" class="form-control">
                                                        </div>
                                                        <div class="mb-3">
                                                            <label>Phone Number (MY)</label>
                                                            <input type="text" name="phone_my"
                                                                value="{{ $driver->phone_my }}" class="form-control">
                                                        </div>
                                                        <div class="mb-3">
                                                            <label>Phone Number (SG)</label>
                                                            <input type="text" name="phone_sg"
                                                                value="{{ $driver->phone_sg }}" class="form-control">
                                                        </div>
                                                        <div class="mb-3">
                                                            <label>Lorry Assigned</label>
                                                            <input type="text" name="truck"
                                                                value="{{ $driver->truck }}" class="form-control">
                                                        </div>
                                                        <div class="mb-3">
                                                            <label>Note</label>
                                                            <textarea name="note" class="form-control">{{ $driver->note }}</textarea>
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
                                        <td colspan="9" class="text-center">No drivers found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>

                    </div>
                    <div class="d-flex justify-content-between align-items-center mt-4">
                        <div>
                            Showing {{ $drivers->firstItem() }} to {{ $drivers->lastItem() }} of
                            {{ $drivers->total() }} entries
                        </div>

                        <div>
                            {{ $drivers->appends(request()->except('page'))->links('pagination::bootstrap-5') }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- SweetAlert -->
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        @if (session('swal'))
            Swal.fire(@json(session('swal')));
        @endif

        document.addEventListener("DOMContentLoaded", function() {

            const btn = document.getElementById('driver-sync-btn');

            btn.addEventListener('click', function() {
                const originalHtml = btn.innerHTML;

                // Disable button & show syncing
                btn.disabled = true;
                btn.innerHTML =
                    `<i class="nc-icon nc-refresh-69" style="font-size: 20px; margin-right: 5px;"></i> Syncing...`;

                axios.post('{{ route('driver.sync') }}', {}, {
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        }
                    })
                    .then(res => {
                        let swalData = res.data.swal || {
                            icon: 'success',
                            title: 'Done',
                            text: 'Drivers synced successfully.'
                        };
                        Swal.fire({
                            icon: swalData.icon,
                            title: swalData.title,
                            text: swalData.text
                        }).then(() => {
                            location.reload(); // optionally reload to see updated table
                        });
                    })
                    .catch(err => {
                        let swalData = err.response?.data?.swal || {
                            icon: 'error',
                            title: 'Error',
                            text: 'Sync failed'
                        };
                        Swal.fire({
                            icon: swalData.icon,
                            title: swalData.title,
                            text: swalData.text
                        });
                    })
                    .finally(() => {
                        // Restore button
                        btn.disabled = false;
                        btn.innerHTML = originalHtml;
                    });
            });
        });
        // Delete confirm
        document.querySelectorAll('.delete-form').forEach(form => {

            form.addEventListener('submit', function(e) {
                e.preventDefault();
                let driver = form.querySelector('button[type="submit"]').getAttribute("data-number");

                Swal.fire({
                    title: 'Are you sure?',
                    text: "This will delete the driver '" + driver + "'",
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
