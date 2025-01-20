<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class GameSpell extends Model
{
    protected $table = 'game_spells';
    protected $primaryKey = 'id';

    public function SpellSchool(): HasOne
    {
        return $this->hasOne(GameSpellSchool::class, 'id', 'school');
    }

    public function CharClasses(): BelongsToMany
    {
        return $this->belongsToMany(CharClass::class, 'char_class_spells', 'spell_id', 'class_id');
    }
}
