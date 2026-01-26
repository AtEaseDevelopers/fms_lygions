@extends('component.layout')
@section('title', 'Create Truck')
@section('content')
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    <div class="d-flex justify-content-center">
        <div class="card p-4" style="min-width: 400px; max-width: 600px; width: 100%;">
            <h4 class="mb-4">Create Lorry</h4>
            <form method="POST" action="{{ route('truck.store') }}">
                @csrf
                <div class="mb-3">
                    <label for="number" class="form-label">Number</label>
                    <input type="text" name="number" class="form-control" id="number"
                        placeholder="Enter lorry number" required>
                </div>

                <div class="mb-3">
                    <label for="group" class="form-label">Group</label>
                    <input type="text" name="group" class="form-control" id="group" placeholder="Enter group"
                        required>
                </div>

                <div class="mb-3">
                    <label for="group" class="form-label">Size</label>
                    <select name="size" class="form-select" id="size">
                        <option value="" selected disabled>-- Select Size --</option>
                        <option value="Any">Any</option>
                        <option value="Small">Small</option>

                    </select>
                </div>

                <div class="mb-3">
                    <label for="team" class="form-label">Team</label>
                    <select name="team" class="form-select" id="team">
                        <option value="" selected disabled>-- Select Team --</option>
                        <option value="A">A</option>
                        <option value="B">B</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="tonnage" class="form-label">Tonnage</label>
                    <input type="number" step="0.01" name="tonnage" class="form-control" id="tonnage"
                        placeholder="Enter tonnage" required>
                </div>

                <div class="mb-3">
                    <label for="floor_space" class="form-label">Floor Space (sqft)</label>
                    <input type="number" step="0.01" name="floor_space" class="form-control" id="floor_space"
                        placeholder="Enter floor space" required>
                </div>

                <div class="mb-3">
                    <label for="chassis_type" class="form-label">Chassis Type</label>
                    <select class="form-select" name="chassis_type" id="chassis_type" required>
                        <option selected disabled>Select Type</option>
                        <option value="open">Open</option>
                        <option value="curtain">Curtain</option>
                        <option value="tailgate">Tailgate</option>
                    </select>
                </div>

                <!-- Extra Date Fields -->
                <div class="mb-3">
                    <label for="next_inspection" class="form-label">Next Inspection Date</label>
                    <input type="date" name="next_inspection" class="form-control" id="next_inspection">
                </div>

                <div class="mb-3">
                    <label for="next_tyre" class="form-label">Next Tyre Date</label>
                    <input type="date" name="next_tyre" class="form-control" id="next_tyre">
                </div>

                <div class="mb-3">
                    <label for="next_permit" class="form-label">Next Permit Date</label>
                    <input type="date" name="next_permit" class="form-control" id="next_permit">
                </div>

                <div class="mb-3">
                    <label for="next_extinguisher" class="form-label">Next Fire Extinguisher Date</label>
                    <input type="date" name="next_extinguisher" class="form-control" id="next_extinguisher">
                </div>

                <div class="mb-3">
                    <label for="next_roadtax" class="form-label">Next Roadtax Date</label>
                    <input type="date" name="next_roadtax" class="form-control" id="next_roadtax">
                </div>

                <div class="mb-3">
                    <label for="next_insurance" class="form-label">Next Insurance Date</label>
                    <input type="date" name="next_insurance" class="form-control" id="next_insurance">
                </div>

                <div class="mb-3">
                    <label for="next_others" class="form-label">Next Others Date</label>
                    <input type="date" name="next_others" class="form-control" id="next_others">
                </div>

                <div class="text-end">
                    <button type="submit" class="btn btn-dark">Create Truck</button>
                </div>
            </form>
        </div>
    </div>

@endsection
