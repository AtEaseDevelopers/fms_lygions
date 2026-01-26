<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('consignments', function (Blueprint $table) {
            $table->text('pick_address')->nullable()->after('pick_point');
            $table->text('drop_address')->nullable()->after('drop_point');
        });
    }

    public function down()
    {
        Schema::table('consignments', function (Blueprint $table) {
            $table->dropColumn(['pick_address', 'drop_address']);
        });
    }
};
