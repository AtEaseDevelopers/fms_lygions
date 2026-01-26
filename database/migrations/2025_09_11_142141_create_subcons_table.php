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
        Schema::create('subcons', function (Blueprint $table) {
            $table->id();
            $table->string('subcon_name');
            $table->string('truck_no')->unique();
            $table->string('driver_name');
            $table->string('truck_size');
            $table->string('phone_my')->nullable();
            $table->string('phone_sg')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subcons');
    }
};
