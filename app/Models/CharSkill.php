<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class CharSkill extends Model
{
    protected $table = 'char_skills';
    protected $primaryKey = 'id';

    public function PrimaryAbility(): HasOne
    {
        return $this->hasOne(CharAbility::class, 'id', 'primary_ability');
    }
}
