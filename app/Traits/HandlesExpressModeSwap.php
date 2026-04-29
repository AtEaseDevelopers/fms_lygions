<?php

namespace App\Traits;

use App\Models\Availability;
use App\Models\Consignment;
use App\Models\Notification;
use App\Models\Subcon;
use App\Models\Truck;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

trait HandlesExpressModeSwap
{
    public function regionFromPickPoint(?string $pickPoint): string
    {
        return strcasecmp((string) $pickPoint, 'Singapore') === 0 ? 'SG' : 'MY';
    }

    public function applyExpressSwap(Consignment $consignment): array
    {
        if (!$consignment->express_mode || !$consignment->truck_number) {
            return ['affected' => collect()];
        }

        $truckNumber = $consignment->truck_number;
        $truckId = Truck::where('number', $truckNumber)->value('id');
        $subconId = $truckId ? null : Subcon::where('truck_no', $truckNumber)->value('id');

        if (!$truckId && !$subconId) {
            return ['affected' => collect()];
        }

        $loadDate = Carbon::parse($consignment->load_date);
        $originLocation = $this->regionFromPickPoint($consignment->pick_point);
        $oppositeLocation = $originLocation === 'SG' ? 'MY' : 'SG';

        $affected = collect();

        DB::transaction(function () use (
            $consignment,
            $truckId,
            $subconId,
            $truckNumber,
            $loadDate,
            $oppositeLocation,
            &$affected
        ) {
            $this->enableOppositeRegionOnLoadDate(
                $truckId,
                $subconId,
                $loadDate->toDateString(),
                $oppositeLocation
            );

            $currentDate = $loadDate->copy()->addDay();

            while (true) {
                $query = Availability::query()->whereDate('date', $currentDate->toDateString());
                if ($truckId) {
                    $query->where('truck_id', $truckId);
                } else {
                    $query->where('subcon_id', $subconId);
                }
                $availability = $query->first();

                if (!$availability || strtolower($availability->status) !== 'available') {
                    break;
                }

                $newLocation = $availability->location === 'SG' ? 'MY' : 'SG';
                $availability->update(['location' => $newLocation]);

                $stranded = Consignment::where('truck_number', $truckNumber)
                    ->whereDate('load_date', $currentDate->toDateString())
                    ->where('id', '!=', $consignment->id)
                    ->get();

                foreach ($stranded as $other) {
                    $required = $this->regionFromPickPoint($other->pick_point);
                    if ($required === $newLocation) {
                        continue;
                    }

                    $other->update(['truck_number' => null]);

                    Notification::create([
                        'type' => 'express_swap_unassigned',
                        'consignment_id' => $other->id,
                        'triggered_by_consignment_id' => $consignment->id,
                        'truck_number' => $truckNumber,
                        'affected_date' => $currentDate->toDateString(),
                        'message' => sprintf(
                            'Truck %s swapped to %s on %s due to express order %s; %s unassigned.',
                            $truckNumber,
                            $newLocation,
                            $currentDate->format('Y-m-d'),
                            $consignment->consignment_no,
                            $other->consignment_no
                        ),
                    ]);

                    $affected->push($other);
                }

                $currentDate->addDay();
            }
        });

        return ['affected' => $affected];
    }

    private function enableOppositeRegionOnLoadDate(
        ?int $truckId,
        ?int $subconId,
        string $date,
        string $oppositeLocation
    ): void {
        $query = Availability::query()
            ->whereDate('date', $date)
            ->where('location', $oppositeLocation);

        if ($truckId) {
            $query->where('truck_id', $truckId);
        } else {
            $query->where('subcon_id', $subconId);
        }

        $existing = $query->first();

        if ($existing) {
            $existing->update(['status' => 'available']);
            return;
        }

        Availability::create([
            'truck_id' => $truckId,
            'subcon_id' => $subconId,
            'date' => $date,
            'location' => $oppositeLocation,
            'status' => 'available',
        ]);
    }
}
