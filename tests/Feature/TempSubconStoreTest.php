<?php

namespace Tests\Feature;

use App\Http\Controllers\CalendarController;
use App\Models\TemporaryTruck;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Temporary subcons can be added on their own — the Subcon section carries its own
 * date range and location, so the left "Basic" fields (status / month / first
 * location) are no longer required just to reserve subcon capacity.
 *
 * Truck lives on the separate `snl` connection; point it at its own in-memory sqlite.
 */
class TempSubconStoreTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::parse('2026-09-02 09:00:00'));

        config(['database.connections.snl' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => false,
        ]]);
        DB::purge('snl');

        Schema::connection('snl')->create('lorries', function (Blueprint $t) {
            $t->id();
            $t->string('number')->nullable();
            $t->string('team')->nullable();
            $t->boolean('is_outsider')->default(false);
            $t->timestamps();
        });

        Schema::create('subcons', function (Blueprint $t) {
            $t->id();
            $t->string('truck_no')->nullable();
            $t->string('team')->nullable();
            $t->timestamps();
        });

        Schema::create('temporary_trucks', function (Blueprint $t) {
            $t->id();
            $t->date('date')->nullable();
            $t->string('location')->nullable();
            $t->string('chassis_type')->nullable();
            $t->string('size')->nullable();
            $t->string('label')->nullable();
            $t->unsignedBigInteger('subcon_id')->nullable();
            $t->decimal('floor_space', 8, 2)->nullable();
            $t->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        Schema::dropIfExists('temporary_trucks');
        Schema::dropIfExists('subcons');
        Schema::connection('snl')->dropIfExists('lorries');
        DB::purge('snl');
        parent::tearDown();
    }

    public function test_temp_subcons_save_without_the_basic_status_month_or_location(): void
    {
        // Only the Subcon section is filled — no status, no month, no left location.
        (new CalendarController)->store(Request::create('/calendar', 'POST', [
            'temp_location' => 'SG',
            'date_range' => '2026-09-10 to 2026-09-11',
            'temp_chassis_type' => ['open'],
            'temp_size' => ['Small'],
            'temp_qty' => [1],
            'labels' => [['X1']],
            'floor_space' => [['12.5']],
        ]));

        // Two dates × one label.
        $this->assertSame(2, TemporaryTruck::count());
        $rows = TemporaryTruck::orderBy('date')->get();
        $this->assertSame('SG', $rows[0]->location);
        $this->assertSame('X1', $rows[0]->label);
        $this->assertSame('open', $rows[0]->chassis_type);
        $this->assertSame('Small', $rows[0]->size);
        $this->assertSame('12.50', (string) $rows[0]->floor_space);
        $this->assertSame('2026-09-10', $rows[0]->date->format('Y-m-d'));
        $this->assertSame('2026-09-11', $rows[1]->date->format('Y-m-d'));
    }

    public function test_temp_location_falls_back_to_the_basic_location_when_omitted(): void
    {
        (new CalendarController)->store(Request::create('/calendar', 'POST', [
            'location' => 'MY', // legacy path: only the left location supplied
            'date_range' => '2026-09-10 to 2026-09-10',
            'temp_chassis_type' => ['box'],
            'temp_size' => ['Any'],
            'temp_qty' => [1],
            'labels' => [['X9']],
            'floor_space' => [['']],
        ]));

        $this->assertSame(1, TemporaryTruck::count());
        $this->assertSame('MY', TemporaryTruck::first()->location);
    }
}
