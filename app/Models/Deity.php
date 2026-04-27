<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Deity extends Model
{
    protected $table = 'game_deities';
    protected $primaryKey = 'id';

    public function Alignment(): HasOne
    {
        return $this->hasOne(Alignment::class, 'id', 'alignment');
    }
}
