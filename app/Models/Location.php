<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Location extends Model
{
    protected $table = 'game_locations';
    protected $primaryKey = 'id';

    public function Floors(): HasMany
    {
        return $this->hasMany(LocationFloor::class, 'location_id', 'id');
    }
}
