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
        Schema::table('availabilities', function (Blueprint $table) {
            $table->enum('location', ['KL', 'MY', 'SG'])
                ->collation('utf8mb4_unicode_ci')
                ->default('KL')
                ->change();
        });

        DB::table('availabilities')->where('location', 'KL')->update(['location' => 'MY']);

        Schema::table('availabilities', function (Blueprint $table) {
            $table->enum('location', ['MY', 'SG'])
                ->collation('utf8mb4_unicode_ci')
                ->default('MY')
                ->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
            Schema::table('availabilities', function (Blueprint $table) {
            $table->enum('location', ['KL', 'MY', 'SG'])
                ->collation('utf8mb4_unicode_ci')
                ->default('MY')
                ->change();
        });

        DB::table('availabilities')->where('location', 'MY')->update(['location' => 'KL']);

        Schema::table('availabilities', function (Blueprint $table) {
            $table->enum('location', ['KL', 'SG'])
                ->collation('utf8mb4_unicode_ci')
                ->default('KL')
                ->change();
        });

    }
};
