<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('trucks', function (Blueprint $table) {
            $table->id();
            $table->string('number')->unique();
            $table->string('group');
            $table->string('tonnage');
            $table->string('floor_space');
            $table->string('chassis_type');
            $table->date('next_inspection')->nullable();
            $table->date('next_tyre')->nullable();
            $table->date('next_permit')->nullable();
            $table->date('next_extinguisher')->nullable();
            $table->date('next_roadtax')->nullable();
            $table->date('next_insurance')->nullable();
            $table->date('next_others')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('trucks');
    }
};
