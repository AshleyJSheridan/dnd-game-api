<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GameSpellSchool extends Model
{
    /** @use HasFactory<\Database\Factories\GameSpellFactory> */
    use HasFactory;

    protected $table = 'game_spell_schools';
    protected $primaryKey = 'id';
    const CREATED_AT = null;
    const UPDATED_AT = null;

    public function Spells(): HasMany
    {
        return $this->hasMany(GameSpell::class, 'school', 'id');
    }
}
