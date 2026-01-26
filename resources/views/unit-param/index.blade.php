@extends('component.layout')
@section('title', 'Standard Units Parameter & Estimated Space')
@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    {{-- <h4 class="card-title">Standard Units Parameter & Estimated Space</h4> --}}
                </div>

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
                                Parameter
                            </button>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered table-hover align-middle">
                            <thead class="text-primary">
                                <tr>
                                    <th class="text-center">No</th>
                                    <th>Unit</th>
                                    <th>Description</th>
                                    <th>Qty In P4</th>
                                    <th>Foot Space</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($units as $index => $u)
                                    <tr>
                                        <td class="text-center">{{ $index + 1 }}</td>
                                        <td>{{ $u->unit }}</td>
                                        <td>{{ $u->desc }}</td>
                                        <td>{{ $u->qty }}</td>
                                        <td>{{ $u->space }}</td>
                                        <td>
                                            <!-- Edit -->
                                            <button type="button" class="btn btn-info" data-bs-toggle="modal"
                                                data-bs-target="#editModal{{ $u->id }}">
                                                <i class="bi bi-pencil-square"></i>
                                            </button>

                                            <!-- Delete -->
                                            <form action="{{ route('unit-param.destroy', $u->id) }}" method="POST"
                                                class="d-inline delete-form">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-danger"
                                                    data-number="{{ $u->unit }}">
                                                    <i class="bi bi-trash-fill"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>

                                    <!-- Edit Modal -->
                                    <div class="modal fade" id="editModal{{ $u->id }}" tabindex="-1">
                                        <div class="modal-dialog modal-dialog-centered">
                                            <div class="modal-content">
                                                <form action="{{ route('unit-param.update', $u->id) }}" method="POST">
                                                    @csrf
                                                    @method('PUT')
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Edit Parameter</h5>
                                                        <button type="button" class="btn-close"
                                                            data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="mb-3">
                                                            <label>Unit</label>
                                                            <input type="text" name="unit"
                                                                value="{{ $u->unit }}" class="form-control" required>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label>Description</label>
                                                            <input type="text" name="desc"
                                                                value="{{ $u->desc }}" class="form-control">
                                                        </div>
                                                        <div class="mb-3">
                                                            <label>Qty In P4</label>
                                                            <input type="number" name="qty"
                                                                value="{{ $u->qty }}" class="form-control" required>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label>Foot Space</label>
                                                            <input type="number" step="0.01" name="space"
                                                                value="{{ $u->space }}" class="form-control">
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
                                        <td colspan="8" class="text-center">No unit parameter&space available.</td>
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
                <form action="{{ route('unit-param.store') }}" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Create Parameter</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label>Unit</label>
                            <input type="text" name="unit" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label>Description</label>
                            <input type="text" name="desc" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label>Qty In P4</label>
                            <input type="number" name="qty" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label>Foot Space</label>
                            <input type="number" step="0.01" name="space" class="form-control">
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
                    text: "This will delete the parameter '" + param + "'",
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
