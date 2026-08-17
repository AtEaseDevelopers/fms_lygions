@php
    $status = $cellData['status'] ?? 'empty';
    $used_capacity = $cellData['used_capacity'] ?? 0;
    $total_capacity = $cellData['total_capacity'] ?? 0;
    $consignors = $cellData['consignors'] ?? collect();

    $isAssigned = $consignors->isNotEmpty();
    // Only explicitly-recorded 'available' cells have a backing availability row, so
    // only they can be dragged to relocate the record.
    $hasRecord = $cellData['has_record'] ?? false;
    $isWeekend = \Carbon\Carbon::parse($dateOnly)->isWeekend();
    // Flow-off: truck is committed on the OTHER location as it moves MY<->SG across
    // the work-week (computed in CalendarController::applyAvailabilityFlow).
    $flowOff = $cellData['flow_off'] ?? false;

    $occupiedColor = $location === 'SG' ? '#d1ecf1' : '#f7c6c7';
    // Special-arrangement colour + label (off-day, maintenance, holiday, breakdown,
    // express, inspection, saturday-loading/unloading). Null for plain statuses.
    $profile = \App\Models\Availability::arrangementProfiles()[$status] ?? null;

    if ($isAssigned) {
        // Truck planning assigned -> solid MY (pink) / SG (blue); overrides weekend
        $cellStyle = 'background-color: ' . $occupiedColor . ';';
    } elseif ($profile) {
        // Special arrangement -> its own colour profile
        $cellStyle = 'background-color: ' . $profile['color'] . ';';
    } elseif ($flowOff) {
        // Truck is away on this side today (part of the MY<->SG flow) -> grey
        $cellStyle = 'background-color: #c3c2c2;';
    } elseif ($status === 'available') {
        // Explicitly marked available -> blue (MY) / red (SG)
        $availableColor = $location === 'SG' ? '#f1aeb5' : '#9ec5fe';
        $cellStyle = 'background-color: ' . $availableColor . ';';
    } else {
        // Not available: no availability record (empty) or weekend -> grey
        $cellStyle = 'background-color: #c3c2c2;';
    }

    $driverOnLeave = $cellData['driver_on_leave'] ?? false;
    $driverOverridden = $cellData['driver_overridden'] ?? false;
    // Isolated day: truck fully unavailable (both MY & SG) the day before AND after.
    $isolatedDay = $cellData['isolated'] ?? false;
    // Free-text planner note attached to this availability cell.
    $remarks = trim((string) ($cellData['remarks'] ?? ''));
@endphp
<td class="p-2 availability-cell @if ($profile) arrangement-cell @endif @if ($driverOnLeave) border border-danger border-2 @endif"
    style="{{ $cellStyle }}"
    @if ($consignors->isNotEmpty() || ($status === 'available' && $hasRecord)) draggable="true" @endif
    data-truck="{{ $truckNumber }}" data-location="{{ $location }}" data-date="{{ $dateOnly }}"
    data-status="{{ $status }}"
    data-weekend="{{ $isWeekend ? 'true' : 'false' }}"
    data-flow-off="{{ $flowOff ? 'true' : 'false' }}"
    data-has-consignors="{{ $isAssigned ? 'true' : 'false' }}">
    @if ($isolatedDay)
        <div class="text-center" style="line-height: 1;"
            title="Isolated day — truck is unavailable both the day before and the day after">
            <i class="bi bi-x-circle-fill text-danger" style="font-size: 1.15rem;"></i>
        </div>
    @endif
    @if ($driverOverridden)
        <div class="text-end" style="line-height: 1;">
            <i class="bi bi-person-fill-gear text-secondary" style="font-size: 0.95rem;"></i>
        </div>
    @endif
    @if ($driverOnLeave)
        @php
            $leaveDriverName = $cellData['driver_name'] ?? 'Driver';
            $leaveTooltip = '<div class="fw-bold text-danger">'
                . e($leaveDriverName) . ' is on leave — please reassign</div>';
        @endphp
        <div class="text-center mb-1" data-bs-toggle="tooltip" data-bs-html="true"
            data-bs-placement="top" title="{{ $leaveTooltip }}">
            <span class="badge bg-danger d-block">On Leave</span>
        </div>
    @endif
    @if ($consignors->isNotEmpty())
        @php
            $count = $consignors->count();
            $tooltip = '';
            if ($count > 0) {
                $tooltip .= '<ul class="mb-0 ps-3">';
                foreach ($consignors as $c) {
                    $tooltip .= '<li>' . e($c) . '</li>';
                }
                $tooltip .= '</ul>';
            }
        @endphp
        <div class="mt-1 text-center consignor-info" data-bs-toggle="tooltip"
            data-bs-html="true" data-bs-placement="top" title="{{ $tooltip }}">
            <span class="badge bg-primary text-dark mb-1">
                {{ number_format($total_capacity - $used_capacity, 1) }}/{{ number_format($total_capacity, 1) }}
            </span>
            <br>
            <span class="badge bg-primary text-dark mb-0">
                {{ $count }} consignor{{ $count > 1 ? 's' : '' }}
            </span>
        </div>
    @endif
    @if ($profile)
        <div class="mt-1 text-center">
            <span class="badge mb-1" style="background-color: rgba(0,0,0,0.6); color:#fff;">
                {{ $profile['label'] }}
            </span>
        </div>
    @endif
    @if ($remarks !== '')
        <div class="mt-1 text-center consignor-info"
            title="{{ '<div class=&quot;fw-bold&quot;>Remark</div>' . e($remarks) }}">
            <span class="badge bg-dark text-white">
                <i class="bi bi-chat-left-text-fill me-1"></i>Note
            </span>
        </div>
    @endif
</td>
