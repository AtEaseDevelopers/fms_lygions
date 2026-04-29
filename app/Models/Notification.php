<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    protected $table = 'notifications';

    protected $fillable = [
        'type',
        'consignment_id',
        'triggered_by_consignment_id',
        'truck_number',
        'affected_date',
        'message',
        'read_at',
    ];

    protected $casts = [
        'affected_date' => 'date',
        'read_at' => 'datetime',
    ];

    public function consignment()
    {
        return $this->belongsTo(Consignment::class, 'consignment_id');
    }

    public function trigger()
    {
        return $this->belongsTo(Consignment::class, 'triggered_by_consignment_id');
    }

    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }
}
