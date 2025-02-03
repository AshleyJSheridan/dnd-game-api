<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Character extends Model
{
    protected $table = 'characters';
    protected $primaryKey = 'id';

    protected $fillable = ['guid', 'name', 'user_id', 'created_at', 'level'];

    public function CharacterClass(): HasOne
    {
        return $this->hasOne(CharClass::class, 'id', 'class_id');
    }

    public function CharacterRace(): HasOne
    {
        return $this->hasOne(CharRace::class, 'id', 'race_id');
    }

    public function CharacterBackground(): HasOne
    {
        return $this->hasOne(CharBackground::class, 'id', 'background_id');
    }

    public function CharacterBackgroundTraits(): BelongsToMany
    {
        return $this->belongsToMany(CharBackgroundCharacteristic::class, 'char_selected_bg_traits', 'character_id', 'characteristic_id');
    }
}
