<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('temporary_trucks', function (Blueprint $table) {
            $table->id();
            $table->date('date')->index();
            $table->enum('location', ['MY', 'SG']);
            $table->string('chassis_type', 50);
            $table->string('size', 50);
            $table->string('label', 50);
            $table->decimal('floor_space', 10, 2)->nullable();
            $table->timestamps();

            $table->unique(['date', 'location', 'label'], 'temp_trucks_date_loc_label_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('temporary_trucks');
    }
};
