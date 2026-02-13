<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-absolute fixed-top navbar-transparent">
    <div class="container-fluid">
        <div class="navbar-wrapper">
            <div class="navbar-minimize">

            </div>
            <div class="navbar-toggle">
                <button type="button" class="navbar-toggler">
                    <span class="navbar-toggler-bar bar1"></span>
                    <span class="navbar-toggler-bar bar2"></span>
                    <span class="navbar-toggler-bar bar3"></span>
                </button>
            </div>
            <div class="d-flex align-items-center">
                @yield('back_button')
                <a class="navbar-brand" href="javascript:;" style="margin-left: 0;">@yield('title')</a>
            </div>
        </div>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navigation"
            aria-controls="navigation-index" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-bar navbar-kebab"></span>
            <span class="navbar-toggler-bar navbar-kebab"></span>
            <span class="navbar-toggler-bar navbar-kebab"></span>
        </button>
        <div class="collapse navbar-collapse justify-content-end" id="navigation">
            {{-- <form>
                <div class="input-group no-border">
                    <input type="text" value="A" class="form-control" placeholder="Search...">
                    <div class="input-group-append">
                        <div class="input-group-text">
                            <i class="nc-icon nc-zoom-split"></i>
                        </div>
                    </div>
                </div>
            </form> --}}
            <ul class="navbar-nav">
                {{-- <li class="nav-item">
                    <a class="nav-link btn-magnify" href="javascript:;">
                        <i class="nc-icon nc-layout-11"></i>
                        <p>
                            <span class="d-lg-none d-md-block">Stats</span>
                        </p>
                    </a>
                </li> --}}
                {{-- <li class="nav-item btn-rotate dropdown">
                    <a class="nav-link dropdown-toggle" href="http://example.com" id="navbarDropdownMenuLink"
                        data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <i class="nc-icon nc-bell-55"></i>
                        <p>
                            <span class="d-lg-none d-md-block">Some Actions</span>
                        </p>
                    </a>
                    <div class="dropdown-menu dropdown-menu-right" aria-labelledby="navbarDropdownMenuLink">
                        <a class="dropdown-item" href="#">Action</a>
                        <a class="dropdown-item" href="#">Another action</a>
                        <a class="dropdown-item" href="#">Something else here</a>
                    </div>
                </li> --}}
                {{-- <li class="nav-item">
                    <a class="nav-link btn-rotate" href="javascript:;">
                        <i class="nc-icon nc-settings-gear-65"></i>
                        <p>
                            <span class="d-lg-none d-md-block">Account</span>
                        </p>
                    </a>
                </li> --}}
                <ul class="navbar-nav d-flex align-items-center ms-auto" style="gap: 10px;">
                    <li class="nav-item">
                        <button type="button" class="btn btn-info" style="border-radius: 0.2rem;">
                            <i class="bi bi-clock me-1"></i>
                            <span id="timestampText">Loading...</span>
                        </button>
                    </li>

                    <li class="nav-item btn-rotate dropdown">
                        {{-- <a class="nav-link dropdown-toggle d-flex align-items-center gap-2" href="javascript:;"
                            id="accountDropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <i class="nc-icon nc-single-02" style="font-size: 18px; margin-top: 1px;"></i>
                            <span class="d-flex align-items-center">
                                Hi, <span class="fw-bold ms-1">@auth {{ auth()->user()->name }} @endauth</span>
                            </span>
                        </a> --}}
                        <div class="nav-link d-flex align-items-center gap-2">
                            <i class="nc-icon nc-single-02" style="font-size: 18px; margin-top: 1px;"></i>
                            <span class="d-flex align-items-center" style="font-size: 15px;">
                                Hi, <span class="fw-bold ms-1">@auth {{ auth()->user()->name }} @endauth
                                </span>
                            </span>
                        </div>

                        {{-- <div class="dropdown-menu dropdown-menu-right" aria-labelledby="accountDropdown">
                            <a class="dropdown-item" href="#"> <i class="nc-icon nc-layout-11"></i> &nbsp;
                                Profile</a>
                            <a class="dropdown-item" href="#"> <i class="nc-icon nc-settings-gear-65"></i> &nbsp;
                                Settings</a>
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item" href="#"> <i class="nc-icon nc-lock-circle-open"></i> &nbsp;
                                Logout</a>
                        </div> --}}
                    </li>
                </ul>

            </ul>
        </div>
    </div>
</nav>
<!-- End Navbar -->
<script>
    function updateTimestamp() {
        const now = new Date();

        const dayName = now.toLocaleString('en-US', {
            weekday: 'long'
        });
        const formatted = now.toLocaleString('en-GB', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
            hour12: false
        });

        // Example: "04/08/2025, 00:31:55, Monday"
        document.getElementById('timestampText').textContent = `${dayName} ${formatted}`;
    }

    setInterval(updateTimestamp, 1000);
    updateTimestamp();
</script>
