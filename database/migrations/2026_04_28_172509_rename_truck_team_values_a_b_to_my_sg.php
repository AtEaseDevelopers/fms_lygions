<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::connection('snl')->table('lorries')->where('team', 'A')->update(['team' => 'MY']);
        DB::connection('snl')->table('lorries')->where('team', 'B')->update(['team' => 'SG']);

        DB::table('subcons')->where('team', 'A')->update(['team' => 'MY']);
        DB::table('subcons')->where('team', 'B')->update(['team' => 'SG']);
    }

    public function down(): void
    {
        DB::connection('snl')->table('lorries')->where('team', 'MY')->update(['team' => 'A']);
        DB::connection('snl')->table('lorries')->where('team', 'SG')->update(['team' => 'B']);

        DB::table('subcons')->where('team', 'MY')->update(['team' => 'A']);
        DB::table('subcons')->where('team', 'SG')->update(['team' => 'B']);
    }
};
