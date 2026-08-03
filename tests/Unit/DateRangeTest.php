<?php

namespace Tests\Unit;

use App\Support\DateRange;
use PHPUnit\Framework\TestCase;

class DateRangeTest extends TestCase
{
    public function test_empty_input_returns_no_dates(): void
    {
        $this->assertSame([], DateRange::expand(''));
        $this->assertSame([], DateRange::expand(null));
        $this->assertSame([], DateRange::expand('   '));
    }

    public function test_single_date_returns_one_day(): void
    {
        $this->assertSame(['2026-08-10'], DateRange::expand('2026-08-10'));
    }

    public function test_range_expands_to_inclusive_days(): void
    {
        // Driver on leave for 4 days straight.
        $this->assertSame(
            ['2026-08-10', '2026-08-11', '2026-08-12', '2026-08-13'],
            DateRange::expand('2026-08-10 to 2026-08-13')
        );
    }

    public function test_reversed_range_is_normalised(): void
    {
        $this->assertSame(
            ['2026-08-10', '2026-08-11', '2026-08-12'],
            DateRange::expand('2026-08-12 to 2026-08-10')
        );
    }
}
