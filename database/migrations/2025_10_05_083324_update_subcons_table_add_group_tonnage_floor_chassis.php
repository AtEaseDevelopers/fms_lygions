<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
     public function up(): void
    {
        Schema::table('subcons', function (Blueprint $table) {
            // Remove old column
            $table->dropColumn('truck_size');

            // Add new columns
            $table->string('group')->after('driver_name');
            $table->decimal('tonnage', 8, 2)->after('group');
            $table->decimal('floor_space', 8, 2)->after('tonnage');
            $table->enum('chassis_type', ['A', 'B'])->after('floor_space');
        });
    }

    public function down(): void
    {
        Schema::table('subcons', function (Blueprint $table) {
            // Rollback: drop new and restore old
            $table->dropColumn(['group', 'tonnage', 'floor_space', 'chassis_type']);
            $table->string('truck_size')->nullable();
        });
    }
};
