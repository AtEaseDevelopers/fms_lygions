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
        Schema::table('customer_locations', function (Blueprint $table) {
            $table->string('load_type')->nullable();
        });

        Schema::table('draft_customer_locations', function (Blueprint $table) {
            $table->string('load_type')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('customer_locations', function (Blueprint $table) {
            $table->dropColumn('load_type');
        });

        Schema::table('draft_customer_locations', function (Blueprint $table) {
            $table->dropColumn('load_type');
        });
    }
};
