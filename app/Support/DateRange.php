<?php

namespace App\Support;

use Carbon\Carbon;

/**
 * Turns a date-range string into the list of individual days it covers.
 *
 * Accepts either a single date ("2026-08-10") or a range produced by the
 * calendar's daterangepicker ("2026-08-10 to 2026-08-13"). Used by special
 * arrangements so a driver can be marked off for several days at once.
 */
class DateRange
{
    /**
     * Expand a single date or "start to end" range into inclusive Y-m-d days.
     *
     * @return string[] e.g. ['2026-08-10', '2026-08-11', '2026-08-12']
     */
    public static function expand(?string $input): array
    {
        $input = trim((string) $input);
        if ($input === '') {
            return [];
        }

        $parts = array_map('trim', explode(' to ', $input));
        $start = Carbon::parse($parts[0])->startOfDay();
        $end = Carbon::parse($parts[count($parts) - 1])->startOfDay();

        // Guard against a reversed range so the loop below always terminates.
        if ($end->lt($start)) {
            [$start, $end] = [$end, $start];
        }

        $dates = [];
        for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
            $dates[] = $d->format('Y-m-d');
        }

        return $dates;
    }
}
