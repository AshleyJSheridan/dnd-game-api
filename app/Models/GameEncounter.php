<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GameEncounter extends Model
{
    protected $table = 'game_encounters';
    protected $primaryKey = 'id';
    protected $fillable = ['guid', 'type', 'description', 'difficulty', 'party_difficulty', 'environment', 'created_at'];

    public function Creatures(): HasMany
    {
        return $this->hasMany(GameEncounterCreature::class, 'encounter_id', 'id');
    }
}
