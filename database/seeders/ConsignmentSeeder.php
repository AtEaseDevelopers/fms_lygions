<?php

namespace Database\Seeders;

use App\Models\Consignment;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

/**
 * Dummy consignments for testing the Truck Planning grid (Save All / Edit All
 * and the failed-row indicators). Run with:
 *   php artisan db:seed --class=ConsignmentSeeder
 */
class ConsignmentSeeder extends Seeder
{
    public function run(): void
    {
        $today = Carbon::today();
        $dateCode = Carbon::now('Asia/Kuala_Lumpur')->format('dm');

        // Continue numbering past any existing consignments so consignment_no
        // stays unique even if this seeder is run more than once.
        $base = (int) (Consignment::max('id') ?? 0) + 1000;

        $rows = [
            ['sentina',      'APEX PHARMA MARKETING', 'Singapore', 'Singapore', 'Pending'],
            ['otc cosmetics', 'APEX PHARMA MARKETING', 'Singapore', 'Singapore', 'Pending'],
            ['bodibasixs',   'APEX PHARMA MARKETING', 'Singapore', 'Singapore', 'Planning'],
            ['SUCCESS ELECTRONICS AND', 'CONCORD CORPORATION PTE LTD', 'Singapore', 'Singapore', 'Pending'],
            ['WEG',          'United U-LI',           'Singapore', 'Singapore', 'Pending'],
            ['Wintech',      'United U-LI',           'Singapore', 'Singapore', 'Completed'],
            ['United U-LI',  'CONCORD CORPORATION PTE LTD', 'Singapore', 'Singapore', 'Pending'],
            ['United U-LI',  '-',                     'Singapore', '-',         'Pending'],
        ];

        foreach ($rows as $i => [$consignor, $consignee, $pickPoint, $dropPoint, $status]) {
            Consignment::create([
                'load_date'      => $today->copy()->addDays($i % 5)->toDateString(),
                'consignment_no' => 'CSN. ' . $dateCode . '-' . str_pad((string) ($base + $i), 4, '0', STR_PAD_LEFT),
                'consignor'      => $consignor,
                'consignee'      => $consignee,
                'pick_point'     => $pickPoint,
                'drop_point'     => $dropPoint,
                'pick_truck_size' => 'Any',
                'drop_truck_size' => $i % 2 ? 'Small' : 'Any',
                'pick_truck_type' => 'any',
                'drop_truck_type' => 'any',
                'quantity'       => json_encode([$i + 1]),
                'unit'           => json_encode(['p4']),
                'status'         => $status,
                'express_mode'   => false,
                'self_delivery'  => false,
            ]);
        }
    }
}
