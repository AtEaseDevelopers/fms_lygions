<!DOCTYPE html>

<html lang="en">

<head>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta charset="utf-8" />
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
    <title>FMS | @yield('title')</title>
    <meta content='width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0, shrink-to-fit=no'
        name='viewport' />
    <!--     Fonts and icons     -->
    <link href="https://fonts.googleapis.com/css?family=Montserrat:400,700,200" rel="stylesheet" />
    <link href="https://maxcdn.bootstrapcdn.com/font-awesome/latest/css/font-awesome.min.css" rel="stylesheet">
    <!-- CSS Files -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-LN+7fdVzj6u52u30Kp6M/trliBMCMKTyK833zpbD+pXdCLuTusPj697FH4R/5mcr" crossorigin="anonymous">

    <link href="https://cdn.jsdelivr.net/npm/@fullcalendar/core/main.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/@fullcalendar/daygrid/main.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/@fullcalendar/timegrid/main.css" rel="stylesheet" />

    <link href={{ asset('css/bootstrap.min.css') }} rel="stylesheet" />
    <link href={{ asset('css/paper-dashboard.css?v=2.0.1') }} rel="stylesheet" />
    <link href={{ asset('demo/demo.css') }} rel="stylesheet" />
</head>
<style>
    .collapse-menu {
        max-height: 0;
        overflow: hidden;
        padding-left: 20px;
        transition: max-height 0.4s ease;
    }

    .collapse-menu.show {
        max-height: 500px;
        padding-left: 20px;
        /* large enough to fit all submenu items */
    }

    .collapse-menu2 {
        max-height: 0;
        overflow: hidden;
        padding-left: 20px;
        transition: max-height 0.4s ease;
    }

    .collapse-menu2.show2 {
        max-height: 500px;
        padding-left: 20px;
        /* large enough to fit all submenu items */
    }


    /* Base container */
    .slider-toggle {
        position: relative;
        display: inline-block;
        width: 110px;
        height: 36px;
        background-color: red;
        border-radius: 30px;
        /* Ensures roundness */
        cursor: pointer;
        transition: background-color 0.4s;
    }

    /* Hide the default checkbox */
    .slider-toggle input {
        opacity: 0;
        width: 0;
        height: 0;
    }

    /* Label text ("14 Days") */
    .slider-text {
        position: absolute;
        width: 100%;
        height: 100%;
        text-align: center;
        line-height: 36px;
        color: white;
        font-weight: bold;
        font-size: 13px;
        pointer-events: none;
        z-index: 1;
        border-radius: 30px;
        /* Prevent loss of rounding */
        background-color: transparent;
        /* Transparent so bg is inherited from container */
    }

    /* The round slider knob */
    .slider-knob {
        position: absolute;
        height: 25px;
        width: 25px;
        left: 5px;
        top: 5px;
        background-color: white;
        border-radius: 50%;
        transition: transform 0.5s;
        z-index: 2;
    }

    /* When toggled ON */
    .slider-toggle input:checked+.slider-text {
        /* Don't override the rounding here */
    }

    .slider-toggle input:checked~.slider-knob {
        transform: translateX(75px);
    }

    .slider-toggle input:checked~.slider-text,
    .slider-toggle input:checked {
        background-color: green;
    }

    /* Maintain rounded corners in all states */
    .slider-toggle,
    .slider-text,
    .slider-knob {
        border-radius: 30px;
    }
</style>
<body class="">
    @auth
        <div class="wrapper">
            @include('component.sidebar')
            <div class="main-panel">
                @include('component.header')
                <div class="content">
                    @yield('content')
                </div>
                @include('component.footer')
            </div>
        </div>
    @else
        <div class="container d-flex justify-content-center align-items-center" style="min-height:100vh;">
            @yield('content')
        </div>
    @endauth
    <!--   Core JS Files   -->
    <script src={{ asset('js/core/jquery.min.js') }}></script>
    <script src={{ asset('js/core/popper.min.js') }}></script>
    <script src={{ asset('js/core/bootstrap.min.js') }}></script>
    <script src={{ asset('js/plugins/perfect-scrollbar.jquery.min.js') }}></script>
    <!--  Google Maps Plugin    -->
    {{-- <script src="https://maps.googleapis.com/maps/api/js?key=YOUR_KEY_HERE"></script> --}}
    <!-- Chart JS -->

    <script src={{ asset('js/plugins/chartjs.min.js') }}></script>
    <!--  Notifications Plugin    -->
    <script src={{ asset('js/plugins/bootstrap-notify.js') }}></script>
    <!-- Control Center for Now Ui Dashboard: parallax effects, scripts for the example pages etc -->

    <script src={{ asset('js/paper-dashboard.min.js?v=2.0.1') }} type="text/javascript"></script><!-- Paper Dashboard DEMO methods, don't include it in your project! -->
    <script src={{ asset('demo/demo.js') }}></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-ndDqU0Gzau9qJ1lfW4pNLlhNTkCfHzAVBReH9diLvGRem5+R9g2FzA8ZGN954O5Q" crossorigin="anonymous">
    </script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"
        integrity="sha384-I7E8VVD/ismYTF4hNIPjVp/Zjvgyol6VFvRkX/vR+Vc4jQkC+hVqc2pM8ODewa9r" crossorigin="anonymous">
    </script>

    <!-- Moment.js -->
    <script src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>

    <!-- Daterangepicker CSS + JS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
    <script src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js"></script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.min.js"
        integrity="sha384-7qAoOXltbVP82dhxHAUje59V5r2YsVfBafyUDxEdApLPmcdhBPg1DKg1ERo0BZlK" crossorigin="anonymous">
    </script>
    <script>
        $(document).ready(function() {
            // Javascript method's body can be found in assets/assets-for-demo/js/demo.js
            demo.initChartsPages();
        });

        document.addEventListener("DOMContentLoaded", function() {
            const body = document.body;
            const toggleButton = document.getElementById("minimizeSidebar");

            // Restore sidebar state immediately (before flicker)
            if (localStorage.getItem("sidebarState") === "mini") {
                body.classList.add("sidebar-mini");
            }

            // Toggle button click
            toggleButton.addEventListener("click", function() {
                body.classList.toggle("sidebar-mini");

                if (body.classList.contains("sidebar-mini")) {
                    localStorage.setItem("sidebarState", "mini");
                } else {
                    localStorage.setItem("sidebarState", "regular");
                }
            });

            const dropdownToggle = document.querySelector(".dropdown-toggle");
            const submenu = document.querySelector(".collapse-menu");

            dropdownToggle.addEventListener("click", function() {
                submenu.classList.toggle("show");
            });

            const dropdownToggle2 = document.querySelector(".dropdown-toggle2");
            const submenu2 = document.querySelector(".collapse-menu2");

            dropdownToggle2.addEventListener("click", function() {
                submenu2.classList.toggle("show2");
            });

            const form = document.getElementById('filterForm');
            const clearBtn = document.getElementById('clearBtn');

            function checkFormValues() {
                let hasValue = false;

                form.querySelectorAll('input, select').forEach(el => {
                    if (el.type === 'text' && el.value.trim() !== '') {
                        hasValue = true;
                    } else if (el.tagName === 'SELECT' && el.value !== '') {
                        hasValue = true;
                    }
                });

                clearBtn.classList.toggle('d-none', !hasValue);
            }

            // Watch for changes
            form.querySelectorAll('input, select').forEach(el => {
                el.addEventListener('input', checkFormValues);
                el.addEventListener('change', checkFormValues);
            });

            // Hide after reset
            form.addEventListener('reset', () => {
                setTimeout(updateSelectBackground, 0);
                setTimeout(() => {
                    clearBtn.classList.add('d-none');
                }, 10);
            });

            document.getElementById('statusFilter').addEventListener('change', updateSelectBackground);

            // Run on page load (in case filters are prefilled)
            checkFormValues();
            updateSelectBackground();

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


        });

        function updateSelectBackground() {
            const select = document.getElementById('statusFilter');
            const value = select.value;

            // Reset background and text color
            select.classList.remove('bg-warning', 'bg-success', 'bg-secondary', 'text-dark', 'text-white');

            // Apply color based on value
            if (value === 'planning') {
                select.classList.add('bg-warning', 'text-dark');
            } else if (value === 'completed') {
                select.classList.add('bg-success', 'text-white');
            } else if (value === 'pending') {
                select.classList.add('bg-secondary', 'text-white');
            }
        }

        document.querySelectorAll('.btn-group').forEach(group => {
            group.querySelectorAll('.btn').forEach(btn => {
                btn.addEventListener('click', () => {
                    group.querySelectorAll('.btn').forEach(b => b.classList.remove('btn-warning',
                        'active'));
                    btn.classList.add('btn-warning', 'active');
                });
            });
        });

        $(function() {
            $('#filter_daterange').daterangepicker({
                autoUpdateInput: false,
                locale: {
                    cancelLabel: 'Clear',
                    format: 'YYYY-MM-DD'
                }
            });

            $('#filter_daterange').on('apply.daterangepicker', function(ev, picker) {
                $(this).val(picker.startDate.format('YYYY-MM-DD') + ' to ' + picker.endDate.format(
                    'YYYY-MM-DD'));
                $('#filterForm').submit();
            });

            $('#filter_daterange').on('cancel.daterangepicker', function(ev, picker) {
                $(this).val('');
                $('#filterForm').submit();
            });
        });

        const toggle = document.getElementById('toggle14Days');
        toggle.addEventListener('change', () => {
            if (toggle.checked) {
                console.log("14-day view ON");
                // add your logic here
            } else {
                console.log("14-day view OFF");
            }
        });
    </script>
</body>

</html>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
