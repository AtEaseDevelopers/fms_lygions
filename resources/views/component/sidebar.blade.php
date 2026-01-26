@auth
@php
$isMasterActive =
        request()->routeIs('customer.*') ||
        request()->routeIs('draft-customer.*') ||
        request()->routeIs('truck.*') ||
        request()->routeIs('truck-type.*') ||
        request()->routeIs('subcon.*') ||
        request()->routeIs('driver.*') ||
        request()->routeIs('location.*') ||
        request()->routeIs('unit-param.*');

    $isManagementActive = request()->routeIs('role.*') || request()->routeIs('user.*');
@endphp
<div class="sidebar" data-color="white" data-active-color="danger">
    <div class="logo d-flex align-items-center px-3 py-2">
        <a href="#" class="d-flex align-items-center text-decoration-none">
            <div class="logo-image-small d-flex align-items-center">
                <img src="{{ asset('img/logo-small.png') }}" alt="Logo" style="height:50px;">
                <span class="logo-text ms-2">FMS</span>
            </div>
        </a>


        <button id="minimizeSidebar" class="btn btn-icon btn-round ms-auto">
            <i class="nc-icon nc-minimal-right text-center visible-on-sidebar-mini"></i>
            <i class="nc-icon nc-minimal-left text-center visible-on-sidebar-regular"></i>
        </button>
    </div>

    <div class="sidebar-wrapper">
        <ul class="nav">

            {{-- Dashboard --}}
            @if (auth()->user()->hasPermission('dashboard'))
                <li class="{{ request()->routeIs('dashboard.index') || request()->is('/') ? 'active' : '' }}">
                    <a href="{{ route('dashboard.index') }}">
                        <i class="nc-icon nc-bank" data-bs-toggle="tooltip" data-bs-placement="right"
                            title="Dashboard"></i>
                        <p>Dashboard</p>
                    </a>
                </li>
            @endif

            {{-- Truck Planning --}}
            @if (auth()->user()->hasPermission('truck-planning'))
                <li
                    class="{{ request()->routeIs('consignment-order.index', 'archived-consignment-order.index') ? 'active' : '' }}">
                    <a href="{{ route('consignment-order.index') }}">
                        <i class="nc-icon nc-bell-55" data-bs-toggle="tooltip" data-bs-placement="right"
                            title="Truck Planning"></i>
                        <p>Truck Planning</p>
                    </a>
                </li>
            @endif

            {{-- Truck Capacity --}}
            @if (auth()->user()->hasPermission('calendar'))
                <li class="{{ request()->routeIs('calendar.index') ? 'active' : '' }}">
                    <a href="{{ route('calendar.index') }}">
                        <i class="nc-icon nc-calendar-60" data-bs-toggle="tooltip" data-bs-placement="right"
                            title="Truck Capacity"></i>
                        <p>Truck Capacity</p>
                    </a>
                </li>
            @endif

            {{-- Truck Summary --}}
            @if (auth()->user()->hasPermission('truck-summary'))
                <li class="{{ request()->routeIs('truck-summary.index') ? 'active' : '' }}">
                    <a href="{{ route('truck-summary.index') }}">
                        <i class="nc-icon nc-single-copy-04" data-bs-toggle="tooltip" data-bs-placement="right"
                            title="Truck Summary"></i>
                        <p>Truck Summary</p>
                    </a>
                </li>
            @endif

            {{-- Driver Leave Plan --}}
            @if (auth()->user()->hasPermission('driver-leave'))
                <li class="nav-item {{ request()->routeIs('driver-holidays.*') ? 'active' : '' }}">
                    <a href="{{ route('driver-holidays.index') }}">
                        <i class="nc-icon nc-delivery-fast" data-bs-toggle="tooltip" data-bs-placement="right"
                            title="Driver Leave"></i>
                        Driver Leave Plan
                    </a>
                </li>
            @endif

            {{-- Master Data --}}
            @if (auth()->user()->hasPermission('master.customer') ||
                    auth()->user()->hasPermission('master.draft-customer') ||
                    auth()->user()->hasPermission('master.subcon') ||
                    auth()->user()->hasPermission('master.truck') ||
                    auth()->user()->hasPermission('master.driver') ||
                    auth()->user()->hasPermission('master.unit-param') ||
                    auth()->user()->hasPermission('master.location'))
                <li class="nav-item">
                    <a href="javascript:void(0);" class="dropdown-toggle">
                        <span><i class="nc-icon nc-layout-11" data-bs-toggle="tooltip" data-bs-placement="right"
                                title="Master Data"></i> Master Data</span>
                    </a>

                    <ul class="nav submenu collapse-menu {{ $isMasterActive ? 'show' : '' }}" style="margin-top:0">
                        @if (auth()->user()->hasPermission('master.customer'))
                            <li class="{{ request()->routeIs('customer.*') ? 'active' : '' }}">
                                <a href="{{ route('customer.index') }}"><i class="nc-icon nc-single-02"></i>
                                    Customer</a>
                            </li>
                        @endif

                        @if (auth()->user()->hasPermission('master.draft-customer'))
                            <li class="{{ request()->routeIs('draft-customer.*') ? 'active' : '' }}">
                                <a href="{{ route('draft-customer.index') }}"><i class="nc-icon nc-satisfied"></i>
                                    Draft Customer</a>
                            </li>
                        @endif

                        @if (auth()->user()->hasPermission('master.subcon'))
                            <li class="{{ request()->routeIs('subcon.*') ? 'active' : '' }}">
                                <a href="{{ route('subcon.index') }}"><i class="bi bi-subtract"></i> Subcon</a>
                            </li>
                        @endif

                        @if (auth()->user()->hasPermission('master.truck'))
                            <li class="{{ request()->routeIs('truck.*') ? 'active' : '' }}">
                                <a href="{{ route('truck.index') }}"><i class="nc-icon nc-ambulance"></i> Truck</a>
                            </li>
                        @endif

                        @if (auth()->user()->hasPermission('master.driver'))
                            <li class="{{ request()->routeIs('driver.*') ? 'active' : '' }}">
                                <a href="{{ route('driver.index') }}"><i class="nc-icon nc-user-run"></i> Driver</a>
                            </li>
                        @endif

                        @if (auth()->user()->hasPermission('master.unit-param'))
                            <li class="{{ request()->routeIs('unit-param.index') ? 'active' : '' }}">
                                <a href="{{ route('unit-param.index') }}"><i class="nc-icon nc-layout-11"></i> Std Unit
                                    Param</a>
                            </li>
                        @endif

                        @if (auth()->user()->hasPermission('master.location'))
                            <li class="{{ request()->routeIs('location.index') ? 'active' : '' }}">
                                <a href="{{ route('location.index') }}"><i class="nc-icon nc-pin-3"></i> Location</a>
                            </li>
                        @endif
                    </ul>
                </li>
            @endif

            {{-- Activity Log --}}
            @if (auth()->user()->hasPermission('activity-log'))
                <li class="{{ request()->routeIs('activity-log.index') ? 'active' : '' }}">
                    <a href="{{ route('activity-log.index') }}">
                        <i class="nc-icon nc-tile-56"></i>
                        <p>Activity Log</p>
                    </a>
                </li>
            @endif

            {{-- User & Role Management --}}
            @if (auth()->user()->hasPermission('user-management') || auth()->user()->hasPermission('role-management'))
                <li class="nav-item">
                    <a href="javascript:void(0);" class="dropdown-toggle2">
                        <span><i class="nc-icon nc-layout-11"></i> User & Role</span>
                    </a>

                    <ul class="nav submenu collapse-menu2 {{ $isManagementActive ? 'show2' : '' }}"
                        style="margin-top:0">
                        @if (auth()->user()->hasPermission('user-management'))
                            <li class="{{ request()->routeIs('user.index') ? 'active' : '' }}">
                                <a href="{{ route('user.index') }}"><i class="nc-icon nc-single-02"></i> User
                                    Management</a>
                            </li>
                        @endif

                        @if (auth()->user()->hasPermission('role-management'))
                            <li class="{{ request()->routeIs('role.index') ? 'active' : '' }}">
                                <a href="{{ route('role.index') }}"><i class="nc-icon nc-badge"></i> Role
                                    Management</a>
                            </li>
                        @endif
                    </ul>
                </li>
            @endif

            {{-- Logout --}}
            <li>
                <a href="{{ route('logout') }}"
                    onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                    <i class="nc-icon nc-lock-circle-open"></i>
                    <p>Log Out</p>
                </a>

                <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                    @csrf
                </form>
            </li>


        </ul>
    </div>

</div>
@php
$canSeeUserRoleOnly = (
    (auth()->user()->hasPermission('user-management') || auth()->user()->hasPermission('role-management'))
    && !(
        auth()->user()->hasPermission('master.customer') ||
        auth()->user()->hasPermission('master.draft-customer') ||
        auth()->user()->hasPermission('master.subcon') ||
        auth()->user()->hasPermission('master.truck') ||
        auth()->user()->hasPermission('master.driver') ||
        auth()->user()->hasPermission('master.unit-param') ||
        auth()->user()->hasPermission('master.location')
    )
);
@endphp
<script>
    document.addEventListener('DOMContentLoaded', function() {
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function(tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    });

</script>
@if($canSeeUserRoleOnly)
<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.dropdown-toggle2').forEach(toggle => {
        toggle.addEventListener('click', function(e) {
            e.preventDefault(); // prevent page jump
            const submenu = this.nextElementSibling;
            if (submenu) {
                submenu.classList.toggle('show2');
            }
        });
    });
});
</script>
@endif
@endauth
