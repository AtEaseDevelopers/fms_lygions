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
        Schema::table('draft_customer_locations', function (Blueprint $table) {
            $table->dropColumn('default_truck_type');
            $table->string('truck_size')->nullable();
            $table->string('truck_type')->nullable();
        });

        Schema::table('customer_locations', function (Blueprint $table) {
            $table->dropColumn('default_truck_type');
            $table->string('truck_size')->nullable();
            $table->string('truck_type')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('draft_customer_locations', function (Blueprint $table) {
            $table->dropColumn(['truck_size', 'truck_type']);
            $table->string('default_truck_type')->nullable();
        });

        Schema::table('customer_locations', function (Blueprint $table) {
            $table->dropColumn(['truck_size', 'truck_type']);
            $table->string('default_truck_type')->nullable();
        });
    }
};
