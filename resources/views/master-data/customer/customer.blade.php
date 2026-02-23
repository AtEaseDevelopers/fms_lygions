@extends('component.layout')
@section('title', 'Customer')
@section('content')

    @php
        $currentPage = $customers->currentPage();
        $lastPage = $customers->lastPage();
        $query = request()->query();
        unset($query['page']);
        unset($query['per_page']);
    @endphp
    @if (session('swal'))
        <script>
            Swal.fire({
                icon: "{{ session('swal.icon') }}",
                title: "{{ session('swal.title') }}",
                text: "{{ session('swal.text') }}",
                showConfirmButton: false,
                timer: 2000
            });
        </script>
    @endif

    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="card-title">Customers</h4>
                    <form method="GET" class="d-flex align-items-center gap-2">
                        <label for="per_page" class="mb-0">Show</label>
                        <select name="per_page" id="per_page" class="form-select" onchange="this.form.submit()">
                            @foreach ([5, 10, 25, 50] as $limit)
                                <option value="{{ $limit }}"
                                    {{ request('per_page', 10) == $limit ? 'selected' : '' }}>{{ $limit }}</option>
                            @endforeach
                        </select>
                        <span class="mb-0">entries</span>
                    </form>
                </div>

                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center flex-wrap mb-3">
                        <!-- Search Form -->
                        <form class="d-flex gap-3 align-items-stretch" style="width: 50%;" id="filterForm">
                            <div class="input-group no-border w-100">
                                <input type="text" class="form-control" placeholder="Search..." name="search"
                                    value="{{ request('search') }}">
                                <div class="input-group-append">
                                    <div class="input-group-text">
                                        <i class="nc-icon nc-zoom-split"></i>
                                    </div>
                                </div>
                            </div>

                            <div class="input-group w-100">
                                <select name="as" class="form-control" onchange="this.form.submit()">
                                    <option value="" {{ request('as') == null ? 'selected' : '' }}>All</option>
                                    <option value="Consignor" {{ request('as') == 'Consignor' ? 'selected' : '' }}>Consignor
                                    </option>
                                    <option value="Consignee" {{ request('as') == 'Consignee' ? 'selected' : '' }}>Consignee
                                    </option>
                                </select>
                            </div>
                            <div class="input-group w-100">
                                <button type="reset" class="btn btn-danger d-none" style="margin: 0;" id="clearBtn">
                                    <i class="bi bi-x"></i>
                                </button>
                            </div>
                        </form>

                        <!-- Buttons -->
                        <div class="d-flex gap-2">
                            <button class="btn
                                btn-outline-primary"
                                style="border-radius: 0.2rem; display: inline-flex; align-items: center;">
                                <i class="bi bi-file-excel-fill" style="font-size: 20px; margin-right: 5px;"></i> Export
                            </button>
                            {{-- <a href="{{ route('customer.create') }}" class="btn btn-primary"
                                style="border-radius: 0.2rem; display: inline-flex; align-items: center;">
                                <i class="bi bi-person-add" style="font-size: 20px; margin-right: 5px;"></i> Create New
                                Customer
                            </a> --}}
                        </div>
                    </div>
                    <div id="tableScrollTop" style="overflow-x:auto; overflow-y:hidden;"></div>
                    <div id="tableScrollBottom" style="overflow-x:auto;">
                        <table class="table table-striped  table-bordered align-middle">
                            <thead class="text-primary">
                                <tr>
                                    <th class="text-center">No</th>
                                    <th>Name</th>
                                    <th>Nickname</th>
                                    <th>As</th>
                                    <th>Account Number</th>
                                    <th>Phone</th>
                                    <th>Billing Address</th>
                                    <th>Email</th>
                                    <th>Company Reg No (New)</th>
                                    <th>Company Reg No (Old)</th>
                                    <th>Website</th>
                                    <th>Billing Phone</th>
                                    <th>Remark</th>
                                    <th>Consignor Currency</th>
                                    <th>Consignee Currency</th>
                                    <th>City</th>
                                    <th>Post Code</th>
                                    <th>State</th>
                                    <th>Country</th>
                                    <th>TIN</th>
                                    <th>Service Tax No</th>
                                    <th>Contact Person</th>
                                    <th>Term</th>

                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($customers as $customer)
                                    <tr>
                                        <td class="text-center">
                                            {{ $loop->iteration + ($customers->currentPage() - 1) * $customers->perPage() }}
                                        </td>
                                        <td>{{ $customer->name }}</td>
                                        <td>{{ $customer->nickname }}</td>
                                        <td>{{ $customer->type }}</td>
                                        <td>{{ $customer->account_number }}</td>
                                        <td>{{ $customer->phone }}</td>
                                        <td>{{ $customer->billing_address }}</td>
                                        <td>{{ $customer->email }}</td>
                                        <td>{{ $customer->company_reg_no_new }}</td>
                                        <td>{{ $customer->company_reg_no_old }}</td>
                                        <td>{{ $customer->website }}</td>
                                        <td>{{ $customer->billing_phone }}</td>
                                        <td>{{ $customer->remark }}</td>
                                        <td>{{ $customer->consignor_currency }}</td>
                                        <td>{{ $customer->consignee_currency }}</td>
                                        <td>{{ $customer->city }}</td>
                                        <td>{{ $customer->post_code }}</td>
                                        <td>{{ $customer->state }}</td>
                                        <td>{{ $customer->country }}</td>
                                        <td>{{ $customer->tin }}</td>
                                        <td>{{ $customer->service_tax_no }}</td>
                                        <td>{{ $customer->contact_person }}</td>
                                        <td>{{ $customer->term }}</td>

                                        <td class="text-center">
                                            <a href="{{ route('customer.edit', $customer->id) }}"
                                                class="btn btn-info ">
                                                <i class="bi bi-pencil-square"></i>
                                            </a>
                                            <form action="{{ route('customer.destroy', $customer->id) }}" method="POST"
                                                class="d-inline delete-form">
                                                @csrf
                                                @method('DELETE')
                                                <button type="button" class="btn btn-danger delete-btn"
                                                    data-number="{{ $customer->name }}">
                                                    <i class="bi bi-trash-fill"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="25" class="text-center">No customers found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mt-4">
                        <div>
                            Showing {{ $customers->firstItem() }} to {{ $customers->lastItem() }} of
                            {{ $customers->total() }} entries
                        </div>

                        <div>
                            {{ $customers->appends(request()->except('page'))->links('pagination::bootstrap-5') }}
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
@endsection
<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    document.addEventListener("DOMContentLoaded", function() {

        const topScroll = document.getElementById("tableScrollTop");
        const bottomScroll = document.getElementById("tableScrollBottom");

        // make top scroller width = table width
        topScroll.innerHTML = "<div style='width:" + bottomScroll.scrollWidth + "px; height:1px;'></div>";

        // sync scrolling
        topScroll.addEventListener("scroll", () => {
            bottomScroll.scrollLeft = topScroll.scrollLeft;
        });
        bottomScroll.addEventListener("scroll", () => {
            topScroll.scrollLeft = bottomScroll.scrollLeft;
        });
        document.querySelectorAll(".delete-btn").forEach(button => {
            button.addEventListener("click", function(e) {
                let form = this.closest("form");
                let customer = this.getAttribute("data-number");

                Swal.fire({
                    title: "Are you sure?",
                    text: "Customer '" + customer + "'' will be permanently deleted!",
                    icon: "warning",
                    showCancelButton: true,
                    confirmButtonColor: "#d33",
                    cancelButtonColor: "#6c757d",
                    confirmButtonText: "Yes, delete it!"
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            });
        });
    });
</script>
