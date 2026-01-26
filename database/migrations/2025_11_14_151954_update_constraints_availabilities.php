<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('availabilities', function (Blueprint $table) {
            // Drop foreign key on truck_id
            $table->dropForeign(['truck_id']);

            // Make truck_id nullable
            $table->unsignedBigInteger('truck_id')->nullable()->change();

            // Optional: add subcon_id column
            if (!Schema::hasColumn('availabilities', 'subcon_id')) {
                $table->unsignedBigInteger('subcon_id')->nullable()->after('truck_id');
            }

            // Optional: you can add foreign key to subcon_id if desired
            // $table->foreign('subcon_id')->references('id')->on('subcons')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('availabilities', function (Blueprint $table) {
            // Make truck_id not nullable
            $table->unsignedBigInteger('truck_id')->nullable(false)->change();

            // Restore foreign key
            $table->foreign('truck_id')->references('id')->on('trucks')->onDelete('cascade');

            // Drop subcon_id column if exists
            if (Schema::hasColumn('availabilities', 'subcon_id')) {
                $table->dropColumn('subcon_id');
            }
        });
    }
};
