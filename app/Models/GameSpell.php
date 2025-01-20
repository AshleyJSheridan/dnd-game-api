<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class GameSpell extends Model
{
    protected $table = 'game_spells';
    protected $primaryKey = 'id';

    public function SpellSchool(): HasOne
    {
        return $this->hasOne(GameSpellSchool::class, 'id', 'school');
    }
}
