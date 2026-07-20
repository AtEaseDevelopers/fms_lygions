<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Widen availabilities.status from a fixed enum to VARCHAR(50) so it can hold the
 * new "special arrangement" types (holiday, breakdown, express, inspection,
 * saturday-loading, saturday-unloading) alongside the existing statuses.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE availabilities MODIFY status VARCHAR(50) NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE availabilities MODIFY status ENUM('available','off-day','occupied','maintenance') NOT NULL");
    }
};
