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
        // Virtual field names (mapped via mutators)
        'state',
        'address',
    ];

    protected static $typeMap = [
        1 => 'Pickup',
        2 => 'Dropoff',
    ];

    protected static $typeReverseMap = [
        'Pickup' => 1,
        'Dropoff' => 2,
    ];

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

    // Accessor: type (maps numeric to string)
    public function getTypeAttribute($value)
    {
        if (is_numeric($value)) {
            return static::$typeMap[(int) $value] ?? $value;
        }
        return $value;
    }

    // Mutator: type (maps string to numeric)
    public function setTypeAttribute($value)
    {
        if (isset(static::$typeReverseMap[$value])) {
            $this->attributes['type'] = static::$typeReverseMap[$value];
        } else {
            $this->attributes['type'] = $value;
        }
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
