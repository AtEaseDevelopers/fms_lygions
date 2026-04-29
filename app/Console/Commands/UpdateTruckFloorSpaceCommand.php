<?php

namespace App\Console\Commands;

use App\Models\Truck;
use Illuminate\Console\Command;

class UpdateTruckFloorSpaceCommand extends Command
{
    protected $signature = 'truck:update-floor-space {--dry-run : Show what would be done without making changes}';

    protected $description = 'Set floor_space = (leading integer parsed from group) * 8 for every lorry';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->info('[DRY RUN] No changes will be saved.');
        }

        $trucks = Truck::whereRaw("`group` REGEXP '[0-9]'")->get();

        if ($trucks->isEmpty()) {
            $this->info('No trucks found with a number in the group column.');
            return 0;
        }

        $updated = 0;
        $skipped = 0;

        foreach ($trucks as $truck) {
            if (!preg_match('/(\d+)/', (string) $truck->group, $m)) {
                $this->warn("Truck #{$truck->number} | Group: '{$truck->group}' — no digit found, skipping.");
                $skipped++;
                continue;
            }

            $floorSpace = (int) $m[1] * 8;

            $this->line("Truck #{$truck->number} | Group: '{$truck->group}' | Floor space: {$truck->floor_space} → {$floorSpace}");

            if (!$dryRun) {
                $truck->update(['floor_space' => $floorSpace]);
            }

            $updated++;
        }

        $this->info(($dryRun ? '[DRY RUN] Would update' : 'Updated') . " {$updated} truck(s).");

        if ($skipped > 0) {
            $this->warn("Skipped {$skipped} truck(s) with unparseable group.");
        }

        return 0;
    }
}
