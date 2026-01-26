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
         Schema::table('draft_customer_locations', function (Blueprint $table) {
            // Drop old foreign key first
            $table->dropForeign(['customer_id']);

            // Rename column
            $table->renameColumn('customer_id', 'draft_customer_id');
        });

        Schema::table('draft_customer_locations', function (Blueprint $table) {
            // Add new foreign key
            $table->foreign('draft_customer_id')
                  ->references('id')
                  ->on('draft_customers')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
         Schema::table('draft_customer_locations', function (Blueprint $table) {
            $table->dropForeign(['draft_customer_id']);
            $table->renameColumn('draft_customer_id', 'customer_id');
            $table->foreign('customer_id')
                  ->references('id')
                  ->on('customers')
                  ->onDelete('cascade');
        });
    }
};
