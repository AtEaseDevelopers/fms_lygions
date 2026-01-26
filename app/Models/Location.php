<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class Location extends Model
{
    use HasFactory;

    // Allow mass assignment for name
    protected $fillable = ['name'];
}
