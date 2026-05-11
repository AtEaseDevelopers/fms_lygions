@extends('component.layout')
@section('title', 'Subcon')
@section('content')

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

    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex align-items-center">
                    <h4 class="card-title mb-0">Subcons</h4>

                    <form method="GET" class="d-flex align-items-center gap-2 ms-auto">
                        <label for="per_page" class="mb-0">Show</label>
                        <select name="per_page" id="per_page" class="form-select" onchange="this.form.submit()">
                            @foreach ([5, 10, 25, 50] as $limit)
                                <option value="{{ $limit }}"
                                    {{ request('per_page', 10) == $limit ? 'selected' : '' }}>
                                    {{ $limit }}
                                </option>
                            @endforeach
                        </select>
                        <span class="mb-0">entries</span>

                        <!-- Preserve search & sort queries -->
                        <input type="hidden" name="search" value="{{ request('search') }}">
                        <input type="hidden" name="sort_by" value="{{ request('sort_by') }}">
                        <input type="hidden" name="sort_order" value="{{ request('sort_order') }}">
                    </form>
                </div>

                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center flex-wrap mb-3">
                        <!-- Search Form -->
                        <form class="d-flex" style="width: 50%;" method="GET" action="{{ route('subcon.index') }}">
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
                                <a href="{{ route('subcon.index') }}"
                                    class="btn btn-danger {{ request('search') ? '' : 'd-none' }}" id="clearBtn">
                                    <i class="bi bi-x"></i>
                                </a>
                            </div>
                        </form>

                        <!-- Buttons -->
                        <div class="d-flex gap-2">
                            <button id="subcon-sync-btn" class="btn btn-outline-success" style="border-radius: 0.2rem;">
                                <i class="nc-icon nc-refresh-69 me-1"></i> Sync
                            </button>
                            <button class="btn btn-outline-primary" style="border-radius: 0.2rem;">
                                <i class="bi bi-file-excel-fill me-1"></i> Export
                            </button>
                            <a href="{{ route('subcon.create') }}" class="btn btn-primary" style="border-radius: 0.2rem;">
                                <i class="bi bi-person-add me-1"></i> Create New Subcon
                            </a>
                        </div>
                    </div>



                    <div class="table-responsive">
                        <table id="subconTable" class="table table-striped table-bordered  table-sm">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>
                                        <a class="text-dark text-decoration-none"
                                            href="{{ route('subcon.index', array_merge(request()->query(), ['sort_by' => 'subcon_name', 'sort_order' => request('sort_order') === 'asc' && request('sort_by') === 'subcon_name' ? 'desc' : 'asc'])) }}">
                                            Subcon Name
                                            @if (request('sort_by') == 'subcon_name')
                                                <i
                                                    class="bi bi-caret-{{ request('sort_order') == 'asc' ? 'up' : 'down' }}-fill"></i>
                                            @endif
                                        </a>
                                    </th>
                                    <th>
                                        <a class="text-dark text-decoration-none"
                                            href="{{ route('subcon.index', array_merge(request()->query(), ['sort_by' => 'truck_no', 'sort_order' => request('sort_order') === 'asc' && request('sort_by') === 'truck_no' ? 'desc' : 'asc'])) }}">
                                            Truck Number
                                            @if (request('sort_by') == 'truck_no')
                                                <i
                                                    class="bi bi-caret-{{ request('sort_order') == 'asc' ? 'up' : 'down' }}-fill"></i>
                                            @endif
                                        </a>
                                    </th>
                                    <th>Driver Name</th>
                                    <th>Team</th>
                                    <th>
                                        <a class="text-dark text-decoration-none"
                                            href="{{ route('subcon.index', array_merge(request()->query(), ['sort_by' => 'group', 'sort_order' => request('sort_order') === 'asc' && request('sort_by') === 'group' ? 'desc' : 'asc'])) }}">
                                            Group
                                            @if (request('sort_by') == 'group')
                                                <i
                                                    class="bi bi-caret-{{ request('sort_order') == 'asc' ? 'up' : 'down' }}-fill"></i>
                                            @endif
                                        </a>
                                    </th>
                                    <th>Size</th>

                                    <th>
                                        <a class="text-dark text-decoration-none"
                                            href="{{ route('subcon.index', array_merge(request()->query(), ['sort_by' => 'tonnage', 'sort_order' => request('sort_order') === 'asc' && request('sort_by') === 'tonnage' ? 'desc' : 'asc'])) }}">
                                            Tonnage
                                            @if (request('sort_by') == 'tonnage')
                                                <i
                                                    class="bi bi-caret-{{ request('sort_order') == 'asc' ? 'up' : 'down' }}-fill"></i>
                                            @endif
                                        </a>
                                    </th>

                                    <th>
                                        <a class="text-dark text-decoration-none"
                                            href="{{ route('subcon.index', array_merge(request()->query(), ['sort_by' => 'floor_space', 'sort_order' => request('sort_order') === 'asc' && request('sort_by') === 'floor_space' ? 'desc' : 'asc'])) }}">
                                            Floor Space (In sqft)
                                            @if (request('sort_by') == 'floor_space')
                                                <i
                                                    class="bi bi-caret-{{ request('sort_order') == 'asc' ? 'up' : 'down' }}-fill"></i>
                                            @endif
                                        </a>
                                    </th>

                                    <th>
                                        <a class="text-dark text-decoration-none"
                                            href="{{ route('subcon.index', array_merge(request()->query(), ['sort_by' => 'chassis_type', 'sort_order' => request('sort_order') === 'asc' && request('sort_by') === 'chassis_type' ? 'desc' : 'asc'])) }}">
                                            Chassis Type
                                            @if (request('sort_by') == 'chassis_type')
                                                <i
                                                    class="bi bi-caret-{{ request('sort_order') == 'asc' ? 'up' : 'down' }}-fill"></i>
                                            @endif
                                        </a>
                                    </th>
                                    <th>Phone Number (MY)</th>
                                    <th>Phone Number (SG)</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($subcons as $i => $subcon)
                                    <tr>
                                        <td class="text-center">{{ $subcons->firstItem() + $i }}</td>
                                        <td>{{ $subcon->subcon_name }}</td>
                                        <td>{{ $subcon->truck_no }}</td>
                                        <td>{{ $subcon->driver_name }}</td>
                                        <td>{{ $subcon->team }}</td>
                                        <td>{{ $subcon->group }}</td>
                                        <td>{{ $subcon->size }}</td>
                                        <td>{{ $subcon->tonnage }}</td>
                                        <td>{{ $subcon->floor_space }}</td>
                                        <td>{{ $subcon->chassis_type }}</td>
                                        <td>{{ $subcon->phone_my }}</td>
                                        <td>{{ $subcon->phone_sg }}</td>
                                        <td>
                                            {{-- <a href="{{ route('subcon.show', $subcon->id) }}" class="btn btn-success">
                                                <i class="bi bi-eye"></i>
                                            </a> --}}
                                            <button type="button" class="btn btn-info" data-bs-toggle="modal"
                                                data-bs-target="#editModal{{ $subcon->id }}">
                                                <i class="bi bi-pencil-square"></i>
                                            </button>
                                            <!-- Edit Modal -->
                                            <div class="modal fade" id="editModal{{ $subcon->id }}" tabindex="-1"
                                                aria-hidden="true">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <form action="{{ route('subcon.update', $subcon->id) }}"
                                                            method="POST">
                                                            @csrf
                                                            @method('PUT')
                                                            <div class="modal-header">
                                                                <h5 class="modal-title">Edit Subcon</h5>
                                                                <button type="button" class="btn-close"
                                                                    data-bs-dismiss="modal"></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <div class="mb-3">
                                                                    <label class="form-label">Subcon Name</label>
                                                                    <input type="text" name="subcon_name"
                                                                        class="form-control"
                                                                        value="{{ $subcon->subcon_name }}">
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label class="form-label">Truck No</label>
                                                                    <input type="text" name="truck_no"
                                                                        class="form-control"
                                                                        value="{{ $subcon->truck_no }}">
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label class="form-label">Driver Name</label>
                                                                    <input type="text" name="driver_name"
                                                                        class="form-control"
                                                                        value="{{ $subcon->driver_name }}">
                                                                </div>
                                                                <div>
                                                                    <label class="form-label">Team</label>
                                                                    <select name="team" class="form-select"
                                                                        id="team">
                                                                        <option value="" disabled
                                                                            {{ $subcon->team == null ? 'selected' : '' }}>
                                                                            -- Select Team --</option>
                                                                        <option value="MY"
                                                                            {{ $subcon->team == 'MY' ? 'selected' : '' }}>MY Team
                                                                        </option>
                                                                        <option value="SG"
                                                                            {{ $subcon->team == 'SG' ? 'selected' : '' }}>SG Team
                                                                        </option>
                                                                    </select>
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label class="form-label">Phone (MY)</label>
                                                                    <input type="text" name="phone_my"
                                                                        class="form-control"
                                                                        value="{{ $subcon->phone_my }}">
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label class="form-label">Phone (SG)</label>
                                                                    <input type="text" name="phone_sg"
                                                                        class="form-control"
                                                                        value="{{ $subcon->phone_sg }}">
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label>Group</label>
                                                                    <input type="text" name="group"
                                                                        value="{{ $subcon->group }}"
                                                                        class="form-control">
                                                                </div>

                                                                <div class="mb-3">
                                                                    <label>Size</label>
                                                                     <select name="size" class="form-select"
                                                                        id="size">
                                                                        <option value="" disabled
                                                                            {{ $subcon->size == null ? 'selected' : '' }}>
                                                                            -- Select Size --</option>
                                                                        <option value="20"
                                                                            {{ $subcon->size == '20' ? 'selected' : '' }}>20
                                                                        </option>
                                                                        <option value="40"
                                                                            {{ $subcon->size == '40' ? 'selected' : '' }}>40
                                                                        </option>
                                                                        <option value="Small"
                                                                            {{ $subcon->size == 'Small' ? 'selected' : '' }}>Small
                                                                    </select>
                                                                </div>

                                                                <div class="mb-3">
                                                                    <label>Tonnage</label>
                                                                    <input type="number" step="0.01" name="tonnage"
                                                                        value="{{ $subcon->tonnage }}"
                                                                        class="form-control">
                                                                </div>

                                                                <div class="mb-3">
                                                                    <label>Floor Space (sqft)</label>
                                                                    <input type="number" step="0.01"
                                                                        name="floor_space"
                                                                        value="{{ $subcon->floor_space }}"
                                                                        class="form-control">
                                                                </div>

                                                                <div class="mb-3">
                                                                    <label>Chassis Type</label>
                                                                    <input type="text" name="chassis_type"
                                                                        value="{{ $subcon->chassis_type }}"
                                                                        class="form-control">
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="submit" class="btn btn-primary">Save
                                                                    changes</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Delete Button with Swal -->
                                            <form action="{{ route('subcon.destroy', $subcon->id) }}" method="POST"
                                                class="d-inline delete-form">
                                                @csrf
                                                @method('DELETE')
                                                <button type="button" class="btn btn-danger delete-btn"
                                                    data-number="{{ $subcon->subcon_name }}">
                                                    <i
                                                        class="bi
                                                    bi-trash-fill"></i>
                                                </button>
                                            </form>



                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center">No subcon data available.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                        <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap">
                            <!-- Pagination info -->
                            <div class="mb-2 mb-md-0">
                                Showing {{ $subcons->firstItem() ?? 0 }} to {{ $subcons->lastItem() ?? 0 }} of
                                {{ $subcons->total() }} entries
                            </div>

                            <!-- Pagination links -->
                            <div>
                                {{ $subcons->appends(request()->except('page'))->links('pagination::bootstrap-5') }}
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        const btn = document.getElementById('subcon-sync-btn');
        btn.addEventListener('click', function() {
            const originalHtml = btn.innerHTML;

            // Disable button and show syncing
            btn.disabled = true;
            btn.innerHTML = `<i class="nc-icon nc-refresh-69 me-1"></i> Syncing...`;

            axios.post('{{ route('subcon.sync') }}', {}, {
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                })
                .then(res => {
                    let swalData = res.data.swal || {
                        icon: 'success',
                        title: 'Done',
                        text: 'Subcons synced successfully.'
                    };

                    Swal.fire({
                        icon: swalData.icon,
                        title: swalData.title,
                        text: swalData.text
                    }).then(() => {
                        location.reload();
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
        document.querySelectorAll(".delete-btn").forEach(button => {
            button.addEventListener("click", function() {
                let form = this.closest("form");
                let subcon = this.getAttribute("data-number");

                Swal.fire({
                    title: "Are you sure?",
                    text: "This subcon '" + subcon + "' will be permanently deleted!",
                    icon: "warning",
                    showCancelButton: true,
                    confirmButtonColor: "#d33",
                    cancelButtonColor: "#6c757d",
                    confirmButtonText: "Yes, delete it!"
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            });
        });
    });
</script>
