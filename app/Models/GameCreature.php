<?php

namespace App\Models;

use App\Http\Resources\CreatureAlignment;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class GameCreature extends Model
{
    /** @use HasFactory<\Database\Factories\GameCreatureFactory> */
    use HasFactory;

    protected $table = 'game_monsters';
    protected $primaryKey = 'id';
    const UPDATED_AT = null;
    const CREATED_AT = null;

    public function Alignment(): HasOne
    {
        return $this->hasOne(CreatureAlignment::class, 'id', 'alignment');
    }

    public function Languages(): BelongsToMany
    {
        return $this->belongsToMany(CharLanguage::class, 'game_monster_languages', 'monster_id', 'language_id');
    }

    public function Environment(): HasMany
    {
        return $this->hasMany(CreatureLookup::class, 'monster_id', 'id');
    }
}
