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
        $table->boolean('express_mode')->default(false)->after('pre_pick');
    });
}

public function down(): void
{
    Schema::table('consignments', function (Blueprint $table) {
        $table->dropColumn('express_mode');
    });
}
};
