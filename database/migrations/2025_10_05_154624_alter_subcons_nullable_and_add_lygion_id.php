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
            // Make existing columns nullable
            $table->string('subcon_name')->nullable()->change();
            $table->string('truck_no')->nullable()->change();
            $table->string('driver_name')->nullable()->change();
            $table->string('group')->nullable()->change();
            $table->decimal('tonnage', 8, 2)->nullable()->change();
            $table->decimal('floor_space', 8, 2)->nullable()->change();
            $table->string('chassis_type')->nullable()->change();
            $table->string('phone_my')->nullable()->change();
            $table->string('phone_sg')->nullable()->change();

            // Add lygion_id column
            $table->unsignedBigInteger('lygion_id')->nullable()->after('id');

            // Optional: Add unique index to prevent duplicate syncs
            $table->unique(['truck_no', 'lygion_id']);
        });
    }

    public function down(): void
    {
        Schema::table('subcons', function (Blueprint $table) {
            $table->string('subcon_name')->nullable(false)->change();
            $table->string('truck_no')->nullable(false)->change();
            $table->string('driver_name')->nullable(false)->change();
            $table->string('group')->nullable(false)->change();
            $table->decimal('tonnage', 8, 2)->nullable(false)->change();
            $table->decimal('floor_space', 8, 2)->nullable(false)->change();
            $table->string('chassis_type')->nullable(false)->change();
            $table->string('phone_my')->nullable(false)->change();
            $table->string('phone_sg')->nullable(false)->change();

            $table->dropColumn('lygion_id');
            $table->dropUnique(['truck_no', 'lygion_id']);
        });
    }
};
