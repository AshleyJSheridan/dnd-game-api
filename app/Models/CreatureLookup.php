<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class CreatureLookup extends Model
{
    protected $table = 'game_monster_lookup';
    protected $primaryKey = 'id';

    public function Creature(): HasOne
    {
        return $this->hasOne(GameCreature::class, 'id', 'monster_id');
    }
}
