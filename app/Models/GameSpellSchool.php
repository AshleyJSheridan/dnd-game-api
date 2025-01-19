<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GameSpellSchool extends Model
{
    protected $table = 'game_spell_schools';
    protected $primaryKey = 'id';

    public function Spells(): HasMany
    {
        return $this->hasMany(GameSpell::class, 'school', 'id');
    }
}
