<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $snl = 'snl';

        // Add FMS-specific columns to snl.lorries
        Schema::connection($snl)->table('lorries', function (Blueprint $table) {
            if (!Schema::connection('snl')->hasColumn('lorries', 'next_inspection')) {
                $table->date('next_inspection')->nullable();
            }
            if (!Schema::connection('snl')->hasColumn('lorries', 'next_tyre')) {
                $table->date('next_tyre')->nullable();
            }
            if (!Schema::connection('snl')->hasColumn('lorries', 'next_permit')) {
                $table->date('next_permit')->nullable();
            }
            if (!Schema::connection('snl')->hasColumn('lorries', 'next_extinguisher')) {
                $table->date('next_extinguisher')->nullable();
            }
            if (!Schema::connection('snl')->hasColumn('lorries', 'next_roadtax')) {
                $table->date('next_roadtax')->nullable();
            }
            if (!Schema::connection('snl')->hasColumn('lorries', 'next_insurance')) {
                $table->date('next_insurance')->nullable();
            }
            if (!Schema::connection('snl')->hasColumn('lorries', 'next_others')) {
                $table->date('next_others')->nullable();
            }
            if (!Schema::connection('snl')->hasColumn('lorries', 'team')) {
                $table->string('team')->nullable();
            }
        });

        // Add FMS-specific columns to snl.customers
        Schema::connection($snl)->table('customers', function (Blueprint $table) {
            if (!Schema::connection('snl')->hasColumn('customers', 'nickname')) {
                $table->string('nickname')->nullable();
            }
            if (!Schema::connection('snl')->hasColumn('customers', 'billing_phone')) {
                $table->string('billing_phone')->nullable();
            }
        });

        // Add FMS-specific columns to snl.customer_locations
        Schema::connection($snl)->table('customer_locations', function (Blueprint $table) {
            if (!Schema::connection('snl')->hasColumn('customer_locations', 'truck_size')) {
                $table->string('truck_size')->nullable();
            }
            if (!Schema::connection('snl')->hasColumn('customer_locations', 'truck_type')) {
                $table->string('truck_type')->nullable();
            }
            if (!Schema::connection('snl')->hasColumn('customer_locations', 'load_type')) {
                $table->string('load_type')->nullable();
            }
        });
    }

    public function down(): void
    {
        $snl = 'snl';

        Schema::connection($snl)->table('lorries', function (Blueprint $table) {
            $table->dropColumn([
                'next_inspection', 'next_tyre', 'next_permit', 'next_extinguisher',
                'next_roadtax', 'next_insurance', 'next_others', 'team',
            ]);
        });

        Schema::connection($snl)->table('customers', function (Blueprint $table) {
            $table->dropColumn(['nickname', 'billing_phone']);
        });

        Schema::connection($snl)->table('customer_locations', function (Blueprint $table) {
            $table->dropColumn(['truck_size', 'truck_type', 'load_type']);
        });
    }
};
