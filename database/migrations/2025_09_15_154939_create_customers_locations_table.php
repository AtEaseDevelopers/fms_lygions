<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::create('customer_locations', function (Blueprint $table) {
    $table->id();
    $table->foreignId('customer_id')->constrained()->onDelete('cascade');
    $table->string('state')->nullable();
    $table->string('address')->nullable();
    $table->string('pic')->nullable();
    $table->string('phone')->nullable();
    $table->string('type')->nullable();
    $table->timestamps();
});

    }

    public function down(): void {
        Schema::dropIfExists('customer_locations');
    }
};
