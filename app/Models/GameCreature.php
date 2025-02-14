<?php

namespace App\Models;

use App\Http\Resources\CreatureAlignment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class GameCreature extends Model
{
    protected $table = 'game_monsters';
    protected $primaryKey = 'id';

    public function Alignment(): HasOne
    {
        return $this->hasOne(CreatureAlignment::class, 'id', 'alignment');
    }
}
