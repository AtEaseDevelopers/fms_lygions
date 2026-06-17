<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class CustomerLocation extends Model
{
    use HasFactory, SoftDeletes;

    protected $connection = 'snl';
    protected $table = 'customer_locations';

    protected $fillable = [
        'customer_id',
        'state_id',
        'location',
        'pic',
        'phone',
        'type',
        'truck_size',
        'truck_type',
        'load_type',
        'pickup_dropoff_point',
        // Per-day operation hours JSON. Owned/edited by the SNL app; FMS only
        // displays it (view-only) but keeps it fillable so a customer edit in
        // FMS (which deletes + recreates locations) preserves the value via a
        // hidden field instead of wiping it.
        'operation_hours',
        // Virtual field names (mapped via mutators)
        'state',
        'address',
    ];

    /**
     * Days of week for per-day operation hours, in display order.
     * Mirrors SNL's CustomerLocation::OPERATION_DAYS (shared table).
     */
    const OPERATION_DAYS = [
        'monday' => 'Monday',
        'tuesday' => 'Tuesday',
        'wednesday' => 'Wednesday',
        'thursday' => 'Thursday',
        'friday' => 'Friday',
        'saturday' => 'Saturday',
        'sunday' => 'Sunday',
    ];

    protected static $typeMap = [
        1 => 'Pickup',
        2 => 'Dropoff',
        3 => 'Self Delivery',
    ];

    protected static $typeReverseMap = [
        'Pickup' => 1,
        'Dropoff' => 2,
        'Self Delivery' => 3,
    ];

    const SELF_DELIVERY_ID = 3;

    protected static $stateMap = null;

    protected static function getStateMap()
    {
        if (static::$stateMap === null) {
            static::$stateMap = DB::connection('snl')
                ->table('states')
                ->pluck('name', 'id')
                ->toArray();
        }
        return static::$stateMap;
    }

    protected static function getStateReverseMap()
    {
        $map = static::getStateMap();
        return array_flip($map);
    }

    // Accessor: state (resolves state_id to name)
    public function getStateAttribute()
    {
        $stateId = $this->attributes['state_id'] ?? null;
        if ($stateId) {
            $map = static::getStateMap();
            return $map[$stateId] ?? null;
        }
        return null;
    }

    // Mutator: state (converts name to state_id)
    public function setStateAttribute($value)
    {
        if (is_numeric($value)) {
            $this->attributes['state_id'] = $value;
        } else {
            $reverseMap = static::getStateReverseMap();
            $this->attributes['state_id'] = $reverseMap[$value] ?? null;
        }
    }

    // Accessor: address (reads location column)
    public function getAddressAttribute()
    {
        return $this->attributes['location'] ?? null;
    }

    // Mutator: address (writes to location column)
    public function setAddressAttribute($value)
    {
        $this->attributes['location'] = $value;
    }

    // Raw type ids as an array of ints. `type` is stored as a comma separated
    // list (e.g. "1,3") by the SNL app to support multiple types per location.
    public function getTypeIdsAttribute(): array
    {
        $raw = $this->attributes['type'] ?? null;
        if ($raw === null || $raw === '') {
            return [];
        }

        return array_values(array_filter(array_map('intval', explode(',', (string) $raw))));
    }

    // Type ids resolved to labels, e.g. ['Pickup', 'Self Delivery'].
    public function getTypeLabelsAttribute(): array
    {
        return array_map(function ($id) {
            return static::$typeMap[$id] ?? (string) $id;
        }, $this->type_ids);
    }

    // Whether this location offers self delivery.
    public function getHasSelfDeliveryAttribute(): bool
    {
        return in_array(self::SELF_DELIVERY_ID, $this->type_ids, true);
    }

    // Accessor: type (maps comma separated ids to comma separated labels)
    public function getTypeAttribute($value)
    {
        if ($value === null || $value === '') {
            return $value;
        }

        // Single legacy numeric value -> single label (unchanged behaviour).
        if (is_numeric($value)) {
            return static::$typeMap[(int) $value] ?? $value;
        }

        // Comma separated ids -> comma separated labels.
        if (strpos((string) $value, ',') !== false) {
            return implode(',', $this->type_labels);
        }

        return $value;
    }

    // Mutator: type (maps string label to numeric; passes through ids/CSV)
    public function setTypeAttribute($value)
    {
        if (isset(static::$typeReverseMap[$value])) {
            $this->attributes['type'] = static::$typeReverseMap[$value];
        } else {
            $this->attributes['type'] = $value;
        }
    }

    /**
     * Per-day operation hours stored as a JSON map by the SNL app, e.g.
     * {"monday":"8","tuesday":"8", ...}. Returns a normalised array with every
     * day (in OPERATION_DAYS order); empty means the location is closed that
     * day. View-only in FMS.
     */
    public function getOperationHoursArrayAttribute(): array
    {
        $decoded = [];
        $raw = $this->attributes['operation_hours'] ?? null;
        if (!empty($raw)) {
            $decoded = json_decode((string) $raw, true) ?: [];
        }

        $result = [];
        foreach (array_keys(self::OPERATION_DAYS) as $day) {
            $value = $decoded[$day] ?? '';
            $result[$day] = is_scalar($value) ? (string) $value : '';
        }

        return $result;
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
