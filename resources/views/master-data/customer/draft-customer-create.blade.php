@extends('component.layout')
@section('title', 'Create Draft Customer')
@section('content')

    <form action="{{ route('customer.store') }}" method="POST" novalidate>
        @csrf
        @if ($errors->any())
            <div class="alert alert-danger">
                <strong>Error</strong>
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        <div class="accordion" id="customerAccordion">

            <!-- Basic Details Section -->
            <div class="accordion-item">
                <h2 class="accordion-header" id="headingBasic">
                    <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseBasic"
                        aria-expanded="true" aria-controls="collapseBasic" style="background-color:white">
                        Basic Details
                    </button>
                </h2>
                <div id="collapseBasic" class="accordion-collapse collapse show" aria-labelledby="headingBasic"
                    data-bs-parent="#customerAccordion">
                    <div class="accordion-body">
                        <div class="card card-user">
                            <div class="card-header">
                                <h5 class="card-title">Enter Details</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <!-- Left Column -->
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Name</label>
                                            <input type="text" name="name" class="form-control"
                                                value="{{ old('name') }}" placeholder="Name" required>
                                            @error('name')
                                                <small class="text-danger">{{ $message }}</small>
                                            @enderror
                                        </div>

                                        <div class="form-group">
                                            <label>Nickname</label>
                                            <input type="text" name="nickname" class="form-control"
                                                value="{{ old('nickname') }}" placeholder="Nickname">
                                            @error('nickname')
                                                <small class="text-danger">{{ $message }}</small>
                                            @enderror
                                        </div>
                                        <div class="form-group">
                                            <label>Email</label>
                                            <input type="email" name="email" class="form-control"
                                                value="{{ old('email') }}" placeholder="Email">
                                            @error('email')
                                                <small class="text-danger">{{ $message }}</small>
                                            @enderror
                                        </div>
                                        <div class="form-group">
                                            <label>Account Number</label>
                                            <input type="text" name="account_number" class="form-control"
                                                value="{{ old('account_number') }}" placeholder="Account Number" required>
                                            @error('account_number')
                                                <small class="text-danger">{{ $message }}</small>
                                            @enderror
                                        </div>
                                        <div class="form-group d-flex gap-2">
                                            <div style="flex: 1;">
                                                <label>Company Registration Number</label>
                                                <input type="text" name="company_reg_no_new" class="form-control"
                                                    value="{{ old('company_reg_no_new') }}" placeholder="New">
                                            </div>
                                            <div style="flex: 1;">
                                                <label>&nbsp;</label>
                                                <input type="text" name="company_reg_no_old" class="form-control"
                                                    value="{{ old('company_reg_no_old') }}" placeholder="Old">
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label>Website</label>
                                            <input type="text" name="website" class="form-control"
                                                value="{{ old('website') }}" placeholder="Website">
                                        </div>
                                        <div class="form-group">
                                            <label>Billing Phone</label>
                                            <input type="text" name="billing_phone" class="form-control"
                                                value="{{ old('billing_phone') }}" placeholder="Billing Phone">
                                        </div>
                                        <div class="form-group">
                                            <label>Remark</label>
                                            <textarea name="remark" class="form-control" rows="2" placeholder="Remark">{{ old('remark') }}</textarea>
                                        </div>

                                        <div class="form-group mb-4">
                                            <label class="mb-2">Select Role:</label><br>

                                            <!-- Hidden input to store selected types -->
                                            <input type="hidden" name="type" id="type_input"
                                                value="{{ old('type', '') }}">

                                            <!-- Consignor -->
                                            <div class="d-flex align-items-center mb-2 px-3">
                                                <input class="form-check-input me-2 type-checkbox" type="checkbox"
                                                    id="consignor-check" value="Consignor"
                                                    {{ old('type') && str_contains(old('type'), 'Consignor') ? 'checked' : '' }}>
                                                <label class="form-check-label me-3 mb-0"
                                                    for="consignor-check">Consignor</label>

                                                <input type="hidden" name="consignor_currency" id="consignor_currency"
                                                    value="{{ old('consignor_currency', 'MYR') }}">
                                                <div class="btn-group ms-3" role="group">
                                                    <button type="button" class="btn btn-warning active"
                                                        onclick="document.getElementById('consignor_currency').value='MYR'">MYR</button>
                                                    <button type="button" class="btn btn-outline-secondary"
                                                        onclick="document.getElementById('consignor_currency').value='SGD'">SGD</button>
                                                </div>
                                            </div>

                                            <!-- Consignee -->
                                            <div class="d-flex align-items-center mb-2 px-3">
                                                <input class="form-check-input me-2 type-checkbox" type="checkbox"
                                                    id="consignee-check" value="Consignee"
                                                    {{ old('type') && str_contains(old('type'), 'Consignee') ? 'checked' : '' }}>
                                                <label class="form-check-label me-3 mb-0"
                                                    for="consignee-check">Consignee</label>

                                                <input type="hidden" name="consignee_currency" id="consignee_currency"
                                                    value="{{ old('consignee_currency', 'SGD') }}">
                                                <div class="btn-group ms-3" role="group">
                                                    <button type="button" class="btn btn-outline-secondary"
                                                        onclick="document.getElementById('consignee_currency').value='MYR'">MYR</button>
                                                    <button type="button" class="btn btn-warning active"
                                                        onclick="document.getElementById('consignee_currency').value='SGD'">SGD</button>
                                                </div>
                                            </div>

                                        </div>

                                    </div>

                                    <!-- Right Column -->
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Billing Address</label>
                                            <textarea name="billing_address" class="form-control" rows="3" placeholder="Billing Address">{{ old('billing_address') }}</textarea>
                                        </div>
                                        <div class="form-group">
                                            <label>City Name</label>
                                            <input type="text" name="city" class="form-control"
                                                value="{{ old('city') }}" placeholder="City Name">
                                        </div>
                                        <div class="form-group">
                                            <label>Post Code</label>
                                            <input type="text" name="post_code" class="form-control"
                                                value="{{ old('post_code') }}" placeholder="Post Code">
                                        </div>
                                        <div class="form-group">
                                            <label>State</label>
                                            <input type="text" name="state" class="form-control"
                                                value="{{ old('state') }}" placeholder="State">
                                        </div>
                                        <div class="form-group">
                                            <label>Country</label>
                                            <input type="text" name="country" class="form-control"
                                                value="{{ old('country') }}" placeholder="Country">
                                        </div>
                                        <div class="form-group">
                                            <label>Income Tax no. (TIN)</label>
                                            <input type="text" name="tin" class="form-control"
                                                value="{{ old('tin') }}" placeholder="TIN">
                                        </div>
                                        <div class="form-group">
                                            <label>Service Tax No</label>
                                            <input type="text" name="service_tax_no" class="form-control"
                                                value="{{ old('service_tax_no') }}" placeholder="Service Tax No">
                                        </div>
                                        <div class="form-group">
                                            <label>Contact Person</label>
                                            <input type="text" name="contact_person" class="form-control"
                                                value="{{ old('contact_person') }}" placeholder="Contact Person">
                                        </div>
                                        <div class="form-group">
                                            <label>Term</label>
                                            <input type="text" name="term" class="form-control"
                                                value="{{ old('term') }}" placeholder="Term">
                                        </div>
                                    </div>
                                </div>


                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Location Section -->
            <div class="accordion-item">
                <h2 class="accordion-header" id="headingLocation">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                        data-bs-target="#collapseLocation" aria-expanded="false" aria-controls="collapseLocation"
                        style="background-color:white">
                        Location
                    </button>
                </h2>
                <div id="collapseLocation" class="accordion-collapse collapse" aria-labelledby="headingLocation"
                    data-bs-parent="#customerAccordion">
                    <div class="accordion-body">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="mb-0">Location Details</h6>
                            <button type="button" class="btn btn-sm btn-success" onclick="addRow()">Add Row</button>
                        </div>

                        <!-- replaced separate <form> with a container so inputs are inside the main form -->
                        <div id="locationForm">
                            <table class="table table-hover align-middle" id="locationTable">
                                <thead class="text-primary">
                                    <tr>
                                        <th style="width: 50px;">No.</th>
                                        <th>State</th>
                                        <th>Address</th>
                                        <th>PIC</th>
                                        <th>Phone</th>
                                        <th>Mode Type</th>
                                        <th>Truck Size</th>
                                        <th>Truck Type</th>
                                        <th>Load Type</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody id="locationTbody">
                                    <!-- JS will insert rows -->
                                </tbody>
                            </table>
                        </div>

                        <div class="d-flex justify-content-end mt-3">
                            <button type="submit" class="btn btn-outline-primary">Save</button>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </form>

    <script>
        // Ensure only one of the role checkboxes acts as the selected type.
        document.addEventListener('DOMContentLoaded', function() {
            const typeInput = document.getElementById('type_input');
            const checkboxes = document.querySelectorAll('.type-checkbox');

            function updateTypeInput() {
                const selected = Array.from(checkboxes)
                    .filter(cb => cb.checked)
                    .map(cb => cb.value);
                typeInput.value = selected.join(','); // send as comma-separated
            }

            checkboxes.forEach(cb => {
                cb.addEventListener('change', updateTypeInput);
            });

            // initialize
            updateTypeInput();
        });

        let rowCount = 0;

        function addRow() {
            rowCount++;
            const tbody = document.getElementById('locationTbody');

            const row = document.createElement('tr');
            row.innerHTML = `
            <td>${rowCount}</td>
             <td>
            <select class="form-control" name="locations[${rowCount}][state]">
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
            </select>
        </td>
        <td><input type="text" class="form-control" name="locations[${rowCount}][address]"></td>
        <td><input type="text" class="form-control" name="locations[${rowCount}][pic]"></td>
        <td><input type="text" class="form-control" name="locations[${rowCount}][phone]"></td>
        <td>
            <select class="form-control" name="locations[${rowCount}][type]">
                <option value="" disabled selected>Select Type</option>
                <option value="Dropoff">Drop Off</option>
                <option value="Pickup">Pick Up</option>
            </select>
        </td>
        <td>
            <select class="form-control" name="locations[${rowCount}][truck_size]">
                <option value="" disabled selected>Select Truck Size</option>
                <option value="Any">Any</option>
                <option value="Small">Small</option>
            </select>
        </td>
        <td>
            <select class="form-control" name="locations[${rowCount}][truck_type]">
                <option value="" disabled selected>Select Truck Type</option>
                <option value="curtain">Curtain</option>
                <option value="open">Open</option>
                <option value="tailgate">Tailgate</option>
            </select>
        </td>

      <td>
    <div style="display: flex; flex-direction: column; gap: 0.25rem;">
        <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
            <input type="checkbox" name="locations[${rowCount}][load_type][]"
                value="forklift" style="width: 16px; height: 16px; cursor: pointer;">
            <span>Forklift</span>
        </label>
        <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
            <input type="checkbox" name="locations[${rowCount}][load_type][]"
                value="manual_labour" style="width: 16px; height: 16px; cursor: pointer;">
            <span>Manual Labour</span>
        </label>
        <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
            <input type="checkbox" name="locations[${rowCount}][load_type][]"
                value="pallet_jack" style="width: 16px; height: 16px; cursor: pointer;">
            <span>Hand Pallet Jack</span>
        </label>
    </div>
</td>

        <td>
            <button type="button" class="btn btn-sm btn-danger" onclick="deleteRow(this)">
                <i class="bi bi-trash"></i>
            </button>
        </td>
        `;

            tbody.appendChild(row);
        }

        function deleteRow(btn) {
            const row = btn.closest('tr');
            row.remove();

            rowCount = 0;
            document.querySelectorAll('#locationTbody tr').forEach((tr, index) => {
                tr.cells[0].textContent = ++rowCount;
            });
        }
    </script>
@endsection
