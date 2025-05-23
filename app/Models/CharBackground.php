<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class CharBackground extends Model
{
    /** @use HasFactory<\Database\Factories\CharBackgroundFactory> */
    use HasFactory;

    protected $table = 'char_backgrounds';
    protected $primaryKey = 'id';
    const UPDATED_AT = null;
    const CREATED_AT = null;

    public function ProficiencySkill1(): HasOne
    {
        return $this->hasOne(CharSkill::class, 'id', 'proficiency_1');
    }

    public function ProficiencySkill2(): HasOne
    {
        return $this->hasOne(CharSkill::class, 'id', 'proficiency_2');
    }

    public function Characteristics(): HasMany
    {
        return $this->hasMany(CharBackgroundCharacteristic::class, 'background_id', 'id');
    }

    public function StartingEquipmentPacks(): HasMany
    {
        return $this->hasMany(ItemStarterPack::class, 'char_background_id', 'id');
    }
}
