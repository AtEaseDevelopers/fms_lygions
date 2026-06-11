@extends('component.layout')
@section('title', 'Edit Customer')
@section('content')
    <form action="{{ route('customer.update', $customer->id) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="container mt-4">
            <div class="card">
                <div class="card-header">
                    <h4>Edit Customer</h4>
                </div>
                <div class="card-body">
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    <div class="row">
                        <!-- Left Column -->
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="name">Name</label>
                                <input type="text" name="name" id="name" class="form-control"
                                    value="{{ old('name', $customer->name) }}" #>
                            </div>

                            <div class="form-group mb-3">
                                <label for="nickname">Nickname</label>
                                <input type="text" name="nickname" id="nickname" class="form-control"
                                    value="{{ old('nickname', $customer->nickname) }}">
                            </div>

                            <div class="form-group mb-3">
                                <label for="account_number">Account Number</label>
                                <input type="text" name="account_number" id="account_number" class="form-control"
                                    value="{{ old('account_number', $customer->account_number) }}">
                            </div>

                            <div class="form-group mb-3">
                                <label for="type">Type</label>
                                <select name="type" id="type" class="form-control">
                                    <option value="Consignor" {{ $customer->type == 'Consignor' ? 'selected' : '' }}>
                                        Consignor</option>
                                    <option value="Consignee" {{ $customer->type == 'Consignee' ? 'selected' : '' }}>
                                        Consignee</option>
                                </select>
                            </div>

                            <div class="form-group mb-3">
                                <label for="phone">Phone</label>
                                <input type="text" name="phone" id="phone" class="form-control"
                                    value="{{ old('phone', $customer->phone) }}">
                            </div>

                            <div class="form-group mb-3">
                                <label for="billing_phone">Billing Phone</label>
                                <input type="text" name="billing_phone" id="billing_phone" class="form-control"
                                    value="{{ old('billing_phone', $customer->billing_phone) }}">
                            </div>

                            <div class="form-group mb-3">
                                <label for="billing_address">Billing Address</label>
                                <textarea name="billing_address" id="billing_address" class="form-control" rows="3">{{ old('billing_address', $customer->billing_address) }}</textarea>
                            </div>

                            <div class="form-group mb-3">
                                <label for="email">Email</label>
                                @php
                                    $email = old('email', $customer->email);

                                    if (str_starts_with($email, '["') && str_ends_with($email, '"]')) {
                                        $email = substr($email, 2, -2);
                                    }
                                @endphp
                                <input type="text" name="email" id="email" class="form-control"
                                    value="{{ $email }}">
                            </div>


                            <div class="form-group mb-3">
                                <label for="website">Website</label>
                                <input type="text" name="website" id="website" class="form-control"
                                    value="{{ old('website', $customer->website) }}">
                            </div>

                            <div class="form-group mb-3">
                                <label for="company_reg_no_new">Company Reg No (New)</label>
                                <input type="text" name="company_reg_no_new" id="company_reg_no_new" class="form-control"
                                    value="{{ old('company_reg_no_new', $customer->company_reg_no_new) }}">
                            </div>

                            <div class="form-group mb-3">
                                <label for="company_reg_no_old">Company Reg No (Old)</label>
                                <input type="text" name="company_reg_no_old" id="company_reg_no_old" class="form-control"
                                    value="{{ old('company_reg_no_old', $customer->company_reg_no_old) }}">
                            </div>
                        </div>

                        <!-- Right Column -->
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="remark">Remark</label>
                                <textarea name="remark" id="remark" class="form-control" rows="3">{{ old('remark', $customer->remark) }}</textarea>
                            </div>

                            <div class="form-group mb-3">
                                <label for="consignor_currency">Consignor Currency</label>
                                <input type="text" name="consignor_currency" id="consignor_currency" class="form-control"
                                    value="{{ old('consignor_currency', $customer->consignor_currency) }}">
                            </div>

                            <div class="form-group mb-3">
                                <label for="consignee_currency">Consignee Currency</label>
                                <input type="text" name="consignee_currency" id="consignee_currency"
                                    class="form-control"
                                    value="{{ old('consignee_currency', $customer->consignee_currency) }}">
                            </div>

                            <div class="form-group mb-3">
                                <label for="city">City</label>
                                <input type="text" name="city" id="city" class="form-control"
                                    value="{{ old('city', $customer->city) }}">
                            </div>

                            <div class="form-group mb-3">
                                <label for="post_code">Post Code</label>
                                <input type="text" name="post_code" id="post_code" class="form-control"
                                    value="{{ old('post_code', $customer->post_code) }}">
                            </div>

                            <div class="form-group mb-3">
                                <label for="state">State</label>
                                <input type="text" name="state" id="state" class="form-control"
                                    value="{{ old('state', $customer->state) }}">
                            </div>

                            <div class="form-group mb-3">
                                <label for="country">Country</label>
                                <input type="text" name="country" id="country" class="form-control"
                                    value="{{ old('country', $customer->country) }}">
                            </div>

                            <div class="form-group mb-3">
                                <label for="tin">TIN</label>
                                <input type="text" name="tin" id="tin" class="form-control"
                                    value="{{ old('tin', $customer->tin) }}">
                            </div>

                            <div class="form-group mb-3">
                                <label for="service_tax_no">Service Tax No</label>
                                <input type="text" name="service_tax_no" id="service_tax_no" class="form-control"
                                    value="{{ old('service_tax_no', $customer->service_tax_no) }}">
                            </div>

                            <div class="form-group mb-3">
                                <label for="contact_person">Contact Person</label>
                                <input type="text" name="contact_person" id="contact_person" class="form-control"
                                    value="{{ old('contact_person', $customer->contact_person) }}">
                            </div>

                            <div class="form-group mb-3">
                                <label for="term">Term</label>
                                <select name="term" id="term" class="form-control">
                                    <option value="" hidden>Select a term</option>
                                    <option value="1" {{ old('term', $customer->term) == 1 ? 'selected' : '' }}>Cash</option>
                                    <option value="2" {{ old('term', $customer->term) == 2 ? 'selected' : '' }}>30 Days</option>
                                    <option value="3" {{ old('term', $customer->term) == 3 ? 'selected' : '' }}>45 Days</option>
                                    <option value="4" {{ old('term', $customer->term) == 4 ? 'selected' : '' }}>60 Days</option>
                                </select>
                            </div>
                        </div>
                    </div>


                </div>
            </div>

            <!-- Customer Locations Table -->
            <div class="card mt-4">
                <div class="card-header">
                    <h5>Customer Locations</h5>
                    <button type="button" class="btn btn-success btn-sm mt-2" id="addRow" style="float: right">Add
                        Row</button>

                </div>
                <div class="card-body">
                    <table class="table table-hover align-middle" id="locationsTable">
                        <thead>
                            <tr>
                                <th>No.</th>
                                <th>State</th>
                                <th>Address</th>
                                <th>Pick/Drop Point</th>
                                <th>PIC</th>
                                <th>Phone</th>
                                <th>Mode Type</th>
                                <th>Truck Size</th>
                                <th>Truck Type</th>
                                <th>Load Type</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($customer->locations as $location)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>


                                        <select name="locations[{{ $loop->index }}][state]" id="state"
                                            class="form-control">
                                            <option value="" disabled>-- Select State --</option>
                                            <option value="Johor" {{ $location->state == 'Johor' ? 'selected' : '' }}>
                                                Johor</option>
                                            <option value="Kedah" {{ $location->state == 'Kedah' ? 'selected' : '' }}>
                                                Kedah</option>
                                            <option value="Kelantan"
                                                {{ $location->state == 'Kelantan' ? 'selected' : '' }}>Kelantan</option>
                                            <option value="Malacca" {{ $location->state == 'Malacca' ? 'selected' : '' }}>
                                                Malacca</option>
                                            <option value="Negeri Sembilan"
                                                {{ $location->state == 'Negeri Sembilan' ? 'selected' : '' }}>Negeri
                                                Sembilan</option>
                                            <option value="Pahang" {{ $location->state == 'Pahang' ? 'selected' : '' }}>
                                                Pahang</option>
                                            <option value="Penang" {{ $location->state == 'Penang' ? 'selected' : '' }}>
                                                Penang</option>
                                            <option value="Perak" {{ $location->state == 'Perak' ? 'selected' : '' }}>
                                                Perak</option>
                                            <option value="Perlis" {{ $location->state == 'Perlis' ? 'selected' : '' }}>
                                                Perlis</option>
                                            <option value="Sabah" {{ $location->state == 'Sabah' ? 'selected' : '' }}>
                                                Sabah</option>
                                            <option value="Sarawak" {{ $location->state == 'Sarawak' ? 'selected' : '' }}>
                                                Sarawak</option>
                                            <option value="Selangor"
                                                {{ $location->state == 'Selangor' ? 'selected' : '' }}>Selangor</option>
                                            <option value="Terengganu"
                                                {{ $location->state == 'Terengganu' ? 'selected' : '' }}>Terengganu
                                            </option>
                                            <option value="Kuala Lumpur"
                                                {{ $location->state == 'Kuala Lumpur' ? 'selected' : '' }}>Kuala Lumpur
                                            </option>
                                            <option value="Labuan" {{ $location->state == 'Labuan' ? 'selected' : '' }}>
                                                Labuan</option>
                                            <option value="Putrajaya"
                                                {{ $location->state == 'Putrajaya' ? 'selected' : '' }}>Putrajaya</option>
                                            <option value="Singapore"
                                                {{ $location->state == 'Singapore' ? 'selected' : '' }}>Singapore</option>
                                        </select>
                                    </td>
                                    <td>
                                        <input type="text" name="locations[{{ $loop->index }}][address]"
                                            class="form-control" value="{{ $location->address }}">
                                    </td>
                                    <td>
                                        <input type="text" name="locations[{{ $loop->index }}][pickup_dropoff_point]"
                                            class="form-control" value="{{ $location->pickup_dropoff_point }}">
                                    </td>
                                    <td>
                                        <input type="text" name="locations[{{ $loop->index }}][pic]"
                                            class="form-control" value="{{ $location->pic }}">
                                    </td>
                                    <td>
                                        <input type="text" name="locations[{{ $loop->index }}][phone]"
                                            class="form-control" value="{{ $location->phone }}">
                                    </td>
                                    <td>
                                        <select name="locations[{{ $loop->index }}][type]" id="type"
                                            class="form-control">
                                            <option value="Dropoff" {{ $location->type == 'Dropoff' ? 'selected' : '' }}>
                                                Drop Off</option>
                                            <option value="Pickup" {{ $location->type == 'Pickup' ? 'selected' : '' }}>
                                                Pick Up</option>
                                            <option value="Self Delivery"
                                                {{ $location->type == 'Self Delivery' ? 'selected' : '' }}>
                                                Self Delivery</option>
                                        </select>
                                    </td>
                                    <td>
                                        <select name="locations[{{ $loop->index }}][truck_size]" id="truck_size"
                                            class="form-control">

                                            <option value="" {{ empty($location->truck_size) ? 'selected' : '' }}>
                                                -- Select Truck Size --
                                            </option>
                                            <option value="Any"
                                                {{ ($location->truck_size ?? '') == 'Any' ? 'selected' : '' }}>Any
                                            </option>
                                            <option value="Small"
                                                {{ ($location->truck_size ?? '') == 'Small' ? 'selected' : '' }}>
                                                Small</option>
                                            <option value="Warehouse Truck"
                                                {{ ($location->truck_size ?? '') == 'Warehouse Truck' ? 'selected' : '' }}>
                                                Warehouse Truck</option>
                                        </select>
                                    </td>

                                    <td>
                                        <select name="locations[{{ $loop->index }}][truck_type]" id="truck_type"
                                            class="form-control">

                                            <option value="" {{ empty($location->truck_type) ? 'selected' : '' }}>
                                                -- Select Truck Type --
                                            </option>

                                            <option value="any"
                                                {{ ($location->truck_type ?? '') == 'any' ? 'selected' : '' }}>Any
                                            </option>

                                            <option value="curtain"
                                                {{ ($location->truck_type ?? '') == 'curtain' ? 'selected' : '' }}>Curtain
                                            </option>
                                            <option value="open"
                                                {{ ($location->truck_type ?? '') == 'open' ? 'selected' : '' }}>
                                                Open</option>
                                            <option value="tailgate"
                                                {{ ($location->truck_type ?? '') == 'tailgate' ? 'selected' : '' }}>
                                                Tailgate</option>
                                        </select>
                                    </td>

                                    <td>
                                        @php
                                            $loadTypes = [];

                                            if (!empty($location->load_type)) {
                                                $loadTypes = explode(',', $location->load_type);
                                            }
                                        @endphp


                                        <div style="display: flex; flex-direction: column; gap: 0.25rem;">
                                            <label
                                                style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                                                <input type="checkbox" name="locations[{{ $loop->index }}][load_type][]"
                                                    value="forklift"
                                                    {{ in_array('forklift', $loadTypes) ? 'checked' : '' }}
                                                    style="width: 16px; height: 16px; cursor: pointer;">
                                                <span>Forklift</span>
                                            </label>
                                            <label
                                                style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                                                <input type="checkbox" name="locations[{{ $loop->index }}][load_type][]"
                                                    value="manual_labour"
                                                    {{ in_array('manual_labour', $loadTypes) ? 'checked' : '' }}
                                                    style="width: 16px; height: 16px; cursor: pointer;">
                                                <span>Manual Labour</span>
                                            </label>
                                            <label
                                                style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                                                <input type="checkbox" name="locations[{{ $loop->index }}][load_type][]"
                                                    value="pallet_jack"
                                                    {{ in_array('pallet_jack', $loadTypes) ? 'checked' : '' }}
                                                    style="width: 16px; height: 16px; cursor: pointer;">
                                                <span>Hand Pallet Jack</span>
                                            </label>
                                        </div>
                                    </td>

                                    <td>
                                        <button type="button" class="btn btn-danger btn-sm remove-row">Delete</button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="d-flex justify-content-end mt-3">
                    <a href="{{ route('customer.index') }}" class="btn btn-secondary me-2">Cancel</a>
                    <button type="submit" class="btn btn-primary">Update Customer</button>
                </div>
            </div>
        </div>
    </form>


    <!-- JS for Add/Delete Rows -->
    <script>
        document.getElementById('addRow').addEventListener('click', function() {
            let tableBody = document.querySelector('#locationsTable tbody');
            let rowCount = tableBody.rows.length;
            let newRow = `
        <tr>
             <td>${rowCount + 1}</td>
            <td> <select class="form-control" name="locations[${rowCount}][state]">
                <option value=""selected disabled>Select State</option>
                <option value="Selangor">Selangor</option>
                <option value="Johor">Johor</option>
                <option value="Kedah">Kedah</option>
                <option value="Kelantan">Kelantan</option>
                <option value="Malacca">Malacca</option>
                <option value="Negeri Sembilan">Negeri Sembilan</option>
                <option value="Pahang">Pahang</option>
                <option value="Penang">Penang</option>
                <option value="Perak">Perak</option>
                <option value="Perlis">Perlis</option>
                <option value="Sabah">Sabah</option>
                <option value="Sarawak">Sarawak</option>
                <option value="Selangor">Selangor</option>
                <option value="Terengganu">Terengganu</option>
                <option value="Kuala Lumpur">Kuala Lumpur</option>
                <option value="Labuan">Labuan</option>
                <option value="Putrajaya">Putrajaya</option>
                <option value="Singapore">Singapore</option>
            </select></td>
            <td><input type="text" name="locations[${rowCount}][address]" class="form-control"></td>
            <td><input type="text" name="locations[${rowCount}][pickup_dropoff_point]" class="form-control"></td>
            <td><input type="text" name="locations[${rowCount}][pic]" class="form-control"></td>
            <td><input type="text" name="locations[${rowCount}][phone]" class="form-control"></td>
             <td>
                 <select class="form-control" name="locations[${rowCount}][type]">
                <option value="" disabled selected>Select Type</option>
                <option value="Dropoff">Drop Off</option>
                <option value="Pickup">Pick Up</option>
                <option value="Self Delivery">Self Delivery</option>
            </select></td>
<td>

            <select class="form-control" name="locations[${rowCount}][truck_size]">
                <option value="" disabled selected>Select Truck Size</option>
                <option value="Any">Any</option>
                <option value="Small">Small</option>
                <option value="Warehouse Truck">Warehouse Truck</option>
            </select>
        </td>

        <td>
            <select class="form-control" name="locations[${rowCount}][default_truck_type]">
                <option value="" disabled selected>Select Truck Type</option>
                <option value="any">any</option>
                <option value="curtain">curtain</option>
                <option value="open">open</option>
                <option value="tailgate">tailgate</option>
            </select>
        </td>

         <td>
        <div style="display: flex; flex-direction: column; gap: 0.25rem;">
            <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                <input type="checkbox" name="locations[${rowCount}][load_type][]"
                       value="forklift"
                       style="width: 16px; height: 16px; cursor: pointer;">
                <span>Forklift</span>
            </label>

            <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                <input type="checkbox" name="locations[${rowCount}][load_type][]"
                       value="manual_labour"
                       style="width: 16px; height: 16px; cursor: pointer;">
                <span>Manual Labour</span>
            </label>

            <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                <input type="checkbox" name="locations[${rowCount}][load_type][]"
                       value="pallet_jack"
                       style="width: 16px; height: 16px; cursor: pointer;">
                <span>Hand Pallet Jack</span>
            </label>
        </div>
    </td>

            <td><button type="button" class="btn btn-danger btn-sm remove-row">Delete</button></td>
        </tr>
    `;
            tableBody.insertAdjacentHTML('beforeend', newRow);
        });

        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('remove-row')) {
                e.target.closest('tr').remove();
            }
        });
    </script>

@endsection
