<?php

namespace Tests\Feature;

use App\Http\Controllers\CalendarController;
use App\Models\Availability;
use App\Models\Truck;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Planners can attach a free-text remark to any available truck cell on the truck
 * capacity calendar. The remark is stored on that cell's Availability record and
 * targets the exact MY/SG record for the truck+date, never the opposite side.
 *
 * The Truck model is pinned to the separate `snl` connection, so we point that
 * connection at its own in-memory sqlite (never the real database) to keep the
 * test hermetic.
 */
class AvailabilityRemarksTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::parse('2026-08-17 09:00:00'));

        config(['database.connections.snl' => [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => false,
        ]]);
        DB::purge('snl');

        Schema::connection('snl')->create('lorries', function (Blueprint $table) {
            $table->id();
            $table->string('number')->nullable();
            $table->decimal('floor_space', 8, 2)->default(0);
            $table->boolean('is_outsider')->default(false);
            $table->timestamps();
        });

        Schema::create('availabilities', function (Blueprint $table) {
            $table->id();
            $table->date('date')->nullable();
            $table->unsignedBigInteger('truck_id')->nullable();
            $table->unsignedBigInteger('subcon_id')->nullable();
            $table->string('location')->nullable();
            $table->string('status')->nullable();
            $table->string('remarks', 500)->nullable();
            $table->timestamps();
        });

        Schema::create('consignments', function (Blueprint $table) {
            $table->id();
            $table->string('load_date')->nullable();
            $table->string('truck_number')->nullable();
            $table->string('driver')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        Schema::dropIfExists('consignments');
        Schema::dropIfExists('availabilities');
        Schema::connection('snl')->dropIfExists('lorries');
        DB::purge('snl');
        parent::tearDown();
    }

    public function test_it_saves_a_remark_on_the_availability_record(): void
    {
        $truck = Truck::create(['number' => 'T-1', 'floor_space' => 100, 'is_outsider' => false]);
        Availability::create([
            'date' => '2026-08-20', 'truck_id' => $truck->id, 'location' => 'MY', 'status' => 'available',
        ]);

        (new CalendarController)->updateStatus(Request::create('/availability/update', 'POST', [
            'truck' => 'T-1',
            'date' => '2026-08-20',
            'location' => 'MY',
            'status' => 'available',
            'remarks' => 'Leaves late — driver swap',
        ]));

        $this->assertSame('Leaves late — driver swap', Availability::first()->remarks);
    }

    public function test_a_remark_only_touches_the_matching_location_record(): void
    {
        $truck = Truck::create(['number' => 'T-1', 'floor_space' => 100, 'is_outsider' => false]);
        $my = Availability::create([
            'date' => '2026-08-20', 'truck_id' => $truck->id, 'location' => 'MY', 'status' => 'available',
        ]);
        $sg = Availability::create([
            'date' => '2026-08-20', 'truck_id' => $truck->id, 'location' => 'SG', 'status' => 'available',
        ]);

        (new CalendarController)->updateStatus(Request::create('/availability/update', 'POST', [
            'truck' => 'T-1',
            'date' => '2026-08-20',
            'location' => 'SG',
            'remarks' => 'SG only note',
        ]));

        $this->assertNull($my->fresh()->remarks);
        $this->assertSame('SG only note', $sg->fresh()->remarks);
    }

    public function test_an_empty_remark_clears_an_existing_one(): void
    {
        $truck = Truck::create(['number' => 'T-1', 'floor_space' => 100, 'is_outsider' => false]);
        Availability::create([
            'date' => '2026-08-20', 'truck_id' => $truck->id, 'location' => 'MY',
            'status' => 'available', 'remarks' => 'old note',
        ]);

        (new CalendarController)->updateStatus(Request::create('/availability/update', 'POST', [
            'truck' => 'T-1',
            'date' => '2026-08-20',
            'location' => 'MY',
            'remarks' => '',
        ]));

        $this->assertNull(Availability::first()->remarks);
    }
}
