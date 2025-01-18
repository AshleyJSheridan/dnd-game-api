<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CharRace extends Model
{
    protected $table = 'char_races';
    protected $primaryKey = 'id';

    public function Languages(): BelongsToMany
    {
        return $this->belongsToMany(CharLanguage::class, 'char_race_language', 'char_race_id', 'char_language_id');
    }

    public function SubRaces(): HasMany
    {
        return $this->hasMany(CharRace::class, 'parent_race_id', 'id');
    }

    public function RaceTraits(): BelongsToMany
    {
        return $this->belongsToMany(CharTrait::class, 'char_race_traits', 'char_race_id', 'char_trait_id');
    }
}
