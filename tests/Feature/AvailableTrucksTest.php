<?php

namespace Tests\Feature;

use App\Http\Controllers\ConsignmentController;
use App\Models\Availability;
use App\Models\Consignment;
use App\Models\Subcon;
use App\Models\TemporaryTruck;
use App\Models\Truck;
use App\Models\Unit;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * The inline lorry select on the Truck Planning page is populated from
 * /api/available-trucks. It must mirror the truck availability for the row's load
 * date exactly: only owned company trucks with an active availability record — no
 * subcons, no temporary trucks — and a full truck (no remaining floor space) stays
 * selectable so a lorry can still be assigned extra capacity (overloading).
 */
class AvailableTrucksTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::parse('2026-08-17 09:00:00'));

        Schema::create('trucks', function (Blueprint $table) {
            $table->id();
            $table->string('number')->nullable();
            $table->string('group')->nullable();
            $table->string('tonnage')->nullable();
            $table->decimal('floor_space', 8, 2)->default(0);
            $table->string('chassis_type')->nullable();
            $table->string('size')->nullable();
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
            $table->timestamps();
        });

        Schema::create('consignments', function (Blueprint $table) {
            $table->id();
            $table->string('load_date')->nullable();
            $table->string('truck_number')->nullable();
            $table->text('quantity')->nullable();
            $table->text('unit')->nullable();
            $table->timestamps();
        });

        Schema::create('units', function (Blueprint $table) {
            $table->id();
            $table->string('unit')->nullable();
            $table->decimal('space', 8, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('subcons', function (Blueprint $table) {
            $table->id();
            $table->string('truck_no')->nullable();
            $table->string('chassis_type')->nullable();
            $table->string('size')->nullable();
            $table->timestamps();
        });

        Schema::create('temporary_trucks', function (Blueprint $table) {
            $table->id();
            $table->date('date')->nullable();
            $table->string('location')->nullable();
            $table->string('chassis_type')->nullable();
            $table->string('size')->nullable();
            $table->string('label')->nullable();
            $table->unsignedBigInteger('subcon_id')->nullable();
            $table->decimal('floor_space', 8, 2)->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        foreach (['temporary_trucks', 'subcons', 'units', 'consignments', 'availabilities', 'trucks'] as $t) {
            Schema::dropIfExists($t);
        }
        parent::tearDown();
    }

    private function available(string $date): array
    {
        $response = (new ConsignmentController)->getAvailableTrucks(
            Request::create('/api/available-trucks', 'GET', ['date' => $date])
        );

        return $response->getData(true);
    }

    public function test_it_returns_only_owned_trucks_with_an_active_availability_record(): void
    {
        $on = Truck::create(['number' => 'ON-1', 'floor_space' => 100, 'is_outsider' => false]);
        $off = Truck::create(['number' => 'OFF-1', 'floor_space' => 100, 'is_outsider' => false]);
        $noRecord = Truck::create(['number' => 'NONE-1', 'floor_space' => 100, 'is_outsider' => false]);

        Availability::create(['date' => '2026-08-07', 'truck_id' => $on->id, 'status' => 'available']);
        Availability::create(['date' => '2026-08-07', 'truck_id' => $off->id, 'status' => 'off-day']);

        $data = $this->available('2026-08-07');
        $numbers = collect($data['trucks'])->pluck('number')->all();

        $this->assertContains('ON-1', $numbers);
        $this->assertNotContains('OFF-1', $numbers);       // blocked status
        $this->assertNotContains('NONE-1', $numbers);      // no record = not available by default
    }

    public function test_it_excludes_subcons_and_temporary_trucks(): void
    {
        $on = Truck::create(['number' => 'ON-1', 'floor_space' => 100, 'is_outsider' => false]);
        Availability::create(['date' => '2026-08-07', 'truck_id' => $on->id, 'status' => 'available']);

        $subcon = Subcon::create(['truck_no' => 'SUB-1']);
        Availability::create(['date' => '2026-08-07', 'subcon_id' => $subcon->id, 'status' => 'available']);
        TemporaryTruck::create(['date' => '2026-08-07', 'label' => 'TEMP-1']);

        $data = $this->available('2026-08-07');

        $this->assertCount(1, $data['trucks']);
        $this->assertSame('ON-1', $data['trucks'][0]['number']);
        $this->assertEmpty($data['subcons']);
        $this->assertEmpty($data['temp_trucks']);
    }

    public function test_a_full_truck_is_still_selectable_to_allow_overloading(): void
    {
        $truck = Truck::create(['number' => 'FULL-1', 'floor_space' => 10, 'is_outsider' => false]);
        Availability::create(['date' => '2026-08-07', 'truck_id' => $truck->id, 'status' => 'available']);

        // One "PLT" = 10 units of space; a single consignment already fills the truck.
        Unit::create(['unit' => 'PLT', 'space' => 10]);
        Consignment::create([
            'load_date' => '2026-08-07',
            'truck_number' => 'FULL-1',
            'quantity' => json_encode(['1']),
            'unit' => json_encode(['PLT']),
        ]);

        $data = $this->available('2026-08-07');
        $truckRow = collect($data['trucks'])->firstWhere('number', 'FULL-1');

        $this->assertNotNull($truckRow, 'A full truck must remain selectable for overloading');
        $this->assertSame(0.0, (float) $truckRow['remaining']);
    }
}
