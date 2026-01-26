@extends('component.layout')
@section('title', 'Create Driver')
@section('content')
    <div class="d-flex justify-content-center">
        <div class="card p-4" style="min-width: 400px; max-width: 600px; width: 100%;">
            <h4 class="mb-4">Create Driver</h4>
              @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif


            <form action="{{ route('driver.store') }}" method="POST">
                @csrf

                <div class="mb-3">
                    <label for="name" class="form-label">Name</label>
                    <input type="text" class="form-control" id="name" name="name" placeholder="Enter name" required>
                </div>

                <div class="mb-3">
                    <label for="group" class="form-label">Group</label>
                    <input type="text" class="form-control" id="group" name="group" placeholder="Enter group">
                </div>

                <div class="mb-3">
                    <label for="id_number" class="form-label">ID Number</label>
                    <input type="text" class="form-control" id="id_number" name="id_number" placeholder="Enter ID number" required>
                </div>

                <div class="mb-3">
                    <label for="phone_my" class="form-label">Phone Number (MY)</label>
                    <input type="text" class="form-control" id="phone_my" name="phone_my" placeholder="Enter MY phone number">
                </div>

                <div class="mb-3">
                    <label for="phone_sg" class="form-label">Phone Number (SG)</label>
                    <input type="text" class="form-control" id="phone_sg" name="phone_sg" placeholder="Enter SG phone number">
                </div>

                <div class="mb-3">
                    <label for="truck" class="form-label">Default Lorry</label>
                    <select class="form-select" id="truck" name="truck">
                        <option selected disabled>Search</option>
                        <option value="ABC 1111">ABC 1111</option>
                        <option value="DEF 2222">DEF 2222</option>
                        <option value="GHI 3333">GHI 3333</option>
                        <option value="JKL 3A3A">JKL 3A3A</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="license" class="form-label">License</label>
                    <input type="text" class="form-control" id="license" name="license" placeholder="Enter license number">
                </div>

                <div class="mb-3">
                    <label for="license_expiry" class="form-label">License Expiry Date</label>
                    <input type="date" class="form-control" id="license_expiry" name="license_expiry">
                </div>

                <div class="mb-3">
                    <label for="license_remind" class="form-label">Remind before license expiry date (In Days)</label>
                    <input type="number" class="form-control" id="license_remind" name="license_remind" placeholder="e.g., 30" min="0">
                </div>

                <div class="mb-3">
                    <label for="passport" class="form-label">Passport</label>
                    <input type="text" class="form-control" id="passport" name="passport" placeholder="Enter passport number">
                </div>

                <div class="mb-3">
                    <label for="passport_expiry" class="form-label">Passport Expiry Date</label>
                    <input type="date" class="form-control" id="passport_expiry" name="passport_expiry">
                </div>

                <div class="mb-3">
                    <label for="passport_remind" class="form-label">Remind before passport expiry date (In Days)</label>
                    <input type="number" class="form-control" id="passport_remind" name="passport_remind" placeholder="e.g., 30" min="0">
                </div>

                <div class="mb-3">
                    <label for="gdl_expiry" class="form-label">GDL Expiry Date</label>
                    <input type="date" class="form-control" id="gdl_expiry" name="gdl_expiry">
                </div>

                <div class="mb-3">
                    <label for="gdl_remind" class="form-label">Remind before GDL expiry date (In Days)</label>
                    <input type="number" class="form-control" id="gdl_remind" name="gdl_remind" placeholder="e.g., 30" min="0">
                </div>

                <div class="mb-3">
                    <label for="note" class="form-label">Note</label>
                    <textarea class="form-control" id="note" name="note" rows="3" placeholder="Enter any notes"></textarea>
                </div>

                <div class="text-end">
                    <button type="submit" class="btn btn-dark">Create Driver</button>
                </div>
            </form>
        </div>
    </div>
@endsection
