<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Character extends Model
{
    protected $table = 'characters';
    protected $primaryKey = 'id';

    public function CharacterClass(): HasOne
    {
        return $this->hasOne(CharClass::class, 'id', 'class_id');
    }

    public function CharacterRace(): HasOne
    {
        return $this->hasOne(CharRace::class, 'id', 'race_id');
    }
}
