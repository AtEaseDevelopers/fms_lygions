<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
{
    Schema::table('customer_locations', function (Blueprint $table) {
        $table->string('default_truck_type')->nullable()->after('type');
    });

    Schema::table('draft_customer_locations', function (Blueprint $table) {
        $table->string('default_truck_type')->nullable()->after('type');
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
       
    }
};
