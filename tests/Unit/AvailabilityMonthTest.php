<?php

namespace Tests\Unit;

use App\Support\AvailabilityMonth;
use PHPUnit\Framework\TestCase;

class AvailabilityMonthTest extends TestCase
{
    public function test_it_skips_weekends(): void
    {
        // Aug 2026: 1st & 2nd are Sat/Sun; 8th, 9th, 15th, 16th, 22nd, 23rd, 29th, 30th are weekends.
        $schedule = AvailabilityMonth::weekdaySchedule('2026-08', 'MY');
        $dates = array_column($schedule, 'date');

        // 21 weekdays in August 2026.
        $this->assertCount(21, $schedule);
        $this->assertNotContains('2026-08-01', $dates); // Sat
        $this->assertNotContains('2026-08-02', $dates); // Sun
        $this->assertContains('2026-08-03', $dates);    // Mon
    }

    public function test_it_alternates_location_across_working_days(): void
    {
        $schedule = AvailabilityMonth::weekdaySchedule('2026-08', 'MY');

        // First weekday is Mon Aug 3 -> MY, then Tue -> SG, Wed -> MY, ...
        $this->assertSame(['date' => '2026-08-03', 'location' => 'MY'], $schedule[0]);
        $this->assertSame(['date' => '2026-08-04', 'location' => 'SG'], $schedule[1]);
        $this->assertSame(['date' => '2026-08-05', 'location' => 'MY'], $schedule[2]);

        // Weekend gap does not reset alternation: Fri Aug 7 = MY (idx 4), next Mon Aug 10 = SG.
        $this->assertSame(['date' => '2026-08-07', 'location' => 'MY'], $schedule[4]);
        $this->assertSame(['date' => '2026-08-10', 'location' => 'SG'], $schedule[5]);
    }

    public function test_first_location_sg_flips_the_pattern(): void
    {
        $schedule = AvailabilityMonth::weekdaySchedule('2026-08', 'SG');

        $this->assertSame('SG', $schedule[0]['location']);
        $this->assertSame('MY', $schedule[1]['location']);
    }
}
