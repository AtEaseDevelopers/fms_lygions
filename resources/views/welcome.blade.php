@extends("component.layout")
@section("title","Dashboard")
@section("content")
<style>
    body { background-color: #f4f3ef; }
    .dash-row { transition: transform .15s ease, box-shadow .15s ease; }
    .dash-row:hover { transform: translateY(-1px); box-shadow: 0 .4rem .8rem rgba(0,0,0,.07) !important; }
    .dash-day { font-size: .7rem; letter-spacing: .08em; }
    .dash-date { font-size: 1.15rem; line-height: 1.1; }
    .dash-num { font-variant-numeric: tabular-nums; }
    .dash-row.is-today { border-left: 4px solid var(--bs-primary) !important; }
    .dash-metric { min-width: 110px; }
    .dash-loc { min-width: 90px; }
    .dash-leave-toggle .bi-chevron-down { transition: transform .15s ease; }
    .dash-leave-toggle[aria-expanded="true"] .bi-chevron-down { transform: rotate(180deg); }
</style>

<div class="container-fluid mt-3">
    <div class="d-flex justify-content-between align-items-center mb-3 gap-2 flex-wrap">
        @if($unreadCount > 0)
            <form method="POST" action="{{ route('notifications.read-all') }}" class="mb-0">
                @csrf
                <button type="submit" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-check2-all"></i> Mark all {{ $unreadCount }} alert{{ $unreadCount === 1 ? '' : 's' }} as read
                </button>
            </form>
        @else
            <span></span>
        @endif
        <form method="GET" action="{{ route('dashboard.index') }}" class="d-flex align-items-center gap-2 mb-0" id="weekForm">
            <a href="?start_date={{ $prevStart }}" class="btn btn-outline-secondary btn-sm" title="Previous week">
                <i class="bi bi-chevron-left"></i>
            </a>
            <input type="date" name="start_date" value="{{ $rangeStart->toDateString() }}"
                   class="form-control form-control-sm dash-num" style="width:160px;"
                   onchange="this.form.submit()">
            <a href="?start_date={{ $nextStart }}" class="btn btn-outline-secondary btn-sm" title="Next week">
                <i class="bi bi-chevron-right"></i>
            </a>
            <span class="text-muted small ms-2 dash-num">
                {{ $rangeStart->format('d M') }} – {{ $rangeEnd->format('d M Y') }}
            </span>
        </form>
    </div>

    <div class="d-flex flex-column gap-2">
        @foreach($cards as $c)
            @php
                $util = $c['util'];
                $bg   = $util >= 70 ? 'bg-danger' : ($util >= 30 ? 'bg-warning' : 'bg-success');
                $txt  = $util >= 70 ? 'text-danger' : ($util >= 30 ? 'text-warning' : 'text-success');
                $isToday = $c['day']->isToday();
            @endphp
            @php
                $dayAlerts = $alertsByDate->get($c['day']->toDateString(), collect());
                $unreadDay = $dayAlerts->whereNull('read_at');
                $readDay   = $dayAlerts->whereNotNull('read_at');
            @endphp
            <div class="card dash-row shadow-sm border-0 rounded-3 {{ $isToday ? 'is-today' : '' }}">
                <div class="card-body p-3 d-flex align-items-center flex-wrap gap-3">
                    <div class="dash-metric">
                        <div class="dash-day fw-bold text-uppercase text-muted">
                            {{ $c['day']->format('D') }}{{ $isToday ? ' · TODAY' : '' }}
                        </div>
                        <div class="dash-date fw-bold">{{ $c['day']->format('d M Y') }}</div>
                    </div>

                    <div class="dash-metric">
                        <div class="text-muted small">Capacity</div>
                        <div class="fw-semibold dash-num">{{ number_format($c['dayCapacity'], 1) }} m³</div>
                    </div>

                    <div class="dash-metric">
                        <div class="text-muted small">Free</div>
                        <div class="fw-semibold dash-num text-success">{{ number_format($c['free'], 1) }} m³</div>
                    </div>

                    <div class="dash-metric">
                        <div class="text-muted small">Booked</div>
                        <div class="fw-semibold dash-num {{ $txt }}">{{ number_format($c['totalBooked'], 1) }} m³</div>
                    </div>

                    <div class="flex-grow-1" style="min-width: 180px;">
                        <div class="progress" style="height:8px;">
                            <div class="progress-bar {{ $bg }}" role="progressbar"
                                 style="width: {{ min($util, 100) }}%"
                                 aria-valuenow="{{ $util }}" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                    </div>

                    <div class="dash-loc text-end">
                        <div class="dash-day fw-bold text-muted">MY</div>
                        <div class="fw-bold dash-num">{{ number_format($c['myBooked'], 1) }} m³</div>
                        <div class="small text-muted">{{ $c['myCount'] }} {{ $c['myCount'] === 1 ? 'booking' : 'bookings' }}</div>
                    </div>

                    <div class="dash-loc text-end">
                        <div class="dash-day fw-bold text-muted">SG</div>
                        <div class="fw-bold dash-num">{{ number_format($c['sgBooked'], 1) }} m³</div>
                        <div class="small text-muted">{{ $c['sgCount'] }} {{ $c['sgCount'] === 1 ? 'booking' : 'bookings' }}</div>
                    </div>

                    <span class="badge {{ $bg }} dash-num py-2 px-3" style="font-size:.95rem; min-width:64px;">
                        {{ number_format($util, 0) }}%
                    </span>

                    @if($c['dayCapacity'] == 0)
                        <span class="text-muted small ms-2"><i class="bi bi-info-circle"></i> No active fleet</span>
                    @endif

                    @if($unreadDay->isNotEmpty())
                        <span class="badge bg-warning text-dark dash-num py-2 px-3" style="font-size:.85rem;">
                            <i class="bi bi-exclamation-triangle-fill"></i> {{ $unreadDay->count() }} alert{{ $unreadDay->count() === 1 ? '' : 's' }}
                        </span>
                    @endif

                    @if(!empty($c['leaves']))
                        @php $lid = 'leaves-'.$c['day']->format('Ymd'); @endphp
                        <button type="button"
                                class="badge bg-info text-dark dash-num py-2 px-3 border-0 dash-leave-toggle"
                                style="font-size:.85rem;"
                                data-leave-target="#{{ $lid }}"
                                aria-expanded="false" aria-controls="{{ $lid }}">
                            <i class="bi bi-person-x-fill"></i>
                            {{ count($c['leaves']) }} on leave
                            <i class="bi bi-chevron-down ms-1"></i>
                        </button>
                    @endif
                </div>

                @if($unreadDay->isNotEmpty())
                    <div class="card-footer bg-warning-subtle border-0 p-3">
                        <ul class="list-group list-group-flush">
                            @foreach($unreadDay as $n)
                                <li class="list-group-item bg-transparent d-flex justify-content-between align-items-start gap-3 px-0">
                                    <div class="flex-grow-1">
                                        <div class="small text-muted">{{ $n->created_at->format('d M Y H:i') }}</div>
                                        <div>{{ $n->message }}</div>
                                        @if($n->consignment)
                                            <a class="small" href="{{ url('/consignment-order') }}?search={{ urlencode($n->consignment->consignment_no) }}">
                                                View {{ $n->consignment->consignment_no }}
                                            </a>
                                        @endif
                                    </div>
                                    <form method="POST" action="{{ route('notifications.read', $n->id) }}" class="mb-0">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-primary">Mark read</button>
                                    </form>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if(!empty($c['leaves']))
                    @php $lid = 'leaves-'.$c['day']->format('Ymd'); @endphp
                    <div class="collapse" id="{{ $lid }}">
                        <div class="card-footer bg-info-subtle border-0 p-3">
                            <ul class="list-group list-group-flush">
                                @foreach($c['leaves'] as $leave)
                                    <li class="list-group-item bg-transparent px-0">
                                        <span class="fw-semibold">{{ $leave['name'] }}</span>@if(!empty($leave['remarks']))<span class="text-muted"> — {{ $leave['remarks'] }}</span>@endif
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                @endif

                @if($readDay->isNotEmpty())
                    @php $rid = 'read-'.$c['day']->format('Ymd'); @endphp
                    <div class="card-footer bg-light border-0 p-2">
                        <button class="btn btn-sm btn-link text-muted text-decoration-none p-0"
                                type="button" data-bs-toggle="collapse" data-bs-target="#{{ $rid }}"
                                aria-expanded="false" aria-controls="{{ $rid }}">
                            <i class="bi bi-clock-history"></i>
                            Show {{ $readDay->count() }} dismissed
                        </button>
                        <div class="collapse mt-2" id="{{ $rid }}">
                            <ul class="list-group list-group-flush">
                                @foreach($readDay as $n)
                                    <li class="list-group-item bg-transparent d-flex justify-content-between align-items-start gap-3 px-0 text-muted">
                                        <div class="flex-grow-1">
                                            <div class="small">
                                                {{ $n->created_at->format('d M Y H:i') }}
                                                @if($n->read_at)
                                                    · dismissed {{ $n->read_at->format('d M Y H:i') }}
                                                @endif
                                            </div>
                                            <div><s>{{ $n->message }}</s></div>
                                            @if($n->consignment)
                                                <a class="small" href="{{ url('/consignment-order') }}?search={{ urlencode($n->consignment->consignment_no) }}">
                                                    View {{ $n->consignment->consignment_no }}
                                                </a>
                                            @endif
                                        </div>
                                        <form method="POST" action="{{ route('notifications.unread', $n->id) }}" class="mb-0">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-secondary">
                                                <i class="bi bi-arrow-counterclockwise"></i> Restore
                                            </button>
                                        </form>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                @endif
            </div>
        @endforeach
    </div>
</div>

<script>
    document.addEventListener('click', function (e) {
        const btn = e.target.closest('[data-leave-target]');
        if (!btn) return;
        e.preventDefault();
        const target = document.querySelector(btn.getAttribute('data-leave-target'));
        if (!target) return;
        const instance = bootstrap.Collapse.getOrCreateInstance(target, { toggle: false });
        const isShown = target.classList.contains('show');
        if (isShown) {
            instance.hide();
            btn.setAttribute('aria-expanded', 'false');
        } else {
            instance.show();
            btn.setAttribute('aria-expanded', 'true');
        }
    });
</script>

@endsection
