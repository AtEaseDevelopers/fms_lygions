<?php

namespace App\Traits;

use App\Models\Consignment;

trait CalculatesConsignmentCapacity
{
    public function parseQuantityArray($value): array
    {
        if (is_array($value)) {
            return array_map('floatval', $value);
        }
        if (is_numeric($value)) {
            return [(float) $value];
        }

        $decoded = json_decode($value, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return array_map('floatval', $decoded);
        }

        $trimmed = trim((string) $value, "[] \t\n\r\0\x0B");
        if ($trimmed === '') {
            return [];
        }
        $parts = array_filter(array_map('trim', explode(',', $trimmed)));
        return array_map('floatval', $parts);
    }

    public function resolveUnitSpaces($unitValue, array $unitsMap): array
    {
        $unitStrings = json_decode((string) $unitValue, true);
        if (!is_array($unitStrings)) {
            $unitStrings = $this->parseQuantityArray($unitValue);
        }

        return array_map(
            fn($u) => (float) ($unitsMap[trim((string) $u)] ?? 0),
            $unitStrings
        );
    }

    public function consignmentVolume(Consignment $c, array $unitsMap): float
    {
        $qtys = $this->parseQuantityArray($c->quantity);
        $units = $this->resolveUnitSpaces($c->unit, $unitsMap);
        $max = max(count($qtys), count($units), 1);

        $total = 0.0;
        for ($i = 0; $i < $max; $i++) {
            $q = $qtys[$i] ?? $qtys[0] ?? 0;
            $u = $units[$i] ?? $units[0] ?? 1;
            $total += $q * $u;
        }
        return (float) $total;
    }
}
