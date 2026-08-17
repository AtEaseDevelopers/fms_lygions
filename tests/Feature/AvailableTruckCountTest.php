<?php

namespace Tests\Feature;

use App\Http\Controllers\CalendarController;
use Tests\TestCase;

/**
 * The truck calendar's top row shows how many lorries are available on each date,
 * split by location (MY / SG). A lorry counts as available on a side when it has a
 * positive availability record for that location (available, express, saturday-
 * loading/unloading) and no blocking record (off-day, maintenance, holiday,
 * breakdown, inspection) on that same side. This mirrors the "available trucks"
 * definition used by ConsignmentController::getAvailableTrucks. These tests exercise
 * the pure counting logic, independent of the database.
 */
class AvailableTruckCountTest extends TestCase
{
    public function test_it_counts_a_truck_on_its_own_side(): void
    {
        $counts = CalendarController::availableTruckCountsByLocation([
            ['truck_id' => 1, 'status' => 'available', 'location' => 'MY'],
            ['truck_id' => 2, 'status' => 'available', 'location' => 'SG'],
        ]);

        $this->assertSame(['MY' => 1, 'SG' => 1], $counts);
    }

    public function test_it_excludes_a_blocking_record_on_that_side(): void
    {
        foreach (['off-day', 'maintenance', 'holiday', 'breakdown', 'inspection'] as $status) {
            $counts = CalendarController::availableTruckCountsByLocation([
                ['truck_id' => 1, 'status' => $status, 'location' => 'MY'],
            ]);
            $this->assertSame(['MY' => 0, 'SG' => 0], $counts, "$status should not count as available");
        }
    }

    public function test_a_side_is_counted_independently_of_the_other_side(): void
    {
        // Same truck: available on MY but off-day on SG -> counts for MY only.
        $counts = CalendarController::availableTruckCountsByLocation([
            ['truck_id' => 1, 'status' => 'available', 'location' => 'MY'],
            ['truck_id' => 1, 'status' => 'off-day', 'location' => 'SG'],
        ]);

        $this->assertSame(['MY' => 1, 'SG' => 0], $counts);
    }

    public function test_a_blocking_record_wins_over_a_positive_one_on_the_same_side(): void
    {
        $counts = CalendarController::availableTruckCountsByLocation([
            ['truck_id' => 1, 'status' => 'available', 'location' => 'MY'],
            ['truck_id' => 1, 'status' => 'off-day', 'location' => 'MY'],
        ]);

        $this->assertSame(['MY' => 0, 'SG' => 0], $counts);
    }

    public function test_it_counts_a_truck_once_per_side(): void
    {
        $counts = CalendarController::availableTruckCountsByLocation([
            ['truck_id' => 1, 'status' => 'available', 'location' => 'MY'],
            ['truck_id' => 1, 'status' => 'available', 'location' => 'MY'],
            ['truck_id' => 2, 'status' => 'express', 'location' => 'MY'],
        ]);

        $this->assertSame(['MY' => 2, 'SG' => 0], $counts);
    }

    public function test_it_returns_zeros_for_no_records(): void
    {
        $this->assertSame(['MY' => 0, 'SG' => 0], CalendarController::availableTruckCountsByLocation([]));
    }
}
