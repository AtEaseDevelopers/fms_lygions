<?php

namespace Tests\Feature;

use App\Http\Controllers\CalendarController;
use Tests\TestCase;

/**
 * The truck calendar marks a day with a cross icon when the truck is FULLY
 * unavailable (neither MY nor SG usable) on BOTH the day before and the day
 * after — an "isolated" day stranded between two off days. These tests exercise
 * the pure decision logic, independent of the database.
 */
class IsolatedDayTest extends TestCase
{
    private function statuses(array $rows): array
    {
        // $rows: ['Y-m-d' => [my, sg]] -> ['Y-m-d' => ['MY' => my, 'SG' => sg]]
        return array_map(fn ($r) => ['MY' => $r[0], 'SG' => $r[1]], $rows);
    }

    public function test_a_side_is_only_available_for_positive_statuses(): void
    {
        foreach (['available', 'occupied', 'express', 'saturday-loading', 'saturday-unloading'] as $s) {
            $this->assertTrue(CalendarController::sideStatusAvailable($s), "$s should be available");
        }
        foreach (['empty', 'off-day', 'maintenance', 'holiday', 'breakdown', 'inspection', null] as $s) {
            $this->assertFalse(CalendarController::sideStatusAvailable($s), var_export($s, true) . ' should be unavailable');
        }
    }

    public function test_it_flags_a_both_sides_available_day_stranded_between_two_fully_off_days(): void
    {
        $dates = ['2026-08-15', '2026-08-16', '2026-08-17', '2026-08-18', '2026-08-19'];
        $statuses = $this->statuses([
            '2026-08-15' => ['available', 'empty'],       // usable (one side)
            '2026-08-16' => ['empty', 'empty'],           // fully off (day before 17)
            '2026-08-17' => ['available', 'available'],   // MY + SG available -> isolated
            '2026-08-18' => ['empty', 'off-day'],         // fully off (day after 17)
            '2026-08-19' => ['available', 'available'],   // not stranded (18 is off, 20 missing)
        ]);

        $this->assertSame(['2026-08-17'], CalendarController::isolatedDayDates($dates, $statuses));
    }

    public function test_it_does_not_flag_when_only_one_side_is_available_on_the_middle_day(): void
    {
        $dates = ['2026-08-16', '2026-08-17', '2026-08-18'];
        $statuses = $this->statuses([
            '2026-08-16' => ['empty', 'empty'],           // fully off
            '2026-08-17' => ['available', 'empty'],       // only MY available -> no cross
            '2026-08-18' => ['empty', 'empty'],           // fully off
        ]);

        $this->assertSame([], CalendarController::isolatedDayDates($dates, $statuses));
    }

    public function test_it_does_not_flag_when_a_neighbour_is_still_usable(): void
    {
        $dates = ['2026-08-16', '2026-08-17', '2026-08-18'];
        $statuses = $this->statuses([
            '2026-08-16' => ['empty', 'empty'],           // fully off
            '2026-08-17' => ['available', 'available'],
            '2026-08-18' => ['available', 'empty'],       // still usable -> no cross
        ]);

        $this->assertSame([], CalendarController::isolatedDayDates($dates, $statuses));
    }

    public function test_edge_days_are_never_flagged(): void
    {
        // First/last day have only one neighbour, so they can never be isolated.
        $dates = ['2026-08-16', '2026-08-17'];
        $statuses = $this->statuses([
            '2026-08-16' => ['empty', 'empty'],
            '2026-08-17' => ['empty', 'empty'],
        ]);

        $this->assertSame([], CalendarController::isolatedDayDates($dates, $statuses));
    }

    public function test_a_fully_off_middle_day_in_a_run_is_not_flagged(): void
    {
        // A stretch of off/empty days must NOT flag every inner day — only a usable
        // day stranded between off days gets the cross.
        $dates = ['2026-08-20', '2026-08-21', '2026-08-22'];
        $statuses = $this->statuses([
            '2026-08-20' => ['off-day', 'empty'],
            '2026-08-21' => ['empty', 'empty'],   // itself off -> no cross
            '2026-08-22' => ['empty', 'empty'],
        ]);

        $this->assertSame([], CalendarController::isolatedDayDates($dates, $statuses));
    }

    public function test_an_occupied_neighbour_counts_as_available(): void
    {
        $dates = ['2026-08-16', '2026-08-17', '2026-08-18'];
        $statuses = $this->statuses([
            '2026-08-16' => ['occupied', 'empty'],    // occupied = truck working = usable
            '2026-08-17' => ['empty', 'empty'],
            '2026-08-18' => ['empty', 'empty'],
        ]);

        $this->assertSame([], CalendarController::isolatedDayDates($dates, $statuses));
    }
}
