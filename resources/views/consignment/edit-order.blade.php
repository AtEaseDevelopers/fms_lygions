<!-- Edit Modal -->
<style>
    .form-check-input:checked {
        background-color: #dc3545;
        border-color: #dc3545;
    }

    .form-check-input {
        transform: scale(1.3);
        cursor: pointer;
    }

    .form-check-label {
        vertical-align: middle;
    }
</style>
@php
    use App\Models\Truck;
    use App\Models\Customer;
    use App\Models\DraftCustomer;
    use App\Models\Unit;
    use App\Models\Subcon;

    $truckGroups = Truck::where('is_outsider', false)->select('chassis_type')->distinct()->pluck('chassis_type')->filter()->values()->toArray();
    $trucks = Truck::select('number', 'chassis_type', 'size')->get();
    $subcons = Subcon::select('truck_no', 'chassis_type', 'size')->get();
    $consignors = Customer::ofType('Consignor')
        ->pluck('name')
        ->merge(DraftCustomer::where('type', 'Consignor')->where('migrated', false)->pluck('name'))
        ->unique()
        ->sort()
        ->values()
        ->toArray();

    $consignees = Customer::ofType('Consignee')
        ->pluck('name')
        ->merge(DraftCustomer::where('type', 'Consignee')->where('migrated', false)->pluck('name'))
        ->unique()
        ->sort()
        ->values()
        ->toArray();

    $units = Unit::all();

@endphp

<div class="modal fade" id="editModal{{ $index }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <form action="{{ route('consignment-order.update', $order['id']) }}" method="POST">
                @csrf
                @method('PUT')
                <!-- Header -->
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-pencil-square me-2"></i> Edit Consignment Order
                        ({{ $order['consignment_no'] }})
                    </h5>
                    <button type="button" class="btn-close text-danger" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>

                <!-- Body -->
                <div class="modal-body row g-3">
                    {{-- First row: Load Date & Consignment No --}}
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Pick Up Date <span class="text-danger">*</span></label>
                        <input type="date" class="form-control date-clickable" name="load_date"
                            id="load_date_{{ $index }}" value="{{ $order['load_date'] }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Status <span class="text-danger">*</span></label>
                        <select class="form-select" name="status">
                            <option value="Pending" {{ $order['status'] == 'Pending' ? 'selected' : '' }}>
                                Pending</option>
                            <option value="Planning" {{ $order['status'] == 'Planning' ? 'selected' : '' }}>
                                Planning</option>
                            <option value="Completed" {{ $order['status'] == 'Completed' ? 'selected' : '' }}>
                                Completed</option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold">Pick Up Time</label>
                        <input type="time" class="form-control time-clickable" name="pick_time" id="pick_time"
                            value="{{ $order['pick_time'] }}">
                    </div>


                    <div class="col-md-6">
                        <label class="form-label fw-bold">Pre-Pick</label>
                        <select class="form-select" name="pre_pick">
                            <option value="">-- None --</option>
                            <option value="SELF" {{ ($order['pre_pick'] ?? '') === 'SELF' ? 'selected' : '' }}>SELF</option>
                            <option value="WVS 5404" {{ ($order['pre_pick'] ?? '') === 'WVS 5404' ? 'selected' : '' }}>WVS
                                5404 (1 Ton, Box Truck)</option>
                            <option value="NCR 8825" {{ ($order['pre_pick'] ?? '') === 'NCR 8825' ? 'selected' : '' }}>NCR
                                8825 (3 Tons, Curtain Truck)</option>
                            <option value="BSG 8826" {{ ($order['pre_pick'] ?? '') === 'BSG 8826' ? 'selected' : '' }}>BSG
                                8826 (5 Tons, Box Truck)</option>
                        </select>
                    </div>

                    {{-- Second row: Consignor & Pick Point --}}
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Consignor</label>
                        <input name="consignor" list="consignor_list_{{ $index }}" class="form-control"
                            placeholder="Search or Select Consignor"
                            value="{{ old('consignor', $order['consignor'] ?? '') }}">

                        <datalist id="consignor_list_{{ $index }}">
                            @foreach ($consignors as $consignor)
                                <option value="{{ $consignor }}"></option>
                            @endforeach
                        </datalist>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold">Consignee</label>
                        <input name="consignee" list="consignee_list_{{ $index }}" class="form-control"
                            placeholder="Search or Select Consignee"
                            value="{{ old('consignee', $order['consignee'] ?? '') }}">

                        <datalist id="consignee_list_{{ $index }}">
                            @foreach ($consignees as $consignee)
                                <option value="{{ $consignee }}"></option>
                            @endforeach
                        </datalist>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold">Pick Point <span class="text-danger">*</span></label>
                        <input id="pick_point_{{ $index }}" name="pick_point"
                            list="pick_point_list_{{ $index }}" class="form-control"
                            placeholder="Select or Search Pick Point" value="{{ $order['pick_point'] }}">
                        <datalist id="pick_point_list_{{ $index }}"></datalist>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold">Drop Point <span class="text-danger">*</span></label>
                        <input id="drop_point_{{ $index }}" name="drop_point"
                            list="drop_point_list_{{ $index }}" class="form-control"
                            placeholder="Select or Search Drop Point" value="{{ $order['drop_point'] }}">
                        <datalist id="drop_point_list_{{ $index }}"></datalist>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold">Pick Address <span class="text-danger">*</span></label>
                        <input id="pick_address_{{ $index }}" name="pick_address"
                            list="pick_address_list_{{ $index }}" class="form-control"
                            placeholder="Select or Search Pick Address" value="{{ $order['pick_address'] }}">
                        <datalist id="pick_address_list_{{ $index }}"></datalist>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold">Drop Address <span class="text-danger">*</span></label>
                        <input id="drop_address_{{ $index }}" name="drop_address"
                            list="drop_address_list_{{ $index }}" class="form-control"
                            placeholder="Select or Search Drop Address" value="{{ $order['drop_address'] }}">
                        <datalist id="drop_address_list_{{ $index }}"></datalist>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold">Pick Truck Size</label>
                        <select class="form-select" name="pick_truck_size" id="pick_truck_size_{{ $index }}">
                            <option value="">-- Select Size --</option>
                            <option value="Small" {{ $order['pick_truck_size'] === 'Small' ? 'selected' : '' }}>
                                Small</option>
                            <option value="Any" {{ $order['pick_truck_size'] === 'Any' ? 'selected' : '' }}>Any
                            </option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold">Drop Truck Size</label>
                        <select class="form-select" name="drop_truck_size" id="drop_truck_size_{{ $index }}">
                            <option value="">-- Select Size --</option>
                            <option value="Small" {{ $order['drop_truck_size'] === 'Small' ? 'selected' : '' }}>
                                Small</option>
                            <option value="Any" {{ $order['drop_truck_size'] === 'Any' ? 'selected' : '' }}>Any
                            </option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold">Pick Truck Type</label>
                        <select class="form-select truck-type" name="pick_truck_type"
                            id="pick_truck_type_{{ $index }}">
                            <option value="">-- Select Type --</option>
                            <option value="any" {{ $order['pick_truck_type'] == 'any' ? 'selected' : '' }}>Any
                            </option>
                            @foreach ($truckGroups as $group)
                                <option value="{{ $group }}"
                                    {{ $order['pick_truck_type'] == $group ? 'selected' : '' }}>
                                    {{ $group }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold">Drop Truck Type</label>
                        <select class="form-select truck-type" name="drop_truck_type"
                            id="drop_truck_type_{{ $index }}">
                            <option value="">-- Select Type --</option>
                            <option value="any" {{ $order['drop_truck_type'] == 'any' ? 'selected' : '' }}>Any
                            </option>
                            @foreach ($truckGroups as $group)
                                <option value="{{ $group }}"
                                    {{ $order['drop_truck_type'] == $group ? 'selected' : '' }}>
                                    {{ $group }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold">Truck Number</label>
                        <input name="truck_number" list="truck_list_{{ $index }}" class="form-control"
                            id="truck_number_{{ $index }}" placeholder="Search or Select Truck"
                            value="{{ $order['truck_number'] ?? '' }}">
                        <datalist id="truck_list_{{ $index }}">
                            @foreach ($trucks as $truck)
                                <option value="{{ $truck->number }}"></option>
                            @endforeach
                        </datalist>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold">Remarks</label>
                        <input type="text" class="form-control" name="remarks" value="{{ $order['remarks'] }}">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold">Billing remarks</label>
                        <input type="text" class="form-control" name="billing_remark"
                            value="{{ $order['billing_remark'] }}">
                    </div>

                    <div class="col-md-6 d-flex">
                        <div class="form-label form-switch text-center mt-4">
                            <!-- Hidden field ensures "false" is sent when unchecked -->
                            <input type="hidden" name="express_mode" value="0">

                            <input class="form-check-input" type="checkbox" role="switch" name="express_mode"
                                id="expressModeSwitch{{ $order['index'] }}" value="1"
                                {{ $order['express_mode'] ? 'checked' : '' }}>
                            <label class="form-check-label fw-bold ms-2"
                                for="expressModeSwitch{{ $order['index'] }}">
                                Express Mode
                            </label>
                        </div>
                        <div class="form-label form-switch text-center mt-4 ms-4">
                            <!-- Hidden field ensures "false" is sent when unchecked -->
                            <input type="hidden" name="self_delivery" value="0">

                            <input class="form-check-input" type="checkbox" role="switch" name="self_delivery"
                                id="selfDeliverySwitch{{ $order['index'] }}" value="1"
                                {{ !empty($order['self_delivery']) ? 'checked' : '' }}>
                            <label class="form-check-label fw-bold ms-2"
                                for="selfDeliverySwitch{{ $order['index'] }}">
                                Self Delivery
                            </label>
                        </div>
                    </div>

                    <div class="col-md-12">
                        <div class="mb-3">
                            <div class="quantityUnitContainer">
                                <div class="row g-2 fw-bold mb-1">
                                    <div class="col-md-5">Quantity</div>
                                    <div class="col-md-5">Unit</div>
                                    <div class="col-md-2"></div>
                                </div>

                                @php
                                    $quantities = is_string($order['quantity'] ?? '')
                                        ? json_decode($order['quantity'], true)
                                        : $order['quantity'] ?? [];
                                    $orderUnits = is_string($order['unit'] ?? '')
                                        ? json_decode($order['unit'], true)
                                        : $order['unit'] ?? [];
                                    $count = max(count($quantities), count($orderUnits));
                                @endphp

                                @for ($i = 0; $i < $count; $i++)
                                    <div class="row g-2 align-items-end mb-2 quantity-unit-row">
                                        <div class="col-md-5">
                                            <input type="number" class="form-control" name="quantity[]"
                                                value="{{ $quantities[$i] ?? '' }}">
                                        </div>
                                        <div class="col-md-5">
                                            <select class="form-select" name="unit[]">
                                                <option value="">-- Select Unit --</option>
                                                @foreach ($units as $u)
                                                    <option value="{{ $u->unit }}"
                                                        {{ isset($orderUnits[$i]) && $orderUnits[$i] === $u->unit ? 'selected' : '' }}>
                                                        {{ strtoupper($u->unit) }} — {{ $u->desc }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-2 d-flex gap-1">
                                            <button type="button" class="btn btn-success btn-sm addRow">
                                                <i class="bi bi-plus"></i>
                                            </button>
                                            <button type="button" class="btn btn-danger btn-sm removeRow" {!! $count <= 1 ? 'style="display:none;"' : '' !!}>
                                                <i class="bi bi-dash"></i>
                                            </button>
                                        </div>
                                    </div>
                                @endfor

                                @if ($count === 0)
                                    <div class="row g-2 align-items-end mb-2 quantity-unit-row">
                                        <div class="col-md-5">
                                            <input type="number" class="form-control" name="quantity[]"
                                                placeholder="Enter quantity">
                                        </div>
                                        <div class="col-md-5">
                                            <select class="form-select" name="unit[]">
                                                <option value="">-- Select Unit --</option>
                                                @foreach ($units as $unit)
                                                    <option value="{{ $unit->unit }}">
                                                        {{ strtoupper($unit->unit) }} — {{ $unit->desc }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-2 d-flex gap-1">
                                            <button type="button" class="btn btn-success btn-sm addRow">
                                                <i class="bi bi-plus"></i>
                                            </button>
                                            <button type="button" class="btn btn-danger btn-sm removeRow" style="display:none;">
                                                <i class="bi bi-dash"></i>
                                            </button>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.getElementById('load_date').addEventListener('change', function() {
        const loadDate = this.value;
        // No need to auto-fill pick_time anymore since it's just a time input
    });

    document.querySelectorAll('input[type="date"].date-clickable').forEach(input => {
        input.addEventListener('click', function() {
            this.showPicker();
        });
    });

    // Add click handler for time input
    document.querySelectorAll('input[type="time"].time-clickable').forEach(input => {
        input.addEventListener('click', function() {
            this.showPicker();
        });
    });
    document.addEventListener('DOMContentLoaded', function() {
        const modalId = {{ $index }};

        // Date and time click handlers
        const loadDateInput = document.getElementById(`load_date_${modalId}`);
        const pickTimeInput = document.getElementById(`pick_time_${modalId}`);

        if (loadDateInput) {
            loadDateInput.addEventListener('click', function() {
                this.showPicker();
            });
        }

        if (pickTimeInput) {
            pickTimeInput.addEventListener('click', function() {
                this.showPicker();
            });
        }

        const consignorInput = document.querySelector(`#editModal{{ $index }} input[name="consignor"]`);
        const consigneeInput = document.querySelector(`#editModal{{ $index }} input[name="consignee"]`);

        const pickPointList = document.getElementById(`pick_point_list_${modalId}`);
        const dropPointList = document.getElementById(`drop_point_list_${modalId}`);
        const pickAddressList = document.getElementById(`pick_address_list_${modalId}`);
        const dropAddressList = document.getElementById(`drop_address_list_${modalId}`);

        /* ---------------- LOCATION DATALISTS ---------------- */

        function populateDatalists(locations, typeFilter, pointList, addressList) {
            pointList.innerHTML = '';
            addressList.innerHTML = '';

            locations
                .filter(loc => Array.isArray(loc.types) &&
                    loc.types.some(t => t.toLowerCase() === typeFilter))
                .forEach(loc => {
                    const optionPoint = document.createElement('option');
                    optionPoint.value = loc.state;
                    pointList.appendChild(optionPoint);

                    const optionAddress = document.createElement('option');
                    optionAddress.value = loc.address;
                    addressList.appendChild(optionAddress);
                });
        }

        function fetchCustomerLocations(name, type) {
            if (!name) return;

            fetch(`/customers/${encodeURIComponent(name)}/locations`)
                .then(res => res.json())
                .then(data => {
                    if (!data.locations) return;

                    if (type === 'consignor') {
                        populateDatalists(data.locations, 'pickup', pickPointList, pickAddressList);
                    } else if (type === 'consignee') {
                        populateDatalists(data.locations, 'dropoff', dropPointList, dropAddressList);
                    }
                })
                .catch(err => console.error('Error fetching locations:', err));
        }

        if (consignorInput.value) fetchCustomerLocations(consignorInput.value, 'consignor');
        if (consigneeInput.value) fetchCustomerLocations(consigneeInput.value, 'consignee');

        consignorInput.addEventListener('change', () =>
            fetchCustomerLocations(consignorInput.value, 'consignor')
        );
        consigneeInput.addEventListener('change', () =>
            fetchCustomerLocations(consigneeInput.value, 'consignee')
        );

        /* ---------------- TRUCK FILTERING ---------------- */

        const allTrucks = @json($trucks);
        const allSubcons = @json($subcons ?? []);
        let availableTrucks = allTrucks;
        let availableSubcons = allSubcons;
        let availableTempTrucks = [];
        const currentTruckNumber = document.getElementById(`truck_number_${modalId}`)?.value || '';

        const pickTypeEl = document.getElementById(`pick_truck_type_${modalId}`);
        const dropTypeEl = document.getElementById(`drop_truck_type_${modalId}`);
        const pickSizeEl = document.getElementById(`pick_truck_size_${modalId}`);
        const dropSizeEl = document.getElementById(`drop_truck_size_${modalId}`);
        const truckDatalist = document.getElementById(`truck_list_${modalId}`);
        const loadDateEl = document.getElementById(`load_date_${modalId}`);

        function normalize(v) {
            return (v || '').toString().trim().toLowerCase();
        }

        function fetchAvailableTrucks(date) {
            if (!date) {
                availableTrucks = allTrucks;
                availableSubcons = allSubcons;
                availableTempTrucks = [];
                updateTruckNumbers();
                return;
            }
            fetch(`/api/available-trucks?date=${date}`)
                .then(r => r.json())
                .then(data => {
                    availableTrucks = data.trucks;
                    availableSubcons = data.subcons;
                    availableTempTrucks = data.temp_trucks || [];
                    updateTruckNumbers();
                })
                .catch(() => {
                    availableTrucks = allTrucks;
                    availableSubcons = allSubcons;
                    availableTempTrucks = [];
                    updateTruckNumbers();
                });
        }

        function updateTruckNumbers() {
            const pickType = normalize(pickTypeEl.value);
            const dropType = normalize(dropTypeEl.value);
            const pickSize = normalize(pickSizeEl.value);
            const dropSize = normalize(dropSizeEl.value);

            truckDatalist.innerHTML = '';

            function isValid(truck) {
                if (!truck.chassis_type) return false;

                const tType = normalize(truck.chassis_type);
                const tSize = normalize(truck.size);

                let matchPick = true;
                let matchDrop = true;

                // "any" or empty = no type filter; "any" or empty = no size filter
                if (pickType && pickType !== 'any') {
                    matchPick = tType === pickType;
                }
                if (matchPick && pickSize && pickSize !== 'any' && tSize) {
                    matchPick = tSize === pickSize;
                }

                if (dropType && dropType !== 'any') {
                    matchDrop = tType === dropType;
                }
                if (matchDrop && dropSize && dropSize !== 'any' && tSize) {
                    matchDrop = tSize === dropSize;
                }

                return matchPick && matchDrop;
            }

            // Add filtered trucks
            const addedNumbers = new Set();
            availableTrucks.filter(isValid).forEach(t => {
                const opt = document.createElement('option');
                opt.value = t.number;
                truckDatalist.appendChild(opt);
                addedNumbers.add(t.number);
            });

            // Ensure currently assigned truck is always in the list
            if (currentTruckNumber && !addedNumbers.has(currentTruckNumber) && !currentTruckNumber.includes('(Subcon)')) {
                const opt = document.createElement('option');
                opt.value = currentTruckNumber;
                truckDatalist.appendChild(opt);
            }

            // Add filtered subcons
            availableSubcons.filter(isValid).forEach(s => {
                const opt = document.createElement('option');
                opt.value = s.truck_no + ' (Subcon)';
                truckDatalist.appendChild(opt);
            });

            // Add ALL temporary trucks for this date — no type/size filter.
            // Once a real subcon is assigned, surface the subcon's truck no with " (Subcon)";
            // otherwise show the temp label with " (Temp)".
            availableTempTrucks.forEach(t => {
                const opt = document.createElement('option');
                if (t.subcon_id && t.subcon_truck_no) {
                    opt.value = t.subcon_truck_no + ' (Subcon)';
                } else {
                    opt.value = t.truck_no + ' (Temp)';
                }
                truckDatalist.appendChild(opt);
            });

            // Force browser to refresh datalist
            const truckInput = document.getElementById(`truck_number_${modalId}`);
            if (truckInput) {
                truckInput.removeAttribute('list');
                setTimeout(() => truckInput.setAttribute('list', `truck_list_${modalId}`), 0);
            }
        }

        // Fetch available trucks on load based on current load_date
        if (loadDateEl && loadDateEl.value) {
            fetchAvailableTrucks(loadDateEl.value);
        } else {
            updateTruckNumbers();
        }

        // Re-fetch when load_date changes
        if (loadDateEl) {
            loadDateEl.addEventListener('change', function() {
                fetchAvailableTrucks(this.value);
            });
        }

        // Re-filter on truck type/size change
        [pickTypeEl, dropTypeEl, pickSizeEl, dropSizeEl].forEach(el => {
            if (el) el.addEventListener('change', updateTruckNumbers);
        });
    });
</script>
