@extends('component.layout')
@section('title', 'Truck Size')

@section('content')
    <style>
        body {
            background-color: #f4f3ef;
        }
    </style>
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

                            <!-- Create Modal Button -->
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal"
                                data-bs-target="#createModal"
                                style="border-radius: 0.2rem; display: inline-flex; align-items: center;">
                                <i class="bi bi-plus-circle" style="font-size: 20px; margin-right: 5px;"></i> Create New
                                Size
                            </button>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered table-hover align-middle">
                            <thead class="text-primary">
                                <tr>
                                    <th class="text-center">No</th>
                                    <th>Truck Size</th>
                                    <th>Sq Ft</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($trucks as $index => $truck)
                                    <tr>
                                        <td class="text-center">{{ $index + 1 }}</td>
                                        <td>{{ $truck->size }}</td>
                                        <td>{{ $truck->sqft }}</td>
                                        <td>
                                            <!-- Edit Button -->
                                            <button type="button" class="btn btn-info" data-bs-toggle="modal"
                                                data-bs-target="#editModal{{ $truck->id }}">
                                                <i class="bi bi-pencil-square"></i>
                                            </button>

                                            <!-- Delete Button -->
                                            <form action="{{ route('truck-type.destroy', $truck->id) }}" method="POST"
                                                class="d-inline delete-form">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-danger">
                                                    <i class="bi bi-trash-fill"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>

                                    <!-- Edit Modal -->
                                    <div class="modal fade" id="editModal{{ $truck->id }}" tabindex="-1">
                                        <div class="modal-dialog modal-dialog-centered">
                                            <div class="modal-content">
                                                <form action="{{ route('truck-type.update', $truck->id) }}" method="POST">
                                                    @csrf
                                                    @method('PUT')
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Edit Truck Size</h5>
                                                        <button type="button" class="btn-close"
                                                            data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="mb-3">
                                                            <label>Truck Size</label>
                                                            <input type="text" name="size" value="{{ $truck->size }}"
                                                                class="form-control" required>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label>Sq Ft</label>
                                                            <input type="number" step="0.01" name="sqft"
                                                                value="{{ $truck->sqft }}" class="form-control" required>
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
                                        <td colspan="4" class="text-center">No truck type found.</td>
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
    <div class="modal fade" id="createModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form action="{{ route('truck-type.store') }}" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Create Truck Size</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label>Truck Size</label>
                            <input type="text" name="size" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label>Sq Ft</label>
                            <input type="number" step="0.01" name="sqft" class="form-control" required>
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
                Swal.fire({
                    title: 'Are you sure?',
                    text: "This will delete the truck size.",
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
