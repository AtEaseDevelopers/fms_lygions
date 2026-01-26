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
       Schema::create('consignments', function (Blueprint $table) {
            $table->id();
            $table->date('load_date');
            $table->string('consignment_no')->unique();
            $table->string('consignor');
            $table->string('pick_point');
            $table->string('consignee');
            $table->string('drop_point');
            $table->string('truck_type')->nullable();
            $table->string('truck_number')->nullable();
            $table->string('remarks')->nullable();
            $table->enum('status', ['Pending', 'Planning', 'Completed'])->default('Pending');
            $table->string('pick_truck')->nullable();
            $table->string('drop_truck')->nullable();
            $table->string('pick_time')->nullable();
            $table->integer('quantity')->nullable();
            $table->string('unit')->nullable();
            $table->string('pre_pick')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('consignments');
    }
};
