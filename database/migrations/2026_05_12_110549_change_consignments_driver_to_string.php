<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // The driver column was declared int but every flow in the codebase
        // (cell-details dropdown, pluck('driver'), name comparisons) treats it
        // as a name string. Under MySQL strict mode, writes were silently
        // failing. Widen to nullable string so explicit driver picks persist.
        DB::statement('ALTER TABLE consignments MODIFY driver VARCHAR(255) NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE consignments MODIFY driver INT NULL');
    }
};
