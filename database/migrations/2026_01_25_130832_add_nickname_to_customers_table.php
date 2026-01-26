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
        // Add nickname to customers
        Schema::table('customers', function (Blueprint $table) {
            $table->string('nickname')->nullable()->after('name');
        });

        // Add nickname to draft_customers
        Schema::table('draft_customers', function (Blueprint $table) {
            $table->string('nickname')->nullable()->after('name');
        });
    }

    public function down(): void
    {
        // Remove nickname from customers
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('nickname');
        });

        // Remove nickname from draft_customers
        Schema::table('draft_customers', function (Blueprint $table) {
            $table->dropColumn('nickname');
        });
    }
};
