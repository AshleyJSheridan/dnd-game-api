<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class CharClass extends Model
{
    protected $table = 'char_classes';
    protected $primaryKey = 'id';

    public function getPrimaryAbility1(): HasOne
    {
        return $this->hasOne(CharAbility::class, 'id', 'primary_ability_1');
    }

    public function getPrimaryAbility2(): HasOne
    {
        return $this->hasOne(CharAbility::class, 'id', 'primary_ability_2');
    }

    public function getSavingThrowProficiency1(): HasOne
    {
        return $this->hasOne(CharAbility::class, 'id', 'saving_throw_ability_1');
    }

    public function getSavingThrowProficiency2(): HasOne
    {
        return $this->hasOne(CharAbility::class, 'id', 'saving_throw_ability_2');
    }

    public function ArmourProficiencies(): BelongsToMany
    {
        return $this->belongsToMany(CharProficiency::class, 'char_class_proficiencies', 'char_class_id', 'char_proficiency_id')
            ->where('type', 'Armor');
    }

    public function WeaponProficiencies(): BelongsToMany
    {
        return $this->belongsToMany(CharProficiency::class, 'char_class_proficiencies', 'char_class_id', 'char_proficiency_id')
            ->whereIn('type', ['Melee (simple)', 'Melee (martial)', 'Ranged (simple)', 'Ranged (martial)']);
    }

    public function ToolProficiencies(): BelongsToMany
    {
        return $this->belongsToMany(ToolProficiency::class, 'char_class_tools', 'char_class_id', 'char_tool_id');
    }

    public function ClassFeatures(): BelongsToMany
    {
        return $this->belongsToMany(CharFeature::class, 'char_class_features', 'char_class_id', 'char_feature_id');
    }

    public function StartingEquipmentPacks(): HasMany
    {
        return $this->hasMany(ItemStarterPack::class, 'char_class_id', 'id');
    }
}
