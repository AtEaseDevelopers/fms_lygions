<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('temporary_trucks', function (Blueprint $table) {
            $table->unsignedBigInteger('subcon_id')->nullable()->after('label');
            $table->index('subcon_id');
        });
    }

    public function down(): void
    {
        Schema::table('temporary_trucks', function (Blueprint $table) {
            $table->dropIndex(['subcon_id']);
            $table->dropColumn('subcon_id');
        });
    }
};
