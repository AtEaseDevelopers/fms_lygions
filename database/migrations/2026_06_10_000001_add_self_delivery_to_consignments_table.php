<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('consignments', function (Blueprint $table) {
            $table->boolean('self_delivery')->default(false)->after('express_mode');
        });
    }

    public function down(): void
    {
        Schema::table('consignments', function (Blueprint $table) {
            $table->dropColumn('self_delivery');
        });
    }
};
