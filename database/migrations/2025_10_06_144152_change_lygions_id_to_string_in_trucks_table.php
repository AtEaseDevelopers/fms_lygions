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
        Schema::table('trucks', function (Blueprint $table) {
            // Change to string to allow prefix like "lygion_408"
            $table->string('lygions_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('trucks', function (Blueprint $table) {
            // Revert back if needed
            $table->integer('lygions_id')->nullable()->change();
        });
    }
};
