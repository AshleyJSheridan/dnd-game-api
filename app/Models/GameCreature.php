<?php

namespace App\Models;

use App\Http\Resources\CreatureAlignment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class GameCreature extends Model
{
    protected $table = 'game_monsters';
    protected $primaryKey = 'id';

    public function Alignment(): HasOne
    {
        return $this->hasOne(CreatureAlignment::class, 'id', 'alignment');
    }

    public function Languages(): BelongsToMany
    {
        return $this->belongsToMany(CharLanguage::class, 'game_monster_languages', 'monster_id', 'language_id');
    }
}
