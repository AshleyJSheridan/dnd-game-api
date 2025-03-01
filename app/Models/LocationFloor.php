<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LocationFloor extends Model
{
    protected $table = 'game_locations_floor';
    protected $primaryKey = 'id';

    public function Objects(): HasMany
    {
        return $this->hasMany(LocationObject::class, 'floor_id', 'id');
    }
}
