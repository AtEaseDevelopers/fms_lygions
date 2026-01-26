<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{public function up(): void
    {
        Schema::table('consignments', function (Blueprint $table) {

            /**
             * ADD NEW COLUMNS
             */
            $table->string('pick_truck_size')->nullable()->after('driver');
            $table->string('drop_truck_size')->nullable()->after('pick_truck_size');

            $table->string('pick_truck_type')->nullable()->after('drop_truck_size');
            $table->string('drop_truck_type')->nullable()->after('pick_truck_type');

            /**
             * DROP UNUSED / REPLACED COLUMNS
             */
            if (Schema::hasColumn('consignments', 'truck_type')) {
                $table->dropColumn('truck_type');
            }

            if (Schema::hasColumn('consignments', 'pick_truck')) {
                $table->dropColumn('pick_truck');
            }

            if (Schema::hasColumn('consignments', 'drop_truck')) {
                $table->dropColumn('drop_truck');
            }
        });
    }

    public function down(): void
    {
        Schema::table('consignments', function (Blueprint $table) {

            /**
             * REVERT DROPPED COLUMNS
             */
            $table->string('truck_type')->nullable();
            $table->string('pick_truck')->nullable();
            $table->string('drop_truck')->nullable();

            /**
             * REMOVE NEW COLUMNS
             */
            $table->dropColumn([
                'pick_truck_size',
                'drop_truck_size',
                'pick_truck_type',
                'drop_truck_type',
            ]);
        });
    }
};
