<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A free-text note the planner can attach to any truck's availability cell
 * (e.g. "leaves late", "driver swap", "half-day only"). Shown on the calendar
 * cell and editable from the cell-details modal.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('availabilities', function (Blueprint $table) {
            if (!Schema::hasColumn('availabilities', 'remarks')) {
                $table->string('remarks', 500)->nullable()->after('status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('availabilities', function (Blueprint $table) {
            if (Schema::hasColumn('availabilities', 'remarks')) {
                $table->dropColumn('remarks');
            }
        });
    }
};
