<?php

namespace App\Support;

use Carbon\Carbon;

/**
 * Pure helper for turning a chosen month into the set of "available" cells to
 * create. Trucks are NOT available by default any more — availability is created
 * per-month and only on weekdays. The MY/SG side alternates across working days,
 * matching the old date-range flow behaviour.
 */
class AvailabilityMonth
{
    /**
     * Expand a "YYYY-MM" month into one entry per WEEKDAY (Mon–Fri), alternating
     * location starting from $firstLocation. Weekends are skipped entirely and do
     * not consume an alternation step.
     *
     * @return array<int, array{date: string, location: string}>
     */
    public static function weekdaySchedule(string $month, string $firstLocation): array
    {
        $first = strtoupper($firstLocation) === 'SG' ? 'SG' : 'MY';
        $second = $first === 'MY' ? 'SG' : 'MY';

        $start = Carbon::createFromFormat('Y-m-d', $month . '-01')->startOfMonth();
        $end = $start->copy()->endOfMonth();

        $schedule = [];
        $useFirst = true;
        for ($day = $start->copy(); $day->lte($end); $day->addDay()) {
            if ($day->isWeekend()) {
                continue;
            }
            $schedule[] = [
                'date' => $day->format('Y-m-d'),
                'location' => $useFirst ? $first : $second,
            ];
            $useFirst = !$useFirst;
        }

        return $schedule;
    }
}
