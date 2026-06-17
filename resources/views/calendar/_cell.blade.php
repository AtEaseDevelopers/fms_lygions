@php
    $status = $cellData['status'] ?? 'empty';
    $used_capacity = $cellData['used_capacity'] ?? 0;
    $total_capacity = $cellData['total_capacity'] ?? 0;
    $consignors = $cellData['consignors'] ?? collect();

    $occupiedColor = $location === 'SG' ? '#d1ecf1' : '#f7c6c7';
    $cellStyle = $consignors->isNotEmpty()
        ? 'background-color: ' . $occupiedColor . ';'
        : match ($status) {
            'available' => 'background-color: #ffffff;',
            default => 'background-color: #c3c2c2;',
        };

    $driverOnLeave = $cellData['driver_on_leave'] ?? false;
    $driverOverridden = $cellData['driver_overridden'] ?? false;
@endphp
<td class="p-2 availability-cell @if ($driverOnLeave) border border-danger border-2 @endif"
    style="{{ $cellStyle }}"
    @if ($consignors->isNotEmpty() || $status === 'available') draggable="true" @endif
    data-truck="{{ $truckNumber }}" data-location="{{ $location }}" data-date="{{ $dateOnly }}"
    data-status="{{ $status }}"
    data-has-consignors="{{ $consignors->isNotEmpty() ? 'true' : 'false' }}">
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
    @if (in_array($status, ['off-day', 'maintenance']))
        <div class="mt-1 text-center">
            <span
                class="badge
                    @if ($status === 'off-day') bg-secondary
                    @else bg-warning text-dark @endif mb-1">
                {{ ucfirst($status) }}
            </span>
        </div>
    @endif
</td>
