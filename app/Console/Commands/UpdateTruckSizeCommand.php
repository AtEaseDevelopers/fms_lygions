<?php

namespace App\Console\Commands;

use App\Models\Truck;
use Illuminate\Console\Command;

class UpdateTruckSizeCommand extends Command
{
    protected $signature = 'truck:update-size {--dry-run : Show what would be done without making changes}';

    protected $description = 'Update truck size based on group: 40 ft = Any, other ft = Small';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->info('[DRY RUN] No changes will be saved.');
        }

        $trucks = Truck::where('group', 'like', '%ft%')->get();

        if ($trucks->isEmpty()) {
            $this->info('No trucks found with "ft" in group.');
            return 0;
        }

        $updated = 0;

        foreach ($trucks as $truck) {
            $size = stripos($truck->group, '40 ft') !== false ? 'Any' : 'Small';

            $this->line("Truck #{$truck->number} | Group: {$truck->group} | Size: {$truck->size} → {$size}");

            if (!$dryRun) {
                $truck->update(['size' => $size]);
            }

            $updated++;
        }

        $this->info(($dryRun ? '[DRY RUN] Would update' : 'Updated') . " {$updated} truck(s).");

        return 0;
    }
}
