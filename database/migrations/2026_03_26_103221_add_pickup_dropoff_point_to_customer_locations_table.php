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
        Schema::connection('snl')->table('customer_locations', function (Blueprint $table) {
            $table->string('pickup_dropoff_point')->nullable();
        });

        Schema::table('draft_customer_locations', function (Blueprint $table) {
            $table->string('pickup_dropoff_point')->nullable();
        });
    }

    public function down(): void
    {
        Schema::connection('snl')->table('customer_locations', function (Blueprint $table) {
            $table->dropColumn('pickup_dropoff_point');
        });

        Schema::table('draft_customer_locations', function (Blueprint $table) {
            $table->dropColumn('pickup_dropoff_point');
        });
    }
};
