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
         // Add to subcons table
        Schema::table('subcons', function (Blueprint $table) {
            $table->string('size')->nullable()->after('team');
        });

        // Add to trucks table
        Schema::table('trucks', function (Blueprint $table) {
            $table->string('size')->nullable()->after('team');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
       // Rollback both columns
        Schema::table('subcons', function (Blueprint $table) {
            $table->dropColumn('size');
        });

        Schema::table('trucks', function (Blueprint $table) {
            $table->dropColumn('size');
        });
    }
};
