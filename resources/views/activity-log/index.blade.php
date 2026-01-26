@extends('component.layout')
@section('title', 'Activity Log')
@section('content')
    @php
        $actions = ['Update', 'Create', 'Delete'];
        $functions = ['Master Data - Customers', 'Master Data - Miscellaneous Charges', 'Master Data - Products'];
        $names = ['JENNY', 'ALAN', 'SUSAN', 'MARK'];
    @endphp
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    {{-- <h4 class="card-title"> Simple Table</h4> --}}
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center flex-wrap mb-3">
                        <!-- Search and Filter Form -->
                        <form class="d-flex gap-3 align-items-stretch" style="width: 70%;" id="filterForm">
                            <!-- Search Input -->
                            <div class="input-group no-border w-100"  >
                                <input type="text" class="form-control" placeholder="Search...">
                                <div class="input-group-append">
                                    <span class="input-group-text">
                                        <i class="nc-icon nc-zoom-split"></i>
                                    </span>
                                </div>
                            </div>

                            <!-- Log Name Dropdown -->
                            <div class="input-group" >
                                <select id="logName" name="logName" class="form-control">
                                    <option value="A" selected disabled>Select a Log Name</option>
                                    <option value="customers">Master Data - Customers</option>
                                    <option value="charges">Master Data - Miscellaneous Charges</option>
                                    <option value="products">Master Data - Products</option>
                                </select>
                            </div>

                            <div class="input-group" >
                                <input type="text" id="filter_daterange" name="filter_daterange" class="form-control"
                                    placeholder="Filter Date Range" autocomplete="off">
                                <span class="input-group-text">
                                    <i class="nc-icon nc-calendar-60"></i>
                                </span>
                            </div>
                            <div class="input-group w-100">
                                <button type="reset" class="btn btn-danger d-none" style="margin: 0;" id="clearBtn"> <i
                                        class="bi bi-x"></i> </button>
                            </div>

                        </form>




                    </div>
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered ">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Old Data</th>
                                    <th>New Data</th>
                                    <th>Action</th>
                                    <th>Function</th>
                                    <th>By</th>
                                    <th>Log At</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach (range(1, 10) as $i)
                                    @php
                                        $id = rand(100, 500);
                                        $account = 'A03/' . rand(1000, 9999);
                                        $email = "account{$i}@actcan.com.my";
                                        $name = 'ACTCAN (MALAYSIA) SDN. BHD.';
                                        $action = $actions[array_rand($actions)];
                                        $function = $functions[array_rand($functions)];
                                        $user = $names[array_rand($names)];
                                        $now = now()->subMinutes(rand(0, 60))->format('d M, Y H:i');
                                    @endphp
                                    <tr>
                                        <td>{{ $i }}</td>
                                        <td>
                                            <strong>Id:</strong> {{ $id }}<br>
                                            <strong>Name:</strong> {{ $name }}<br>
                                            <strong>Email:</strong> [{{ $email }}]<br>
                                            <strong>Account number:</strong> {{ $account }}<br>
                                            <strong>Company registration number:</strong> 198001010{{ rand(100, 999) }}<br>
                                            <strong>Phone:</strong> 603-{{ rand(6000, 7999) }} {{ rand(1000, 9999) }}<br>
                                            <strong>City:</strong>
                                            {{ ['Kuala Lumpur', 'Petaling Jaya', 'Shah Alam', 'Subang'][rand(0, 3)] }}<br>
                                            <strong>Country:</strong> Malaysia<br>
                                        </td>
                                        <td>
                                            <strong>Id:</strong> {{ $id }}<br>
                                            <strong>Name:</strong> {{ $name }}<br>
                                            <strong>Email:</strong> [{{ $email }}]<br>
                                            <strong>Account number:</strong> {{ $account }}<br>
                                            <strong>Company registration number:</strong> 198001010{{ rand(100, 999) }}<br>
                                            <strong>Phone:</strong> 603-{{ rand(6000, 7999) }} {{ rand(1000, 9999) }}<br>
                                            <strong>City:</strong>
                                            {{ ['Kuala Lumpur', 'Petaling Jaya', 'Shah Alam', 'Subang'][rand(0, 3)] }}<br>
                                            <strong>Country:</strong> Malaysia<br>
                                        </td>
                                        <td>{{ $action }}</td>
                                        <td>{{ $function }}</td>
                                        <td>{{ $user }}</td>
                                        <td>{{ $now }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
