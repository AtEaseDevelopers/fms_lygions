@php
    $isTemp = $isTemp ?? false;
@endphp
@if ($isTemp)
    <div class="text-start" id="tempCellDetails" data-temp-id="{{ $tempTruckId }}">
        <table class="table table-bordered table-sm">
            <tbody>
                <tr>
                    <th style="width: 30%;">Temp Truck Label</th>
                    <td><strong>{{ $truckNumber }}</strong> ({{ $location }})
                        <span class="badge bg-secondary ms-1">Temp</span>
                        @if (!empty($assignedSubcon))
                            <span class="badge bg-success ms-1">Assigned</span>
                        @endif
                    </td>
                </tr>
                <tr>
                    <th>Date</th>
                    <td>{{ \Carbon\Carbon::parse($date)->format('d M Y') }}</td>
                </tr>
                <tr>
                    <th>Type / Size</th>
                    <td>{{ $tempMeta['chassis_type'] ?? '-' }} / {{ $tempMeta['size'] ?? '-' }}</td>
                </tr>
                <tr>
                    <th>Subcon</th>
                    <td>
                        @if (!empty($assignedSubcon))
                            <div class="mb-2">
                                <strong>{{ $assignedSubcon->subcon_name }}</strong>
                                <span class="text-muted">— truck {{ $assignedSubcon->truck_no }}</span>
                            </div>
                        @endif
                        <form id="assignSubconForm" class="d-flex gap-2 align-items-center">
                            <select name="subcon_id" id="assignSubconSelect" class="form-select form-select-sm">
                                <option value="">-- Select subcon to assign --</option>
                                @foreach ($candidateSubcons as $s)
                                    <option value="{{ $s->id }}"
                                        {{ optional($assignedSubcon)->id === $s->id ? 'selected' : '' }}>
                                        {{ $s->subcon_name }} ({{ $s->truck_no }})
                                        @if ($s->size)
                                            — {{ $s->size }}
                                        @endif
                                    </option>
                                @endforeach
                            </select>
                            <button type="submit" class="btn btn-primary btn-sm">
                                {{ !empty($assignedSubcon) ? 'Update' : 'Assign' }}
                            </button>
                            @if (!empty($assignedSubcon))
                                <button type="button" id="unassignSubconBtn" class="btn btn-outline-secondary btn-sm">
                                    Unassign
                                </button>
                            @endif
                        </form>
                        @if (count($candidateSubcons) === 0 && empty($assignedSubcon))
                            <small class="text-muted d-block mt-1">
                                No registered subcons match this type / size.
                            </small>
                        @endif
                        <small class="text-muted d-block mt-2">
                            Assigning a subcon replaces the placeholder label with the subcon's actual truck number.
                            Existing consignments on this date/location are re-pointed automatically.
                        </small>
                    </td>
                </tr>
                <tr>
                    <th>Consignors</th>
                    <td>
                        @if (count($consignors) === 0)
                            <em class="text-muted">No consignments assigned to this label yet.</em>
                        @else
                            <ul class="mb-0 ps-3">
                                @foreach ($consignors as $c)
                                    <li>{{ $c['name'] }}</li>
                                @endforeach
                            </ul>
                        @endif
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
@else
<div class="text-start">
    <form id="updateAvailabilityForm" onsubmit="handleFormSubmit(event, this)">
        <table class="table table-bordered table-sm">
            <tbody>
                <tr>
                    <th style="width: 30%;">Truck</th>
                    <td>{{ $truckNumber }} ({{ $location }})</td>
                </tr>
                <tr>
                    <th>Date</th>
                    <td>{{ \Carbon\Carbon::parse($date)->format('d M Y') }}</td>
                </tr>
                @if ($availability?->status == null || $consignments->count() > 0)
                    <tr>
                        <th>Driver</th>
                        <td>
                            @php
                                $drivers = \App\Models\Driver::orderBy('name')->get();

                                // Resolve the driver that should appear selected: the explicit
                                // consignment.driver if any consignment has one, else the
                                // truck's default driver from Driver.default_lorry_id.
                                $assignedDriverName = $consignments
                                    ->pluck('driver')
                                    ->filter(fn ($v) => !empty(trim((string) $v)))
                                    ->first();
                                if (empty($assignedDriverName)) {
                                    $truckRecord = \App\Models\Truck::where('number', $truckNumber)->first();
                                    if ($truckRecord) {
                                        $assignedDriverName = optional(
                                            \App\Models\Driver::where('default_lorry_id', $truckRecord->id)->first()
                                        )->name;
                                    }
                                }
                                $assignedKey = strtolower(trim((string) $assignedDriverName));
                            @endphp
                            <select name="driver" class="form-select form-select-sm w-auto d-inline">
                                <option value="">— Unassigned —</option>
                                @foreach ($drivers as $driver)
                                    <option value="{{ $driver->name }}"
                                        {{ strtolower(trim((string) $driver->name)) === $assignedKey ? 'selected' : '' }}>
                                        {{ $driver->name }} ({{ $driver->phone_my ?? ($driver->phone_sg ?? '-') }})
                                    </option>
                                @endforeach
                            </select>

                        </td>
                    </tr>
                    <tr>
                        <th>Consignors</th>
                        <td>
                            <table class="table table-sm mb-0">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Capacity</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($consignors as $c)
                                        <tr>
                                            <td>{{ $c['name'] }}</td>
                                            <td>{{ $c['capacity'] }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <th>Total Capacity</th>
                        <td>{{ $totalCapacity - $usedCapacity }}/{{ $totalCapacity }}</td>
                    </tr>
                @endif
                @if ($availability?->status != null && $consignments->count() == 0)
                    <tr>
                        <th>Availability</th>
                        <td>
                            <select name="status" class="form-select form-select-sm w-auto d-inline">
                                <option value="available"
                                    {{ $availability?->status == 'available' ? 'selected' : '' }}>
                                    Available</option>
                                <option value="off-day" {{ $availability?->status == 'off-day' ? 'selected' : '' }}>
                                    Off-Day
                                </option>
                                <option value="maintenance"
                                    {{ $availability?->status == 'maintenance' ? 'selected' : '' }}>Maintenance
                                </option>
                            </select>
                        </td>
                    </tr>
                @endif
            </tbody>
        </table>



        <!-- Update Button -->
        @if ($consignments->count() == 0)
            <input type="hidden" name="truck" value="{{ $truckNumber }}">
            <input type="hidden" name="date" value="{{ $date }}">
            <div class="text-end mt-2">
               <button type="button"
                        class="btn btn-danger btn-sm"
                        onclick="handleDeleteAvailability('{{ $truckNumber }}', '{{ $date }}')">
                    Delete
                </button>
                <button type="submit" class="btn btn-primary btn-sm">Update</button>
            </div>
        @endif
    </form>
</div>
@endif

