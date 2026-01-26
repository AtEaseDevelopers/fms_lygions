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
        Schema::table('consignments', function (Blueprint $table) {
            $table->json('quantity')->nullable()->change();
            $table->json('unit')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('consignments', function (Blueprint $table) {
            $table->string('quantity')->nullable()->change();
            $table->string('unit')->nullable()->change();
        });
    }
};
