<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CharRace extends Model
{
    /** @use HasFactory<\Database\Factories\CharClassFactory> */
    use HasFactory;

    protected $table = 'char_races';
    protected $primaryKey = 'id';
    const CREATED_AT = null;
    const UPDATED_AT = null;

    public function SubRaces(): HasMany
    {
        return $this->hasMany(CharRace::class, 'parent_race_id', 'id');
    }

    public function RaceTraits(): BelongsToMany
    {
        return $this->belongsToMany(CharTrait::class, 'char_race_traits', 'char_race_id', 'char_trait_id');
    }

    public function RaceLanguages(): BelongsToMany
    {
        return $this->belongsToMany(CharLanguage::class, 'char_race_language', 'race_id', 'language_id');
    }
}
