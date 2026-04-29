@extends('component.layout')
@section('title', 'Truck')
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
                <div class="card-header">
                    {{-- <h4 class="card-title"> Simple Table</h4> --}}
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center flex-wrap mb-3">
                        <!-- Search Form -->
                        <form class="d-flex  gap-3 align-items-stretch" style="width: 50%;" id="filterForm">
                            <div class="input-group no-border w-100">
                                <input type="text" value="" class="form-control" name="search"
                                    placeholder="Search...">
                                <div class="input-group-append">
                                    <div class="input-group-text">
                                        <i class="nc-icon nc-zoom-split"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="input-group w-100">
                                <select id="as" name="as" class="form-control">
                                    <option value="" selected disabled> Group </option>
                                    <option value="consignor">Group A</option>
                                    <option value="consignee">Group B</option>
                                </select>
                            </div>

                            <div class="input-group w-100">
                                <button type="reset" class="btn btn-danger d-none" style="margin: 0;" id="clearBtn"> <i
                                        class="bi bi-x"></i> </button>
                            </div>
                        </form>

                        <!-- Buttons -->
                        <div class="d-flex gap-2">
                            <button class="btn btn-outline-primary"
                                style="border-radius: 0.2rem; display: inline-flex; align-items: center;">
                                <i class="bi bi-file-excel-fill" style="font-size: 20px; margin-right: 5px;"></i> Export
                            </button>
                            <a href="{{ route('truck.create') }}" class="btn btn-primary"
                                style="border-radius: 0.2rem; display: inline-flex; align-items: center;"
                                style="border-radius: 0.2rem;"><i class="nc-icon nc-ambulance"
                                    style="font-size: 20px; margin-right: 5px;"></i> Create New Truck</a>
                        </div>
                    </div>

                    <div id="tableScrollTop" style="overflow-x:auto; overflow-y:hidden;"></div>

                    <div class="table-responsive" id="tableScrollBottom" style="overflow-x:auto;">
                        <table id="truckTable"
                            class="table table-striped table-bordered align-middle text-center table-nowrap">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>
                                        <a class="text-dark text-decoration-none"
                                            href="{{ route('truck.index', array_merge(request()->query(), ['sort_by' => 'number', 'sort_order' => $sortOrder === 'asc' && $sortBy === 'number' ? 'desc' : 'asc'])) }}">
                                            Number
                                            @if ($sortBy === 'number')
                                                <i
                                                    class="bi bi-caret-{{ $sortOrder === 'asc' ? 'up' : 'down' }}-fill"></i>
                                            @endif
                                        </a>
                                    </th>
                                    <th>Team</th>
                                    <th>
                                        <a class="text-dark text-decoration-none"
                                            href="{{ route('truck.index', array_merge(request()->query(), ['sort_by' => 'group', 'sort_order' => $sortOrder === 'asc' && $sortBy === 'group' ? 'desc' : 'asc'])) }}">
                                            Group
                                            @if ($sortBy === 'group')
                                                <i
                                                    class="bi bi-caret-{{ $sortOrder === 'asc' ? 'up' : 'down' }}-fill"></i>
                                            @endif
                                        </a>
                                    </th>
                                    <th>Size</th>
                                    <th>
                                        <a class="text-dark text-decoration-none"
                                            href="{{ route('truck.index', array_merge(request()->query(), ['sort_by' => 'tonnage', 'sort_order' => $sortOrder === 'asc' && $sortBy === 'tonnage' ? 'desc' : 'asc'])) }}">
                                            Tonnage
                                            @if ($sortBy === 'tonnage')
                                                <i
                                                    class="bi bi-caret-{{ $sortOrder === 'asc' ? 'up' : 'down' }}-fill"></i>
                                            @endif
                                        </a>
                                    </th>
                                    <th>
                                        <a class="text-dark text-decoration-none"
                                            href="{{ route('truck.index', array_merge(request()->query(), ['sort_by' => 'floor_space', 'sort_order' => $sortOrder === 'asc' && $sortBy === 'floor_space' ? 'desc' : 'asc'])) }}">
                                            <span class="d-inline-block" style="white-space: normal;"> Floor Space (In sqft)
                                            </span>

                                            @if ($sortBy === 'floor_space')
                                                <i
                                                    class="bi bi-caret-{{ $sortOrder === 'asc' ? 'up' : 'down' }}-fill"></i>
                                            @endif
                                        </a>
                                    </th>
                                    <th>
                                        <a class="text-dark text-decoration-none"
                                            href="{{ route('truck.index', array_merge(request()->query(), ['sort_by' => 'chassis_type', 'sort_order' => $sortOrder === 'asc' && $sortBy === 'chassis_type' ? 'desc' : 'asc'])) }}">
                                            <span class="d-inline-block" style="white-space: normal;"> Chassis Type</span>
                                            @if ($sortBy === 'chassis_type')
                                                <i
                                                    class="bi bi-caret-{{ $sortOrder === 'asc' ? 'up' : 'down' }}-fill"></i>
                                            @endif
                                        </a>
                                    </th>
                                    <th> <span class="d-inline-block" style="white-space: normal;"> Next Inspection
                                            Date</span></th>

                                    <th class="extra-col"> <span class="d-inline-block" style="white-space: normal;"> Next
                                            Tyre Date</span>
                                    </th>

                                    <th class="extra-col"> <span class="d-inline-block" style="white-space: normal;"> Next
                                            Permit Date</span>
                                    </th>
                                    <th class="extra-col"> <span class="d-inline-block" style="white-space: normal;"> Next
                                            Fire Extinguisher
                                            Date</span></th>
                                    <th class="extra-col"> <span class="d-inline-block" style="white-space: normal;"> Next
                                            Roadtax Date</span>
                                    </th>
                                    <th class="extra-col"> <span class="d-inline-block" style="white-space: normal;"> Next
                                            Insurance
                                            Date</span></th>
                                    <th class="extra-col"> <span class="d-inline-block" style="white-space: normal;"> Next
                                            Others Date</span>
                                    </th>
                                    <th> <button class="btn btn-sm btn-outline-secondary" id="toggleExtraCols">
                                            <i class="bi bi-bar-chart-steps"></i>
                                        </button></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($trucks as $i => $truck)
                                    <tr>
                                        <td>{{ $i + 1 }}</td>
                                        <td>{{ $truck->number }}</td>
                                        <td>{{ $truck->team }}</td>
                                        <td>{{ $truck->group }}</td>
                                        <td>{{ $truck->size }}</td>
                                        <td>{{ $truck->tonnage }}</td>
                                        <td>{{ $truck->floor_space }}</td>
                                        <td>{{ $truck->chassis_type }}</td>
                                        <td>{{ $truck->next_inspection }}</td>
                                        <td class="extra-col">{{ $truck->next_tyre }}</td>
                                        <td class="extra-col">{{ $truck->next_permit }}</td>
                                        <td class="extra-col">{{ $truck->next_extinguisher }}</td>
                                        <td class="extra-col">{{ $truck->next_roadtax }}</td>
                                        <td class="extra-col">{{ $truck->next_insurance }}</td>
                                        <td class="extra-col">{{ $truck->next_others }}</td>
                                        <td>
                                            <button class="btn btn-info " data-bs-toggle="modal"
                                                data-bs-target="#editModal{{ $truck->id }}">
                                                <i class="bi bi-pencil-square"></i>
                                            </button>

                                            <!-- Delete -->
                                            <form action="{{ route('truck.destroy', $truck->id) }}" method="POST"
                                                class="d-inline delete-form">
                                                @csrf
                                                @method('DELETE')
                                                <button type="button" class="btn btn-danger delete-btn"
                                                    data-number="{{ $truck->number }}">
                                                    <i class="bi bi-trash-fill"></i>
                                                </button>
                                            </form>

                                        </td>
                                    </tr>

                                    <!-- Edit Modal -->
                                    <div class="modal fade" id="editModal{{ $truck->id }}" tabindex="-1">
                                        <div class="modal-dialog modal-dialog-centered">
                                            <div class="modal-content">
                                                <form action="{{ route('truck.update', $truck->id) }}" method="POST"
                                                    class="modal-content">
                                                    @csrf
                                                    @method('PUT')
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Edit Truck</h5>
                                                        <button type="button" class="btn-close"
                                                            data-bs-dismiss="modal"></button>
                                                    </div>

                                                    <div class="modal-body">
                                                        <div class="mb-3">
                                                            <label>Number</label>
                                                            <input type="text" name="number"
                                                                value="{{ $truck->number }}" class="form-control"
                                                                required>
                                                        </div>

                                                        <div class="mb-3">
                                                            <label>Team</label>
                                                            <select name="team" class="form-select"
                                                                        id="team">
                                                                        <option value="" disabled
                                                                            {{ $truck->team == null ? 'selected' : '' }}>
                                                                            -- Select Team --</option>
                                                                        <option value="MY"
                                                                            {{ $truck->team == 'MY' ? 'selected' : '' }}>MY Team
                                                                        </option>
                                                                        <option value="SG"
                                                                            {{ $truck->team == 'SG' ? 'selected' : '' }}>SG Team
                                                                        </option>
                                                                    </select>
                                                        </div>

                                                        <div class="mb-3">
                                                            <label>Group</label>
                                                            <input type="text" name="group"
                                                                value="{{ $truck->group }}" class="form-control"
                                                                required>
                                                        </div>

                                                        <div class="mb-3">
                                                            <label>Size</label>
                                                             <select name="size" class="form-select"
                                                                        id="size">
                                                                        <option value="" disabled
                                                                            {{ $truck->size == null ? 'selected' : '' }}>
                                                                            -- Select Size --</option>
                                                                        <option value="Any"
                                                                            {{ $truck->size == 'Any' ? 'selected' : '' }}>Any
                                                                        </option>
                                                                        <option value="Small"
                                                                            {{ $truck->size == 'Small' ? 'selected' : '' }}>Small
                                                                    </select>
                                                        </div>

                                                        <div class="mb-3">
                                                            <label>Tonnage</label>
                                                            <input type="number" step="0.01" name="tonnage"
                                                                value="{{ $truck->tonnage }}" class="form-control"
                                                                required>
                                                        </div>

                                                        <div class="mb-3">
                                                            <label>Floor Space (sqft)</label>
                                                            <input type="number" step="0.01" name="floor_space"
                                                                value="{{ $truck->floor_space }}" class="form-control"
                                                                required>
                                                        </div>

                                                        <div class="mb-3">
                                                            <label>Chassis Type</label>
                                                            <select class="form-select" name="chassis_type" required>
                                                                <option disabled>Select Type</option>
                                                                <option value="open"
                                                                    {{ $truck->chassis_type == 'open' ? 'selected' : '' }}>
                                                                    Open</option>
                                                                <option value="curtain"
                                                                    {{ $truck->chassis_type == 'curtain' ? 'selected' : '' }}>
                                                                    Curtain</option>
                                                                <option value="tailgate"
                                                                    {{ $truck->chassis_type == 'tailgate' ? 'selected' : '' }}>
                                                                    Tailgate</option>
                                                            </select>
                                                        </div>

                                                        <!-- Date fields -->
                                                        <div class="mb-3">
                                                            <label>Next Inspection Date</label>
                                                            <input type="date" name="next_inspection"
                                                                value="{{ $truck->next_inspection }}"
                                                                class="form-control">
                                                        </div>

                                                        <div class="mb-3">
                                                            <label>Next Tyre Date</label>
                                                            <input type="date" name="next_tyre"
                                                                value="{{ $truck->next_tyre }}" class="form-control">
                                                        </div>

                                                        <div class="mb-3">
                                                            <label>Next Permit Date</label>
                                                            <input type="date" name="next_permit"
                                                                value="{{ $truck->next_permit }}" class="form-control">
                                                        </div>

                                                        <div class="mb-3">
                                                            <label>Next Fire Extinguisher Date</label>
                                                            <input type="date" name="next_extinguisher"
                                                                value="{{ $truck->next_extinguisher }}"
                                                                class="form-control">
                                                        </div>

                                                        <div class="mb-3">
                                                            <label>Next Roadtax Date</label>
                                                            <input type="date" name="next_roadtax"
                                                                value="{{ $truck->next_roadtax }}" class="form-control">
                                                        </div>

                                                        <div class="mb-3">
                                                            <label>Next Insurance Date</label>
                                                            <input type="date" name="next_insurance"
                                                                value="{{ $truck->next_insurance }}"
                                                                class="form-control">
                                                        </div>

                                                        <div class="mb-3">
                                                            <label>Next Others Date</label>
                                                            <input type="date" name="next_others"
                                                                value="{{ $truck->next_others }}" class="form-control">
                                                        </div>
                                                    </div>

                                                    <div class="modal-footer">
                                                        <button type="submit" class="btn btn-dark">Update</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center">No trucks found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
        document.getElementById('toggleExtraCols').addEventListener('click', function() {
            const extraCols = document.querySelectorAll('.extra-col');
            extraCols.forEach(col => {
                col.style.display = (col.style.display === 'none' || col.style.display === '') ?
                    'table-cell' :
                    'none';
            });
        });
    </script>

    @if (session('swal'))
        <script>
            Swal.fire(@json(session('swal')));
        </script>
    @endif

@endsection

<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    document.addEventListener("DOMContentLoaded", function() {

        const topScroll = document.getElementById("tableScrollTop");
        const bottomScroll = document.getElementById("tableScrollBottom");

        // make top scroller width = table width
        topScroll.innerHTML = "<div style='width:" + bottomScroll.scrollWidth + "px; height:1px;'></div>";

        // sync scrolling
        topScroll.addEventListener("scroll", () => {
            bottomScroll.scrollLeft = topScroll.scrollLeft;
        });
        bottomScroll.addEventListener("scroll", () => {
            topScroll.scrollLeft = bottomScroll.scrollLeft;
        });
        document.querySelectorAll(".delete-btn").forEach(button => {
            button.addEventListener("click", function() {
                let form = this.closest("form");
                let truckNumber = this.getAttribute("data-number");

                Swal.fire({
                    title: "Are you sure?",
                    text: "Truck '" + truckNumber + "' will be permanently deleted!",
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
