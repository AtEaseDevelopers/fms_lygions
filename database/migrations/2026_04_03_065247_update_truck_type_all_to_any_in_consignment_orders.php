<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('consignments')
            ->where('pick_truck_type', 'all')
            ->update(['pick_truck_type' => 'any']);

        DB::table('consignments')
            ->where('drop_truck_type', 'all')
            ->update(['drop_truck_type' => 'any']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('consignments')
            ->where('pick_truck_type', 'any')
            ->update(['pick_truck_type' => 'all']);

        DB::table('consignments')
            ->where('drop_truck_type', 'any')
            ->update(['drop_truck_type' => 'all']);
    }
};
