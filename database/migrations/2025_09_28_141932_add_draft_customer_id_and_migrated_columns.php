<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        // Add draft_customer_id to customers
        Schema::table('customers', function (Blueprint $table) {
            $table->unsignedBigInteger('draft_customer_id')->nullable()->after('id');

            // if you want a foreign key reference:
            $table->foreign('draft_customer_id')
                  ->references('id')
                  ->on('draft_customers')
                  ->onDelete('set null'); // or cascade, depending on your logic
        });

        // Add migrated to draft_customers
        Schema::table('draft_customers', function (Blueprint $table) {
            $table->boolean('migrated')->default(false)->after('id');
        });
    }

    public function down()
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropForeign(['draft_customer_id']);
            $table->dropColumn('draft_customer_id');
        });

        Schema::table('draft_customers', function (Blueprint $table) {
            $table->dropColumn('migrated');
        });
    }
};
