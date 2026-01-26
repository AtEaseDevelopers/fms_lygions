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
         Schema::create('availabilities', function (Blueprint $table) {
        $table->id();
        $table->foreignId('truck_id')->constrained('trucks')->onDelete('cascade');
        $table->date('date');
        $table->enum('location', ['KL', 'SG']);
        $table->enum('status', ['available', 'off-day', 'occupied', 'maintenance']);
        $table->timestamps();
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('availabilities');
    }
};
