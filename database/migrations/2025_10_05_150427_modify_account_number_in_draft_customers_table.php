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
          Schema::table('draft_customers', function (Blueprint $table) {
            $table->string('account_number')->nullable()->change();
            $table->dropUnique(['account_number']);
        });

        // Customers
        Schema::table('customers', function (Blueprint $table) {
            $table->string('account_number')->nullable()->change();
            $table->dropUnique(['account_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('draft_customers', function (Blueprint $table) {
            $table->string('account_number')->unique()->change();
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->string('account_number')->unique()->change();
        });
    }
};
