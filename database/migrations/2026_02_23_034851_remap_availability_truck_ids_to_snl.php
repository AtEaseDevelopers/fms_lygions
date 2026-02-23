<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Remap availability truck_id from local trucks.id to snl lorries.id
        // using the lygions_id field which stores 'lygion_{snl_lorry_id}'
        $localDb = config('database.connections.mysql.database');
        $snlDb = config('database.connections.snl.database');

        DB::statement("
            UPDATE {$localDb}.availabilities a
            JOIN {$localDb}.trucks t ON a.truck_id = t.id
            JOIN {$snlDb}.lorries l ON CONCAT('lygion_', l.id) = t.lygions_id
            SET a.truck_id = l.id
        ");
    }

    public function down(): void
    {
        // This migration is not easily reversible
    }
};
