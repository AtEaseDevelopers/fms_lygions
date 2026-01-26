@extends('component.layout')
@section('title', 'Driver Holiday')
@section('content')
    {{--
<div class="card">
  <div class="card-header">
    <h4 class="card-title">Holiday Schedule</h4>
  </div>
  <div class="card-body">
    <div class="table-responsive">
      <table class="table table-bordered">
        <thead class="text-primary">
          <tr>
            <th>Location</th>
            <th>28-Oct (Mon)</th>
            <th>29-Oct (Tue)</th>
            <th>30-Oct (Wed)</th>
            <th>31-Oct (Thu)</th>
            <th>01-Nov (Fri)</th>
            <th>02-Nov (Sat)</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td>KL</td>
            <td>Driver A</td>
            <td>Driver B</td>
            <td>Driver C</td>
            <td>Driver D</td>
            <td>Driver E</td>
            <td class="text-danger">H</td>
          </tr>
          <tr>
            <td>SG</td>
            <td>Driver F</td>
            <td>Driver G</td>
            <td>Driver H</td>
            <td>Driver I</td>
            <td>Driver J</td>
            <td class="text-danger">H</td>
          </tr>
          <tr>
            <td>JB</td>
            <td>Driver K</td>
            <td>Driver L</td>
            <td>Driver M</td>
            <td>Driver N</td>
            <td>Driver O</td>
            <td class="text-danger">H</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</div> --}}
    {{-- <div id="calendar"></div> --}}
    <div class="container">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title">Holiday Schedule</h4>
            </div>

            <div class="card-body">
                <!-- Truck Legend -->
                <div class="mb-3">
                    <span class="badge bg-primary">Truck</span>
                    <span class="badge bg-success text-dark">Consignor</span>
                    <span class="badge bg-danger text-white">Holiday</span>
                </div>

                <!-- Week Selector Buttons -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <form method="GET" action="{{ route('driver-holiday.index') }}">
                        <input type="hidden" name="week_start" value="{{ $weekStart->copy()->subWeek()->toDateString() }}">
                        <button class="btn btn-secondary">← Previous Week</button>
                    </form>

                    <h4 class="mb-0">{{ $weekStart->format('d M Y') }} -
                        {{ $weekStart->copy()->addDays(6)->format('d M Y') }}</h4>

                    <form method="GET" action="{{ route('driver-holiday.index') }}">
                        <input type="hidden" name="week_start" value="{{ $weekStart->copy()->addWeek()->toDateString() }}">
                        <button class="btn btn-secondary">Next Week →</button>
                    </form>
                </div>

                <!-- Calendar Table -->
                <div class="table-responsive">
                    <table class="table table-bordered text-center align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th>Truck</th>
                                <th>Location</th>
                                @foreach ($days as $day)
                                    <th>
                                        {{ $day->format('D') }}<br>{{ $day->format('d M') }}
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($truckData as $type => $locations)
                                @foreach ($locations as $location => $daysData)
                                    <tr>
                                        <td>{{ $type }}FT</td>
                                        <td>{{ $location }}</td>
                                        @foreach ($days as $day)
                                            @php $isHoliday = in_array($day->toDateString(), $holidays); @endphp
                                            <td class="{{ $isHoliday ? 'bg-danger text-white' : '' }}">
                                                @if (!$isHoliday && isset($truckData[$type][$location][$day->toDateString()]))
                                                    {!! $truckData[$type][$location][$day->toDateString()] !!}
                                                @endif
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
{{-- <script>
            document.addEventListener('DOMContentLoaded', function () {
                var calendarEl = document.getElementById('calendar');
                var calendar = new FullCalendar.Calendar(calendarEl, {
                    initialView: 'timeGridWeek',
                    slotMinTime: '8:00:00',
                    slotMaxTime: '19:00:00',
                    events: @json($events),
                });
                calendar.render();
            });
        </script> --}}
