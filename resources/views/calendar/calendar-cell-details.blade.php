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
                                $drivers = \App\Models\Driver::all();
                            @endphp
                            <select name="driver" class="form-select form-select-sm w-auto d-inline">
                                @foreach ($consignments as $consignment)
                                    @if ($consignment->driverInfo)
                                        <option value="{{ $consignment->driverInfo->name }}" selected>
                                            {{ $consignment->driverInfo->name }}
                                            ({{ $consignment->driverInfo->phone_my ?? ($consignment->driverInfo->phone_sg ?? '-') }})
                                        </option>
                                    @endif
                                @endforeach

                                {{-- Add all other drivers from database that might not be in consignments --}}
                                @foreach ($drivers as $driver)
                                    @if (!in_array($driver->name, $consignments->pluck('driver')->toArray()))
                                        <option value="{{ $driver->name }}">
                                            {{ $driver->name }} ({{ $driver->phone_my ?? ($driver->phone_sg ?? '-') }})
                                        </option>
                                    @endif
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

