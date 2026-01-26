@extends('component.layout')
@section('title', 'Create Subcon')
@section('content')
    <div class="d-flex justify-content-center">
        <div class="card p-4" style="min-width: 400px; max-width: 600px; width: 100%;">
            <h4 class="mb-4">Create Subcon</h4>

            {{-- Show validation errors --}}
            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('subcon.store') }}" method="POST">
                @csrf

                <div class="mb-3">
                    <label for="subcon_name" class="form-label">Subcon Name</label>
                    <input type="text" name="subcon_name" class="form-control" id="subcon_name" placeholder="Enter name"
                        value="{{ old('subcon_name') }}">
                </div>

                <div class="mb-3">
                    <label for="truck_no" class="form-label">Truck Number</label>
                    <input type="text" name="truck_no" class="form-control" id="truck_no"
                        placeholder="Enter truck number" value="{{ old('truck_no') }}">
                </div>

                <div class="mb-3">
                    <label for="driver_name" class="form-label">Driver Name</label>
                    <input type="text" name="driver_name" class="form-control" id="driver_name"
                        placeholder="Enter driver name" value="{{ old('driver_name') }}">
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
                    <label for="group" class="form-label">Group</label>
                    <input type="text" name="group" class="form-control" id="group" placeholder="Enter group">
                </div>

                <div class="mb-3">
                    <label for="size" class="form-label">Size</label>
                    <select name="size" class="form-select" id="size">
                        <option value="" selected disabled>-- Select Size --</option>
                        <option value="20">20</option>
                        <option value="40">40</option>
                        <option value="Small">Small</option>

                    </select>
                </div>

                <div class="mb-3">
                    <label for="tonnage" class="form-label">Tonnage</label>
                    <input type="number" step="0.01" name="tonnage" class="form-control" id="tonnage"
                        placeholder="Enter tonnage">
                </div>

                <div class="mb-3">
                    <label for="floor_space" class="form-label">Floor Space (sqft)</label>
                    <input type="number" step="0.01" name="floor_space" class="form-control" id="floor_space"
                        placeholder="Enter floor space">
                </div>

                <div class="mb-3">
                    <label for="chassis_type" class="form-label">Chassis Type</label>
                    <input type="text" class="form-control" name="chassis_type" id="chassis_type"
                        placeholder="Enter chassis type">
                </div>


                <div class="mb-3">
                    <label for="phone_my" class="form-label">Phone Number (MY)</label>
                    <input type="text" name="phone_my" class="form-control" id="phone_my"
                        placeholder="Enter MY phone number" value="{{ old('phone_my') }}">
                </div>

                <div class="mb-3">
                    <label for="phone_sg" class="form-label">Phone Number (SG)</label>
                    <input type="text" name="phone_sg" class="form-control" id="phone_sg"
                        placeholder="Enter SG phone number" value="{{ old('phone_sg') }}">
                </div>

                <div class="text-end">
                    <a href="{{ route('subcon.index') }}" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-dark">Create Subcon</button>
                </div>
            </form>
        </div>
    </div>
@endsection
