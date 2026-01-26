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
            $table->dropUnique(['truck_no']); // remove unique constraint
        });
    }

    public function down(): void
    {
        Schema::table('subcons', function (Blueprint $table) {
            $table->unique('truck_no'); // restore unique constraint if rolled back
        });
    }
};
