@extends('component.layout')
@section('title', 'Location')
@section('content')
    @if (session('swal'))
        <script>
            document.addEventListener("DOMContentLoaded", function() {
                Swal.fire(@json(session('swal')));
            });
        </script>
    @endif

    <div class="row">
        <div class="col-md-12">
            <div class="card">
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
                                <button type="reset" class="btn btn-danger d-none" id="clearBtn">
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

                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createLocationModal"
                                style="border-radius: 0.2rem; display: inline-flex; align-items: center;">
                                <i class="bi bi-person-add" style="font-size: 20px; margin-right: 5px;"></i>
                                Create New Location
                            </button>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered table-hover align-middle">
                            <thead class="text-primary">
                                <tr>
                                    <th class="text-center">No</th>
                                    <th>
                                        <a href="{{ route('location.index', [
                                            'sort' => 'name',
                                            'direction' => request('direction') === 'asc' ? 'desc' : 'asc',
                                        ]) }}"
                                            style="color: black">
                                            Location
                                            @if (request('sort') === 'name')
                                                @if (request('direction') === 'asc')
                                                    <i class="bi bi-caret-up-fill"></i>
                                                @else
                                                    <i class="bi bi-caret-down-fill"></i>
                                                @endif
                                            @endif
                                        </a>
                                    </th>

                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($locations as $index => $location)
                                    <tr>
                                        <td class="text-center">{{ $index + 1 }}</td>
                                        <td>{{ $location->name }}</td>
                                        <td>
                                            <!-- Edit -->
                                            <button class="btn btn-info" data-bs-toggle="modal"
                                                data-bs-target="#editLocationModal{{ $location->id }}">
                                                <i class="bi bi-pencil-square"></i>
                                            </button>

                                            <!-- Delete -->
                                            <form action="{{ route('location.destroy', $location->id) }}" method="POST"
                                                class="d-inline delete-form" data-name="{{ $location->name }}">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-danger">
                                                    <i class="bi bi-trash-fill"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>

                                    <!-- Edit Modal -->
                                    <div class="modal fade" id="editLocationModal{{ $location->id }}" tabindex="-1"
                                        aria-hidden="true">
                                        <div class="modal-dialog modal-dialog-centered">
                                            <div class="modal-content">
                                                <form action="{{ route('location.update', $location->id) }}" method="POST"
                                                    class="modal-content">
                                                    @csrf
                                                    @method('PUT')
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Edit Location</h5>
                                                        <button type="button" class="btn-close"
                                                            data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="mb-3">
                                                            <label class="form-label">Location</label>
                                                            <input type="text" class="form-control" name="name"
                                                                value="{{ $location->name }}" required>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="submit" class="btn btn-primary">Save Changes</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center">No locations found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <!-- Create Modal -->
    <div class="modal fade" id="createLocationModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form action="{{ route('location.store') }}" method="POST" class="modal-content">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Create New Location</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Location</label>
                        <input type="text" class="form-control" name="name" placeholder="Enter location name"
                            required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Create</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Delete confirmation
        document.querySelectorAll('.delete-form').forEach(form => {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                let name = this.getAttribute("data-name");

                Swal.fire({
                    title: 'Are you sure?',
                    text: "This will delete the location '" + name + "'",
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
