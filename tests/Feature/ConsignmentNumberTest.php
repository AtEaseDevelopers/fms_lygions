<?php

namespace Tests\Feature;

use App\Http\Controllers\ConsignmentController;
use App\Models\Consignment;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * The consignment-number generator must produce a unique number scoped to the
 * current day's "CSN. dm-" prefix, immune to unrelated rows created the same day
 * (the old bug derived the next index from the last row created today, ignoring
 * its prefix, which caused UniqueConstraintViolationException collisions).
 */
class ConsignmentNumberTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('consignments', function (Blueprint $table) {
            $table->id();
            $table->string('consignment_no')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        Schema::dropIfExists('consignments');
        parent::tearDown();
    }

    private function nextNumber(): string
    {
        // 3 Aug 2026 -> prefix "CSN. 0308-".
        return (new ConsignmentController)->nextConsignmentNumber(
            Carbon::parse('2026-08-03 10:00:00')
        );
    }

    public function test_it_starts_at_0001_when_no_orders_exist_for_today(): void
    {
        $this->assertSame('CSN. 0308-0001', $this->nextNumber());
    }

    public function test_it_takes_the_max_index_for_todays_prefix(): void
    {
        Consignment::create(['consignment_no' => 'CSN. 0308-0001']);
        Consignment::create(['consignment_no' => 'CSN. 0308-0005']); // out of order on purpose

        $this->assertSame('CSN. 0308-0006', $this->nextNumber());
    }

    public function test_it_ignores_rows_with_a_different_prefix(): void
    {
        // The exact shape that used to break: a row created today whose number
        // belongs to a different prefix / sequence must NOT drive today's index.
        Consignment::create(['consignment_no' => 'CSN. DUMMY-0001']);
        Consignment::create(['consignment_no' => 'CSN. 0208-0009']); // yesterday's prefix

        $this->assertSame('CSN. 0308-0001', $this->nextNumber());
    }
}
