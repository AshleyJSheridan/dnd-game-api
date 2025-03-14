<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class GameEncounterCreature extends Model
{
    protected $table = 'game_encounter_creatures';
    protected $primaryKey = 'id';
    protected $fillable = ['guid', 'encounter_id', 'creature_id', 'unique_name', 'max_hp', 'current_hp', 'overrides', 'created_at'];

    public function Creature(): HasOne
    {
        return $this->hasOne(GameCreature::class, 'id', 'creature_id');
    }
}
