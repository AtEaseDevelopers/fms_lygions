<?php

namespace Tests\Feature;

use App\Http\Controllers\ConsignmentController;
use App\Models\Consignment;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Truck Planning quality-of-life: per-user listing preferences (column filters) are
 * saved server-side so they survive refresh / navigation / logout, and the truck
 * number can be reassigned inline via a lightweight endpoint that touches only that
 * one field (no "Edit All", no reset of filters).
 *
 * These exercise the endpoints directly with an injected user, avoiding the heavy
 * index() page build and its cross-connection dependencies.
 */
class ConsignmentPrefsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('email')->unique();
            $t->string('password')->nullable();
            $t->unsignedBigInteger('role_id')->nullable();
            $t->text('planning_prefs')->nullable();
            $t->timestamps();
        });

        Schema::create('consignments', function (Blueprint $t) {
            $t->id();
            $t->string('load_date')->nullable();
            $t->string('truck_number')->nullable();
            $t->string('status')->default('Pending');
            $t->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('consignments');
        Schema::dropIfExists('users');
        parent::tearDown();
    }

    private function makeUser(): User
    {
        return User::create(['name' => 'Planner', 'email' => 'p@t.test', 'password' => 'x', 'role_id' => 2]);
    }

    private function as(Request $request, User $user): Request
    {
        $request->setUserResolver(fn () => $user);
        return $request;
    }

    public function test_save_prefs_stores_column_filters_for_the_user(): void
    {
        $user = $this->makeUser();
        $request = Request::create('/consignment-order/save-prefs', 'POST', [
            'columnFilters' => ['15' => ['WLR 9811', 'BJH 9599']],
        ]);

        $res = (new ConsignmentController)->savePrefs($this->as($request, $user));

        $this->assertSame(200, $res->getStatusCode());
        $this->assertSame(['15' => ['WLR 9811', 'BJH 9599']], $user->fresh()->planning_prefs['columnFilters']);
    }

    public function test_save_prefs_replaces_column_filters_without_touching_saved_query(): void
    {
        $user = $this->makeUser();
        $user->planning_prefs = ['columnFilters' => ['3' => ['old']], 'query' => ['status' => 'planning']];
        $user->save();

        $request = Request::create('/x', 'POST', ['columnFilters' => ['18' => ['Completed']]]);
        (new ConsignmentController)->savePrefs($this->as($request, $user));

        $prefs = $user->fresh()->planning_prefs;
        $this->assertSame(['18' => ['Completed']], $prefs['columnFilters']);
        $this->assertSame(['status' => 'planning'], $prefs['query']); // server filters untouched
    }

    public function test_update_truck_number_sets_only_the_truck_and_strips_suffix(): void
    {
        $user = $this->makeUser();
        $c = Consignment::create(['load_date' => '2026-09-04', 'truck_number' => 'OLD 1', 'status' => 'Pending']);

        $request = Request::create("/x/{$c->id}/truck-number", 'PATCH', ['truck_number' => 'WLR 9811 (Subcon)']);
        $res = (new ConsignmentController)->updateTruckNumber($this->as($request, $user), $c->id);

        $this->assertSame(200, $res->getStatusCode());
        $this->assertSame('WLR 9811', $c->fresh()->truck_number);
        $this->assertSame('Pending', $c->fresh()->status); // nothing else changed
    }

    public function test_update_truck_number_can_clear_the_assignment(): void
    {
        $user = $this->makeUser();
        $c = Consignment::create(['load_date' => '2026-09-04', 'truck_number' => 'OLD 1', 'status' => 'Pending']);

        $request = Request::create("/x/{$c->id}/truck-number", 'PATCH', ['truck_number' => '']);
        (new ConsignmentController)->updateTruckNumber($this->as($request, $user), $c->id);

        $this->assertNull($c->fresh()->truck_number);
    }
}
