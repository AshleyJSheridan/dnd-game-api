<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MapObject extends Model
{
    protected $table = 'game_map_objects';
    protected $primaryKey = 'id';

    protected $fillable = ['name', 'description', 'width', 'height', 'image'];
}
