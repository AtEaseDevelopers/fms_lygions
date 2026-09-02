<?php

namespace Tests\Feature;

use App\Http\Controllers\CalendarController;
use App\Models\Availability;
use App\Models\Consignment;
use App\Models\Truck;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Planners can build/adjust the truck capacity calendar directly on the grid:
 *   - click a grey (empty) box to add a single-day availability, and
 *   - drag a white availability box vertically to move/swap MY <-> SG for the
 *     same truck on the same date.
 *
 * The Truck model lives on the separate `snl` connection, pointed here at its own
 * in-memory sqlite so the test never touches the real database.
 */
class CalendarCellEditTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::parse('2026-09-02 09:00:00'));

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
            $table->string('pick_point')->nullable();
            $table->string('quantity')->nullable();
            $table->string('unit')->nullable();
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

    // --- Grey box -> add availability -------------------------------------

    public function test_clicking_a_grey_box_adds_a_single_day_availability(): void
    {
        $truck = Truck::create(['number' => 'T-1', 'floor_space' => 100, 'is_outsider' => false]);

        $res = (new CalendarController)->addAvailability(Request::create('/calendar/add-availability', 'POST', [
            'truck' => 'T-1',
            'date' => '2026-09-02',
            'location' => 'SG',
        ]));

        $this->assertSame(200, $res->getStatusCode());
        $row = Availability::first();
        $this->assertNotNull($row);
        $this->assertSame($truck->id, $row->truck_id);
        $this->assertSame('SG', $row->location);
        $this->assertSame('available', $row->status);
        $this->assertSame('2026-09-02', Carbon::parse($row->date)->format('Y-m-d'));
    }

    public function test_adding_availability_is_rejected_when_the_cell_already_has_one(): void
    {
        $truck = Truck::create(['number' => 'T-1', 'floor_space' => 100, 'is_outsider' => false]);
        Availability::create([
            'date' => '2026-09-02', 'truck_id' => $truck->id, 'location' => 'MY', 'status' => 'available',
        ]);

        $res = (new CalendarController)->addAvailability(Request::create('/calendar/add-availability', 'POST', [
            'truck' => 'T-1',
            'date' => '2026-09-02',
            'location' => 'MY',
        ]));

        $this->assertSame(422, $res->getStatusCode());
        $this->assertSame(1, Availability::count());
    }

    // --- Vertical drag -> move/swap MY <-> SG -----------------------------

    public function test_vertical_drag_moves_availability_to_the_empty_other_side(): void
    {
        $truck = Truck::create(['number' => 'T-1', 'floor_space' => 100, 'is_outsider' => false]);
        $my = Availability::create([
            'date' => '2026-09-02', 'truck_id' => $truck->id, 'location' => 'MY', 'status' => 'available',
        ]);

        $res = (new CalendarController)->moveAvailability(Request::create('/calendar/move-availability', 'POST', [
            'source_truck' => 'T-1', 'source_date' => '2026-09-02', 'source_location' => 'MY',
            'target_truck' => 'T-1', 'target_date' => '2026-09-02', 'target_location' => 'SG',
        ]));

        $this->assertSame(200, $res->getStatusCode());
        $this->assertSame('SG', $my->fresh()->location);
        $this->assertSame(1, Availability::count());
    }

    public function test_vertical_drag_swaps_when_both_sides_have_availability(): void
    {
        $truck = Truck::create(['number' => 'T-1', 'floor_space' => 100, 'is_outsider' => false]);
        $my = Availability::create([
            'date' => '2026-09-02', 'truck_id' => $truck->id, 'location' => 'MY', 'status' => 'available',
        ]);
        $sg = Availability::create([
            'date' => '2026-09-02', 'truck_id' => $truck->id, 'location' => 'SG', 'status' => 'available',
        ]);

        $res = (new CalendarController)->moveAvailability(Request::create('/calendar/move-availability', 'POST', [
            'source_truck' => 'T-1', 'source_date' => '2026-09-02', 'source_location' => 'MY',
            'target_truck' => 'T-1', 'target_date' => '2026-09-02', 'target_location' => 'SG',
        ]));

        $this->assertSame(200, $res->getStatusCode());
        $this->assertSame('SG', $my->fresh()->location);
        $this->assertSame('MY', $sg->fresh()->location);
    }

    public function test_vertical_drag_is_blocked_when_the_other_side_has_consignments(): void
    {
        $truck = Truck::create(['number' => 'T-1', 'floor_space' => 100, 'is_outsider' => false]);
        $my = Availability::create([
            'date' => '2026-09-02', 'truck_id' => $truck->id, 'location' => 'MY', 'status' => 'available',
        ]);
        Consignment::create([
            'load_date' => '2026-09-02', 'truck_number' => 'T-1', 'pick_point' => 'Singapore',
            'quantity' => '[1]', 'unit' => '["FT"]',
        ]);

        $res = (new CalendarController)->moveAvailability(Request::create('/calendar/move-availability', 'POST', [
            'source_truck' => 'T-1', 'source_date' => '2026-09-02', 'source_location' => 'MY',
            'target_truck' => 'T-1', 'target_date' => '2026-09-02', 'target_location' => 'SG',
        ]));

        $this->assertSame(422, $res->getStatusCode());
        $this->assertSame('MY', $my->fresh()->location);
    }

    public function test_horizontal_drag_still_moves_availability_to_another_date(): void
    {
        $truck = Truck::create(['number' => 'T-1', 'floor_space' => 100, 'is_outsider' => false]);
        $my = Availability::create([
            'date' => '2026-09-02', 'truck_id' => $truck->id, 'location' => 'MY', 'status' => 'available',
        ]);

        $res = (new CalendarController)->moveAvailability(Request::create('/calendar/move-availability', 'POST', [
            'source_truck' => 'T-1', 'source_date' => '2026-09-02', 'source_location' => 'MY',
            'target_truck' => 'T-1', 'target_date' => '2026-09-03', 'target_location' => 'MY',
        ]));

        $this->assertSame(200, $res->getStatusCode());
        $this->assertSame('2026-09-03', Carbon::parse($my->fresh()->date)->format('Y-m-d'));
    }
}
