<?php

namespace Tests\Feature;

use App\Http\Controllers\CalendarController;
use Tests\TestCase;

/**
 * The truck calendar's top row shows how many lorries are available on each date.
 * A lorry counts as available for a date when it has a positive availability record
 * (available, express, saturday-loading/unloading) and no blocking record (off-day,
 * maintenance, holiday, breakdown, inspection). This mirrors the "available trucks"
 * definition used by ConsignmentController::getAvailableTrucks. These tests exercise
 * the pure counting logic, independent of the database.
 */
class AvailableTruckCountTest extends TestCase
{
    public function test_it_counts_a_truck_with_a_positive_record(): void
    {
        $count = CalendarController::availableTruckCount([
            ['truck_id' => 1, 'status' => 'available'],
        ]);

        $this->assertSame(1, $count);
    }

    public function test_it_excludes_trucks_with_a_blocking_record(): void
    {
        foreach (['off-day', 'maintenance', 'holiday', 'breakdown', 'inspection'] as $status) {
            $count = CalendarController::availableTruckCount([
                ['truck_id' => 1, 'status' => $status],
            ]);
            $this->assertSame(0, $count, "$status should not count as available");
        }
    }

    public function test_a_blocking_record_wins_over_a_positive_one_for_the_same_truck(): void
    {
        // Same truck available on MY but off-day on SG for the same date -> not available.
        $count = CalendarController::availableTruckCount([
            ['truck_id' => 1, 'status' => 'available'],
            ['truck_id' => 1, 'status' => 'off-day'],
        ]);

        $this->assertSame(0, $count);
    }

    public function test_it_counts_each_truck_once_across_both_sides(): void
    {
        // One truck with MY + SG available records still counts as a single lorry.
        $count = CalendarController::availableTruckCount([
            ['truck_id' => 1, 'status' => 'available'],
            ['truck_id' => 1, 'status' => 'available'],
            ['truck_id' => 2, 'status' => 'express'],
        ]);

        $this->assertSame(2, $count);
    }

    public function test_it_returns_zero_for_no_records(): void
    {
        $this->assertSame(0, CalendarController::availableTruckCount([]));
    }
}
