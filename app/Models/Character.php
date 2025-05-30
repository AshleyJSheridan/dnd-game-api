<?php

namespace App\Models;

use App\Http\Resources\CreatureAlignment;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Character extends Model
{
    /** @use HasFactory<\Database\Factories\CharacterFactory> */
    use HasFactory, SoftDeletes;

    protected $table = 'characters';
    protected $primaryKey = 'id';

    protected $fillable = ['guid', 'name', 'user_id', 'created_at', 'level', 'class_id', 'race_id', 'background_id', 'custom_portrait'];

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

    public function CharacterBackgroundCharacteristics(): BelongsToMany
    {
        return $this->belongsToMany(CharBackgroundCharacteristic::class, 'char_selected_bg_characteristics', 'character_id', 'characteristic_id');
    }

    public function Spells(): BelongsToMany
    {
        return $this->belongsToMany(GameSpell::class, 'char_known_spells', 'char_id', 'spell_id');
    }

    public function Languages(): BelongsToMany
    {
        return $this->belongsToMany(CharLanguage::class, 'char_known_languages', 'char_id', 'language_id');
    }

    public function AvailableLanguageCount(): int
    {
        $languageCount = 0;

        $languageCount += $this->CharacterBackground->extra_languages ?? 0;
        if ($this->CharacterRace)
        {
            foreach ($this->CharacterRace->RaceTraits->where('type', 'language') as $langTrait) {
                $details = json_decode($langTrait->ability_details);
                $languageCount += $details->languages;
            }
        }


        return $languageCount;
    }

    public function HasMagic(): bool
    {
        $classMagicCount = $this->CharacterClass ?
            count($this->CharacterClass->ClassFeatures->where('type', 'magic')->where('level', '>=', $this->level)) : 0;
        $raceSpellCount = $this->CharacterRace ? count($this->CharacterRace->RaceTraits->where('type', 'spell')) : 0;

        // TODO add checks for feats when those are implemented

        return ($classMagicCount + $raceSpellCount) > 0;
    }

    public function getOtherKnownSpells()
    {
        // TODO figure out how to give user the choice of the various class path spells, etc
        /*$classSpells = $this->CharacterClass ?
            $this->CharacterClass->ClassFeatures->where('type', 'magic')->where('level', '>=', $this->level) :
            collect([]);*/
        $raceSpellDetails = $this->CharacterRace ?
            $this->CharacterRace->RaceTraits->where('type', 'spell') :
            collect([]);

        $raceSpells = collect([]);
        if ($raceSpellDetails)
        {
            $spellIds = [];
            foreach ($raceSpellDetails as $details)
            {
                $detailsJson = json_decode($details->ability_details);
                if (property_exists($detailsJson, 'spells'))
                {
                    $spellIds = array_merge($spellIds, $detailsJson->spells);
                }
            }

            if (count($spellIds))
            {
                $raceSpells = GameSpell::whereIn('id', $spellIds)->get();
            }
        }

        // TODO add checks for feats when those are implemented

        return $raceSpells;
    }

    public function Skills(): BelongsToMany
    {
        return $this->belongsToMany(CharSkill::class, 'char_known_skills', 'char_id', 'skill_id');
    }

    public function Inventory(): HasMany
    {
        return $this->hasMany(CharInventoryItem::class, 'char_id', 'id')
            ->where('parent_id', 0);
    }

    public function CharAlignment(): HasOne
    {
        return $this->hasOne(CreatureAlignment::class, 'id', 'alignment');
    }
}
