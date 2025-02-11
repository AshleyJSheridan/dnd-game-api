<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GameItem extends Model
{
    protected $table = 'game_items';
    protected $primaryKey = 'id';

    protected $fillable = ['name', 'description', 'cost', 'cost_unit', 'type', 'rarity', 'special', 'generated', 'weight'];
}
