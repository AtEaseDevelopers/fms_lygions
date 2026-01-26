@extends('component.layout')
@section('title', 'Role Management')

@section('content')
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
                    <h4 class="card-title">Roles</h4>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createRoleModal"
                        style="border-radius: 0.2rem; display: inline-flex; align-items: center;">
                        <i class="bi bi-plus-lg" style="font-size: 20px; margin-right: 5px;"></i>Create New Role
                    </button>
                </div>

                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover align-middle">
                            <thead class="text-primary">
                                <tr>
                                    <th class="text-center">No</th>
                                    <th>Role</th>
                                    <th>No of Users</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($roles as $index => $role)
                                    <tr>
                                        <td class="text-center">{{ $index + 1 }}</td>
                                        <td>{{ $role->name }}</td>
                                        <td>{{ $role->users_count }}</td>
                                        <td>
                                            <!-- Edit -->
                                            <button class="btn btn-info" data-bs-toggle="modal"
                                                data-bs-target="#editRoleModal{{ $role->id }}">
                                                <i class="bi bi-pencil-square"></i>
                                            </button>

                                            <!-- Delete -->
                                            <form action="{{ route('role.destroy', $role->id) }}" method="POST"
                                                class="d-inline delete-form">
                                                @csrf
                                                @method('DELETE')
                                                <button type="button" class="btn btn-danger delete-btn"
                                                    data-name="{{ $role->name }}">
                                                    <i class="bi bi-trash-fill"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>

                                    <!-- Edit Modal -->
                                    <div class="modal fade" id="editRoleModal{{ $role->id }}" tabindex="-1"
                                        aria-hidden="true">
                                        <div class="modal-dialog modal-dialog-centered ">
                                            <div class="modal-content">
                                                <form action="{{ route('role.update', $role->id) }}" method="POST">
                                                    @csrf
                                                    @method('PUT')

                                                    @php
                                                        $rolePermissions = $role->permissions ?? [];
                                                    @endphp

                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Edit Role</h5>
                                                        <button type="button" class="btn-close"
                                                            data-bs-dismiss="modal"></button>
                                                    </div>

                                                    <div class="modal-body">

                                                        {{-- Role name --}}
                                                        <div class="mb-3">
                                                            <label class="form-label">Role Name</label>
                                                            <input type="text" class="form-control" name="name"
                                                                value="{{ $role->name }}" required>
                                                        </div>

                                                        <hr>
                                                        <h6 class="fw-bold mb-2">Module Access</h6>

                                                        @foreach ($modules as $key => $module)
                                                            {{-- Single module --}}
                                                            @if (is_string($module))
                                                                <div class="form-check">
                                                                    <label class="form-check-label">
                                                                        <input class="form-check-input" type="checkbox"
                                                                            name="permissions[]"
                                                                            value="{{ $key }}"
                                                                            {{ in_array($key, $rolePermissions) ? 'checked' : '' }}>
                                                                        <span class="form-check-sign"></span>
                                                                        {{ $module }}
                                                                    </label>
                                                                </div>
                                                            @endif

                                                            {{-- Grouped module --}}
                                                            @if (is_array($module))
                                                                <div class="mt-3">
                                                                    <strong>{{ $module['label'] }}</strong>

                                                                    <div class="ms-3">
                                                                        @foreach ($module['items'] as $perm => $label)
                                                                            <div class="form-check">
                                                                                <label class="form-check-label">
                                                                                    <input class="form-check-input"
                                                                                        type="checkbox" name="permissions[]"
                                                                                        value="{{ $perm }}"
                                                                                        {{ in_array($perm, $rolePermissions) ? 'checked' : '' }}>
                                                                                    <span class="form-check-sign"></span>
                                                                                    {{ $label }}
                                                                                </label>
                                                                            </div>
                                                                        @endforeach
                                                                    </div>
                                                                </div>
                                                            @endif
                                                        @endforeach

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
                                        <td colspan="4" class="text-center">No roles found.</td>
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
    <div class="modal fade" id="createRoleModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog  modal-dialog-centered ">
            <form action="{{ route('role.store') }}" method="POST" class="modal-content">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Create New Role</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Role Name</label>
                        <input type="text" class="form-control" name="name" required>
                    </div>

                    <hr>
                    <h6 class="fw-bold">Module Access: </h6>

                    @foreach ($modules as $key => $module)
                        {{-- Single module --}}
                        @if (is_string($module))
                            <div class="form-check">
                                <label class="form-check-label">
                                    <input class="form-check-input" type="checkbox" name="permissions[]"
                                        value="{{ $key }}">
                                    <span class="form-check-sign"></span>
                                    {{ $module }}
                                </label>
                            </div>
                        @endif

                        {{-- Grouped module --}}
                        @if (is_array($module))
                            <div class="mt-3">
                                <strong>{{ $module['label'] }}</strong>

                                <div class="ms-3">
                                    @foreach ($module['items'] as $perm => $label)
                                        <div class="form-check">
                                            <label class="form-check-label">
                                                <input class="form-check-input" type="checkbox" name="permissions[]"
                                                    value="{{ $perm }}">
                                                <span class="form-check-sign"></span>
                                                {{ $label }}
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    @endforeach

                </div>

                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Create</button>
                </div>
            </form>
        </div>
    </div>
@endsection
<script>
    document.addEventListener("DOMContentLoaded", function() {
        document.querySelectorAll(".delete-btn").forEach(button => {
            button.addEventListener("click", function(e) {
                let form = this.closest("form");
                let roleName = this.getAttribute("data-name");

                Swal.fire({
                    title: "Are you sure?",
                    text: "Role '" + roleName + "' will be permanently deleted!",
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
