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
            // Remove unique constraint from number
            $table->dropUnique(['number']);

            // Add lygions_id column
            $table->unsignedBigInteger('lygions_id')->nullable()->after('id')->unique();

            // Make other fields nullable
            $table->string('group')->nullable()->change();
            $table->decimal('tonnage', 8, 2)->nullable()->change();
            $table->decimal('floor_space', 8, 2)->nullable()->change();
            $table->string('chassis_type')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('trucks', function (Blueprint $table) {
            $table->unique('number');
            $table->dropColumn('lygions_id');
            $table->string('group')->nullable(false)->change();
            $table->decimal('tonnage', 8, 2)->nullable(false)->change();
            $table->decimal('floor_space', 8, 2)->nullable(false)->change();
            $table->string('chassis_type')->nullable(false)->change();
        });
    }
};
