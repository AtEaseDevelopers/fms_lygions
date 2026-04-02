<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<!-- Add Modal -->
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

    .select2-container .select2-selection--single {
        height: calc(2.25rem + 2px);
        padding: .375rem .75rem;
    }

    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: calc(2.25rem + 2px);
    }
</style>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />


@php
    use App\Models\Truck;
    use App\Models\Customer;
    use App\Models\DraftCustomer;
    use App\Models\Unit;
    use App\Models\Subcon;

    $truckGroups = Truck::select('chassis_type')->distinct()->pluck('chassis_type')->filter()->values()->toArray();

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
<div class="modal fade" id="addModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">

            <form action="{{ route('consignment-order.store') }}" method="POST">
                @csrf
                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <!-- Header -->
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-plus-circle me-2"></i> Add Truck Planning
                    </h5>
                    <button type="button" class="btn-close text-danger" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>

                <!-- Body -->
                <div class="modal-body row g-3">
                    {{-- First row: Load Date & Status --}}
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Pick Up Date</label>
                        <input type="date" class="form-control date-clickable" name="load_date" id="load_date"
                            required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Status</label>
                        <select class="form-select" name="status" required>
                            <option value="Pending">Pending</option>
                            <option value="Planning">Planning</option>
                            <option value="Completed">Completed</option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold">Pick Up Time</label>
                        <input type="time" class="form-control time-clickable" name="pick_time" id="pick_time">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold">Pre-Pick</label>
                        <select class="form-select" name="pre_pick">
                            <option value="" selected>-- Select Pre-Pick --</option>
                            <option value="SELF">SELF</option>
                            <option value="WVS 5404">WVS 5404 (1 Ton, Box Truck)</option>
                            <option value="NCR 8825">NCR 8825 (3 Tons, Curtain Truck)</option>
                            <option value="BSG 8826">BSG 8826 (5 Tons, Box Truck)</option>
                        </select>
                    </div>


                    {{-- Second row: Consignor & Pick Point --}}
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Consignor</label>
                        <input name="consignor" list="consignor_list" class="form-control" id="consignor"
                            placeholder="Search or Select Consignor">
                        <datalist id="consignor_list">
                            @foreach ($consignors as $consignor)
                                <option value="{{ $consignor }}"></option>
                            @endforeach
                        </datalist>

                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Consignee</label>
                        <input name="consignee" list="consignee_list" class="form-control" id="consignee"
                            placeholder="Search or Select Consignor">
                        <datalist id="consignee_list">
                            @foreach ($consignees as $consignee)
                                <option value="{{ $consignee }}"></option>
                            @endforeach
                        </datalist>

                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Pick Point </label>
                        <input id="pick_point" name="pick_point" list="pick_point_list" class="form-control"
                            placeholder="Select or Search Pick Point" required>
                        <datalist id="pick_point_list"></datalist>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold">Drop Point</label>
                        <input id="drop_point" name="drop_point" list="drop_point_list" class="form-control"
                            placeholder="Select or Search Drop Point" required>
                        <datalist id="drop_point_list"></datalist>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold">Pick Address</label>
                        <input id="pick_address" name="pick_address" list="pick_address_list" class="form-control"
                            placeholder="Select or Search Pick Address" required>
                        <datalist id="pick_address_list"></datalist>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold">Drop Address</label>
                        <input id="drop_address" name="drop_address" list="drop_address_list" class="form-control"
                            placeholder="Select or Search Drop Address" required>
                        <datalist id="drop_address_list"></datalist>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold">Pick Truck Size</label>
                        <select class="form-select" name="pick_truck_size" id="pick_truck_size">
                            <option value="">-- Select Size --</option>
                            <option value="Small">Small</option>
                            <option value="Any">Any</option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold">Drop Truck Size</label>
                        <select class="form-select" name="drop_truck_size" id="drop_truck_size">
                            <option value="">-- Select Size --</option>
                            <option value="Small">Small</option>
                            <option value="Any">Any</option>
                        </select>
                    </div>



                    {{-- Truck options --}}
                    {{-- <div class="col-md-6">
                        <label class="form-label fw-bold">Pick Truck</label>
                        <select class="form-control" name="pick_truck">
                            <option value="" selected>Select Truck Type</option>
                            <option value="ANY">ANY</option>
                            <option value="ANY (C)">ANY (C)</option>
                            <option value="ANY (O)">ANY (O)</option>
                            <option value="ANY (HZ)">ANY (HZ)</option>
                            <option value="CHECK">CHECK</option>
                            <option value="Self/ Warehouse">Self / Warehouse</option>
                            <option value="Small (C)">Small (C)</option>
                            <option value="Small (O)">Small (O)</option>
                            <option value="Small (HZ)">Small (HZ)</option>
                            <option value="Small (T)">Small (T)</option>
                            <option value="Warehouse Truck">Warehouse Truck</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Drop Truck</label>
                        <select class="form-control" name="drop_truck">
                            <option value="" selected>Select Truck Type</option>
                            <option value="ANY">ANY</option>
                            <option value="ANY (C)">ANY (C)</option>
                            <option value="ANY (O)">ANY (O)</option>
                            <option value="ANY (HZ)">ANY (HZ)</option>
                            <option value="CHECK">CHECK</option>
                            <option value="Self/ Warehouse">Self / Warehouse</option>
                            <option value="Small (C)">Small (C)</option>
                            <option value="Small (O)">Small (O)</option>
                            <option value="Small (HZ)">Small (HZ)</option>
                            <option value="Small (T)">Small (T)</option>
                            <option value="Warehouse Truck">Warehouse Truck</option>
                        </select>
                    </div> --}}

                    <div class="col-md-6">
                        <label class="form-label fw-bold">Pick Truck Type</label>
                        <select class="form-select" name="pick_truck_type" id="pick_truck_type">
                            <option value="">-- Select Type --</option>
                            <option value="all">ALL</option>
                            @foreach ($truckGroups as $group)
                                <option value="{{ $group }}">{{ $group }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold">Drop Truck Type</label>
                        <select class="form-select" name="drop_truck_type" id="drop_truck_type">
                            <option value="">-- Select Type --</option>
                            <option value="all">ALL</option>
                            @foreach ($truckGroups as $group)
                                <option value="{{ $group }}">{{ $group }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- <div class="col-md-6">
                        <label class="form-label fw-bold">Truck Number</label>
                        <select class="form-select" name="truck_number" id="truck_number">
                            <option value="">-- Select Truck --</option>
                            @foreach ($trucks as $truck)
                                <option value="{{ $truck->number }}">{{ $truck->number }}</option>
                            @endforeach
                        </select>
                    </div> --}}

                    <div class="col-md-6">
                        <label class="form-label fw-bold">Truck Number</label>
                        <input name="truck_number" list="truck_list" class="form-control" id="truck_number"
                            placeholder="Search or Select Truck">
                        <datalist id="truck_list">
                            @foreach ($trucks as $truck)
                                <option value="{{ $truck->number }}"></option>
                            @endforeach
                        </datalist>
                    </div>



                    <div class="col-md-6">
                        <label class="form-label fw-bold">Remarks</label>
                        <input type="text" class="form-control" name="remarks">
                    </div>


                    <div class="col-md-6">
                        <label class="form-label fw-bold">Billing Remarks</label>
                        <input type="text" class="form-control" name="billing_remark">
                    </div>

                    <div class="col-md-6 d-flex ">
                        <div class="form-label form-switch text-center mt-4">
                            <input class="form-check-input" type="checkbox" role="switch" name="express_mode"
                                id="expressModeSwitch" value="1">
                            <label class="form-check-label fw-bold ms-2" for="expressModeSwitch">
                                Express Mode
                            </label>
                        </div>
                    </div>

                    {{-- Quantity & Unit --}}
                    <div class="col-md-12">
                        <div class="quantityUnitContainer">
                            <!-- Header row -->
                            <div class="row g-2 fw-bold mb-1">
                                <div class="col-md-5">Quantity</div>
                                <div class="col-md-5">Unit</div>
                                <div class="col-md-2"></div>
                            </div>

                            <!-- First input row -->
                            <div class="row g-2 align-items-end mb-2 quantity-unit-row">
                                <div class="col-md-5">
                                    <input type="number" class="form-control" name="quantity[]" min="1">
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
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Consignment</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {

        $('.select2').select2({
            placeholder: "Search or select an option",
            allowClear: true
        });
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

        document.getElementById('load_date').addEventListener('change', function() {
            const loadDate = this.value;
            if (loadDate) {
                document.getElementById('pick_time').value = loadDate + 'T00:00';
            }
        });

        document.querySelectorAll('input[type="date"].date-clickable').forEach(input => {
            input.addEventListener('click', function() {
                this.showPicker();
            });
        });

        document.querySelectorAll('input[type="datetime-local"].datetime-clickable').forEach(input => {
            input.addEventListener('click', function() {
                this.showPicker();
            });
        });

        // Elements
        const consignorInput = document.querySelector('input[name="consignor"]');
        const consigneeInput = document.querySelector('input[name="consignee"]');

        const pickPointList = document.getElementById('pick_point_list');
        const dropPointList = document.getElementById('drop_point_list');
        const pickAddressList = document.getElementById('pick_address_list');
        const dropAddressList = document.getElementById('drop_address_list');

        // --- Helper function ---
        function populateDatalists(
            locations,
            typeFilter,
            pointList,
            addressList,
            pointInput,
            addressInput,
            truckTypeSelect,
            truckSizeSelect
        ) {
            pointList.innerHTML = '';
            addressList.innerHTML = '';

            const filteredLocations = locations.filter(
                loc => loc.type && loc.type.toLowerCase() === typeFilter
            );

            filteredLocations.forEach(loc => {
                const optionPoint = document.createElement('option');
                optionPoint.value = loc.pickup_dropoff_point ?? loc.state;
                optionPoint.dataset.id = loc.id;
                pointList.appendChild(optionPoint);

                const optionAddress = document.createElement('option');
                optionAddress.value = loc.address;
                optionAddress.dataset.id = loc.id;
                addressList.appendChild(optionAddress);
            });

            if (filteredLocations.length > 0) {
                const loc = filteredLocations[0];

                pointInput.value = loc.pickup_dropoff_point ?? loc.state ?? '';
                addressInput.value = loc.address ?? '';

                if (truckTypeSelect && loc.truck_type) {
                    truckTypeSelect.value = loc.truck_type;
                    truckTypeSelect.dispatchEvent(new Event('change'));
                }

                if (truckSizeSelect && loc.truck_size) {
                    truckSizeSelect.value = loc.truck_size;
                }
            } else {
                pointInput.value = '';
                addressInput.value = '';
            }
        }

        // --- Fetch customer locations ---
        function fetchCustomerLocations(name, type) {
            if (!name) return;

            fetch(`/customers/${encodeURIComponent(name)}/locations`)
                .then(res => res.json())
                .then(data => {
                    if (!data.locations) return;

                    if (type === 'consignor') {
                        const pickPointInput = document.getElementById('pick_point');
                        const pickAddressInput = document.getElementById('pick_address');

                        populateDatalists(
                            data.locations,
                            'pickup',
                            pickPointList,
                            pickAddressList,
                            document.getElementById('pick_point'),
                            document.getElementById('pick_address'),
                            document.getElementById('pick_truck_type'),
                            document.getElementById('pick_truck_size')
                        );

                        // Auto-select pick_truck_type based on default_truck_type from first location
                        const firstPickup = data.locations.find(loc => loc.type.toLowerCase() === 'pickup');
                        const pickTruckTypeSelect = document.getElementById('pick_truck_type');
                        if (firstPickup && firstPickup.default_truck_type && pickTruckTypeSelect) {
                            pickTruckTypeSelect.value = firstPickup.default_truck_type;
                            pickTruckTypeSelect.dispatchEvent(new Event('change'));
                        }

                    } else if (type === 'consignee') {
                        const dropPointInput = document.getElementById('drop_point');
                        const dropAddressInput = document.getElementById('drop_address');

                        populateDatalists(
                            data.locations,
                            'dropoff',
                            dropPointList,
                            dropAddressList,
                            document.getElementById('drop_point'),
                            document.getElementById('drop_address'),
                            document.getElementById('drop_truck_type'),
                            document.getElementById('drop_truck_size')
                        );

                        // Auto-select drop_truck_type based on default_truck_type from first location
                        const firstDrop = data.locations.find(loc => loc.type.toLowerCase() === 'dropoff');
                        const dropTruckTypeSelect = document.getElementById('drop_truck_type');
                        if (firstDrop && firstDrop.default_truck_type && dropTruckTypeSelect) {
                            dropTruckTypeSelect.value = firstDrop.default_truck_type;
                            dropTruckTypeSelect.dispatchEvent(new Event('change'));
                        }
                    }
                })
                .catch(err => console.error('Error fetching locations:', err));
        }
        consignorInput.addEventListener('change', () => {
            fetchCustomerLocations(consignorInput.value, 'consignor');
        });

        consigneeInput.addEventListener('change', () => {
            fetchCustomerLocations(consigneeInput.value, 'consignee');
        });


    });

    const allTrucks = @json($trucks);
    const allSubcons = @json($subcons);
    let availableTrucks = allTrucks;
    let availableSubcons = allSubcons;

    function normalize(val) {
        return (val || '').toString().trim().toLowerCase();
    }

    function fetchAvailableTrucks(date) {
        if (!date) {
            availableTrucks = allTrucks;
            availableSubcons = allSubcons;
            updateTruckNumbers();
            return;
        }
        fetch(`/api/available-trucks?date=${date}`)
            .then(r => r.json())
            .then(data => {
                availableTrucks = data.trucks;
                availableSubcons = data.subcons;
                updateTruckNumbers();
            })
            .catch(() => {
                availableTrucks = allTrucks;
                availableSubcons = allSubcons;
                updateTruckNumbers();
            });
    }

    document.getElementById('load_date').addEventListener('change', function() {
        fetchAvailableTrucks(this.value);
    });

    function updateTruckNumbers() {
        const pickType = normalize(document.getElementById('pick_truck_type').value);
        const pickSize = normalize(document.getElementById('pick_truck_size').value);
        const dropType = normalize(document.getElementById('drop_truck_type').value);
        const dropSize = normalize(document.getElementById('drop_truck_size').value);

        const truckInput = document.getElementById('truck_number');
        const truckDatalist = document.getElementById('truck_list');
        truckDatalist.innerHTML = '';

        function isValid(truck) {
            if (!truck.chassis_type) return false;

            const tType = normalize(truck.chassis_type);
            const tSize = normalize(truck.size);

            let matchPick = true;
            let matchDrop = true;

            // "all" or empty = no type filter; "any" or empty = no size filter
            if (pickType && pickType !== 'all') {
                matchPick = tType === pickType;
            }
            if (matchPick && pickSize && pickSize !== 'any' && tSize) {
                matchPick = tSize === pickSize;
            }

            if (dropType && dropType !== 'all') {
                matchDrop = tType === dropType;
            }
            if (matchDrop && dropSize && dropSize !== 'any' && tSize) {
                matchDrop = tSize === dropSize;
            }

            return matchPick && matchDrop;
        }

        availableTrucks.filter(isValid).forEach(t => {
            const opt = document.createElement('option');
            opt.value = t.number;
            truckDatalist.appendChild(opt);
        });

        availableSubcons.filter(isValid).forEach(s => {
            const opt = document.createElement('option');
            opt.value = s.truck_no + ' (Subcon)';
            truckDatalist.appendChild(opt);
        });

        // Force browser to refresh datalist by toggling the list attribute
        truckInput.removeAttribute('list');
        setTimeout(() => truckInput.setAttribute('list', 'truck_list'), 0);
    }

    ['pick_truck_type', 'pick_truck_size', 'drop_truck_type', 'drop_truck_size']
    .forEach(id => {
        document.getElementById(id).addEventListener('change', updateTruckNumbers);
    });



    document.getElementById('pick_point').addEventListener('change', function() {
        const val = this.value;
        const option = Array.from(document.getElementById('pick_point_list').options)
            .find(o => o.value === val);

        if (option && option.dataset.id) {
            const locationId = option.dataset.id;
            fetch(`/locations/${locationId}`)
                .then(res => res.json())
                .then(data => {
                    const pickTruckSelect = document.querySelector('select[name="pick_truck"]');
                    if (data.default_truck_type) {
                        pickTruckSelect.value = data.default_truck_type;
                    } else {
                        pickTruckSelect.value = "";
                    }
                })
                .catch(err => console.error('Error fetching pick truck type:', err));
        }
    });

    document.getElementById('drop_point').addEventListener('change', function() {
        const val = this.value;
        const option = Array.from(document.getElementById('drop_point_list').options)
            .find(o => o.value === val);

        if (option && option.dataset.id) {
            const locationId = option.dataset.id;
            fetch(`/locations/${locationId}`)
                .then(res => res.json())
                .then(data => {
                    const dropTruckSelect = document.querySelector('select[name="drop_truck"]');
                    if (data.default_truck_type) {
                        dropTruckSelect.value = data.default_truck_type;
                    } else {
                        dropTruckSelect.value = "";
                    }
                })
                .catch(err => console.error('Error fetching drop truck type:', err));
        }
    });
</script>
