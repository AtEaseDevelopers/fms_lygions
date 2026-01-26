@extends('component.layout')
@section('title', 'User Management')
@section('content')
    <style>
        body {
            background-color: #f4f3ef
        }
    </style>
    @if (session('swal'))
        <script>
            document.addEventListener("DOMContentLoaded", function() {
                Swal.fire({
                    icon: "{{ session('swal.icon') }}",
                    title: "{{ session('swal.title') }}",
                    text: "{{ session('swal.text') }}",
                    showConfirmButton: false,
                    timer: 2000
                });
            });
        </script>
    @endif
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="card-title">Users</h4>

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
                                <button type="reset" class="btn btn-danger d-none" style="margin: 0;" id="clearBtn"> <i
                                        class="bi bi-x"></i> </button>
                            </div>
                        </form>

                        <!-- Buttons -->
                        <!-- Buttons -->
                        <div class="d-flex gap-2">
                            <button class="btn btn-outline-primary"
                                style="border-radius: 0.2rem; display: inline-flex; align-items: center;">
                                <i class="bi bi-file-excel-fill" style="font-size: 20px; margin-right: 5px;"></i> Export
                            </button>

                            <!-- Create New User Button -->
                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createUserModal"
                                style="border-radius: 0.2rem; display: inline-flex; align-items: center;">
                                <i class="bi bi-person-add" style="font-size: 20px; margin-right: 5px;"></i> Create New User
                            </button>
                        </div>

                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover table-bordered align-middle">
                            <thead class="text-primary">
                                <tr>
                                    <th class="text-center">No</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($users as $i => $user)
                                    <tr>
                                        <td class="text-center">{{ $users->firstItem() + $i }}</td>
                                        <td>{{ $user->name }}</td>
                                        <td>{{ $user->email }}</td>
                                        <td>{{ $user->role ? $user->role->name : 'No Role Assigned' }}</td>
                                        <td>
                                            <!-- Edit Button -->
                                            <button class="btn btn-info" data-bs-toggle="modal"
                                                data-bs-target="#editModal{{ $user->id }}">
                                                <i class="bi bi-pencil-square"></i>
                                            </button>

                                            <!-- Delete Button -->
                                          <form action="{{ route('user.destroy', $user->id) }}" method="POST" class="d-inline delete-form">
    @csrf
    @method('DELETE')
    <button type="button" class="btn btn-danger delete-btn" data-username="{{ $user->name }}">
        <i class="bi bi-trash-fill"></i>
    </button>
</form>
                                        </td>
                                    </tr>

                                    <!-- Edit Modal -->
                                    <div class="modal fade" id="editModal{{ $user->id }}" tabindex="-1"
                                        aria-labelledby="editModalLabel{{ $user->id }}" aria-hidden="true">
                                        <div class="modal-dialog modal-dialog-centered">
                                            <div class="modal-content">
                                                <form action="{{ route('user.update', $user->id) }}" method="POST">
                                                    @csrf
                                                    @method('PUT')

                                                    <div class="modal-header">
                                                        <h5 class="modal-title" id="editModalLabel{{ $user->id }}">
                                                            <i class="bi bi-pencil-square me-2"></i> Edit User
                                                        </h5>
                                                        <button type="button" class="btn-close text-danger"
                                                            data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>

                                                    <div class="modal-body">
                                                        <div class="mb-3">
                                                            <label class="form-label fw-bold">Name</label>
                                                            <input type="text" name="name" class="form-control"
                                                                value="{{ $user->name }}" required>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label fw-bold">Email</label>
                                                            <input type="email" name="email" class="form-control"
                                                                value="{{ $user->email }}" required>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label fw-bold">Role</label>
                                                            <select name="role_id" class="form-select">
                                                                @foreach ($roles as $role)
                                                                    <option value="{{ $role->id }}"
                                                                        @selected(isset($user) && $user->role_id == $role->id)>
                                                                        {{ $role->name }}
                                                                    </option>
                                                                @endforeach
                                                            </select>
                                                        </div>

                                                    </div>

                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary"
                                                            data-bs-dismiss="modal">CANCEL</button>
                                                        <button type="submit" class="btn btn-primary">UPDATE</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>



                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center">No users found.</td>
                                    </tr>
                                @endforelse
                            </tbody>

                        </table>
                    </div>

                    <!-- Create User Modal -->
                    <div class="modal fade" id="createUserModal" tabindex="-1" aria-labelledby="createUserModalLabel"
                        aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content">
                                <form action="{{ route('user.store') }}" method="POST">
                                    @csrf

                                    <div class="modal-header">
                                        <h5 class="modal-title" id="createUserModalLabel">
                                            <i class="bi bi-person-plus me-2"></i> Create New User
                                        </h5>
                                        <button type="button" class="btn-close text-danger" data-bs-dismiss="modal"
                                            aria-label="Close"></button>
                                    </div>

                                    <div class="modal-body">
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">Name</label>
                                            <input type="text" name="name" class="form-control" required>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label fw-bold">Email</label>
                                            <input type="email" name="email" class="form-control" required>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label fw-bold">Password</label>
                                            <input type="password" name="password" class="form-control" required>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Role</label>
                                            <select name="role_id" class="form-select" required>
                                                @foreach ($roles as $role)
                                                    <option value="{{ $role->id }}"
                                                        {{ isset($user) && $user->role_id == $role->id ? 'selected' : '' }}>
                                                        {{ $role->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>

                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary"
                                            data-bs-dismiss="modal">CANCEL</button>
                                        <button type="submit" class="btn btn-primary">CREATE</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>


                </div>
            </div>

        </div>

    </div>
@endsection
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener("DOMContentLoaded", function () {
    document.querySelectorAll(".delete-btn").forEach(button => {
        button.addEventListener("click", function () {
            let form = this.closest("form");
            let username = this.getAttribute("data-username");

            Swal.fire({
                title: "Are you sure?",
                text: "This will permanently delete user \"" + username + "\".",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#d33",
                cancelButtonColor: "#6c757d",
                confirmButtonText: "Yes, delete it!",
                cancelButtonText: "Cancel"
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        });
    });
});
</script>
