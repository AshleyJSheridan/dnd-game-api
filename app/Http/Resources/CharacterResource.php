<?php

namespace App\Http\Resources;

use App\Models\CharAbility;
use App\Models\CharSkill;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CharacterResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'name' => $this->name,
            'guid' => $this->guid,
            'level' => $this->level,
            'speed' => $this->CharacterRace->speed ?? 0,
            'hit_points' => $this->getHitPoints(),
            'proficiency_bonus' => $this->getProficiencyBonus($this->level),
            'charClass' => $this->CharacterClass->name ?? '',
            'class_path_available' => $this->level >= ($this->CharacterClass->path_level ?? 20),
            'class_path_name' => $this->CharacterClass->path_name ?? '',
            'selected_class_path' => CharClassPathResource::make($this->CharacterClassPath),
            'class_features' => CharFeatureResource::collection($this->CharacterClass->ClassFeatures ?? []),
            'saving_throws' => [
                CharShortAbilityResource::make($this->CharacterClass->getSavingThrowProficiency1 ?? null),
                CharShortAbilityResource::make($this->CharacterClass->getSavingThrowProficiency2 ?? null),
            ],
            'charBackground' => [
                'name' => $this->CharacterBackground->name ?? '',
                'characteristics' => CharBackgroundCharacteristicResource::collection($this->CharacterBackgroundCharacteristics) ?? [],
            ],
            'charRace' => $this->CharacterRace->name ?? '',
            'abilities' => $this->parseAbilities($this->abilities),
            'skills' => $this->getCharSkills(),
            'languages' => [
                'available' => $this->AvailableLanguageCount(),
                'known' => CharLanguageResource::collection($this->Languages ?? []),
                'racial' => CharLanguageResource::collection($this->CharacterRace->RaceLanguages ?? []),
                'class' => CharLanguageResource::collection($this->CharacterClass ? $this->CharacterClass->Languages->where('pivot.level', '<=', $this->level) : []),
            ],
            'magic' => [
                'hasMagic' => $this->HasMagic(),
                'learned_spells' => GameSpellResource::collection($this->Spells),
                'other_known_spells' => GameSpellResource::collection($this->getOtherKnownSpells()),
            ],
            'custom_portrait' => $this->custom_portrait ?? '',
            'inventory' => [
                'money' => $this->formatMoney($this->money),
                'items' => CharInventoryItemResource::collection($this->Inventory)
            ],
            'alignment' => AlignmentResource::make($this->CharAlignment),
            'ac' => $this->calculateArmorClass(),
            'created_at' => $this->created_at,
        ];
    }

    private function calculateArmorClass()
    {
        $response = [
            'base' => 10,
            'modifiers' => [],
            'armor_bonus' => 0,
            'shield_bonus' => 0,
            'total' => 10
        ];

        $parsedAbilities = $this->parseAbilities($this->abilities);
        $dex = $parsedAbilities->where('short_name', 'dex')->first();
        $response['modifiers']['dex'] = ($dex ? $dex->modifier : 0) + ($dex ? $dex->racialModifier : 0);

        $equippedArmour = $this->Inventory->where('equipped', true)->where('Item.type', 'armor');
        if (count($equippedArmour))
        {
            foreach ($equippedArmour as $amour)
            {
                $armourProps = json_decode($amour->Item->armor_props);
                if (property_exists($armourProps, 'ac'))
                {
                    // Add the armour AC to the appropriate bonus. Enforcement of only one type of armour of a type is handled by the equipping of items.
                    $armourType = $amour->Item->Proficiency->name ?? '';
                    if (strstr($armourType, 'Shield'))
                    {
                        $response['shield_bonus'] = $armourProps->ac;
                    }
                    else
                    {
                        $response['armor_bonus'] = $armourProps->ac;
                    }
                }
            }
        }

        // Calculate normal total AC, but it can be overwritten by specific classes
        $response['total'] = ($response['armor_bonus'] > 0 ? $response['armor_bonus'] : $response['base'])
            + $response['modifiers']['dex']
            + $response['shield_bonus'];

        // Only add this if the character is *not* wearing armor (shield is fine).
        if ($this->CharacterClass->name === 'Barbarian' && $response['armor_bonus'] === 0)
        {
            $con = $parsedAbilities->where('short_name', 'con')->first();
            $response['modifiers']['con'] = ($con ? $con->modifier : 0) + ($con ? $con->racialModifier : 0);

            $response['total'] = 10 + $response['modifiers']['dex'] + $response['modifiers']['con'] + $response['shield_bonus'];
        }

        // Only add this if the character is *not* wearing armor (shield is fine).
        if ($this->CharacterClass->name === 'Monk' && $response['armor_bonus'] === 0)
        {
            $wis = $parsedAbilities->where('short_name', 'con')->first();
            $response['modifiers']['wis'] = ($wis ? $wis->modifier : 0) + ($wis ? $wis->racialModifier : 0);

            $response['total'] = 10 + $response['modifiers']['dex'] + $response['modifiers']['wis'] + $response['shield_bonus'];
        }

        return $response;
    }

    private function formatMoney($money)
    {
        $moneyArray = json_decode($money, true);

        if (!is_array($moneyArray)) {
            return [
                'copper' => 0,
                'silver' => 0,
                'electrum' => 0,
                'gold' => 0,
                'platinum' => 0,
            ];
        }

        return array_merge([
            'copper' => 0,
            'silver' => 0,
            'electrum' => 0,
            'gold' => 0,
            'platinum' => 0,
        ], $moneyArray);
    }

    private function parseAbilities($abilitiesJsonStr): Collection
    {
        $abilities = CharAbility::all();

        try {
            $abilitiesJson = json_decode($abilitiesJsonStr, true);

            foreach ($abilities as &$ability)
            {
                $ability->base = 0;

                if (isset($abilitiesJson[$ability->short_name]))
                {
                    $ability->base = $abilitiesJson[$ability->short_name];
                }

                $ability->racialModifier = $this->getRacialModifier($ability->id);

                $ability->modifier = floor(($ability->base + $ability->racialModifier - 10) / 2);
            }
        } catch (\Exception $e) {
            //var_dump($e->getMessage());
        }

        return $abilities;
    }

    private function getRacialModifier(int $abilityId): int
    {
        if(!$this->CharacterRace)
            return 0;

        // local version of Maria doesn't have newer JSON_* methods, so doing this in less than ideal way
        $traits = $this->CharacterRace->RaceTraits
            ->where('type', 'ability_increase');

        foreach ($traits as $trait)
        {
            $ability_details = json_decode($trait->ability_details);

            // we only care about the first match, there shouldn't be two modifiers here for the same ability
            if (property_exists($ability_details, 'abilities') && in_array($abilityId, $ability_details->abilities))
                return $ability_details->increase;
        }

        return 0;
    }

    private function getProficiencyBonus(int $level): int
    {
        if ($level  > 16)
            return 6;

        if ($level > 12)
            return 5;

        if ($level > 8)
            return 4;

        if ($level > 4)
            return 3;

        return 2;
    }

    private function getCharSkills()
    {
        $racialSkillIds = json_decode($this->CharacterRace->skills ?? "[]");
        $classSkillDetails = json_decode($this->CharacterClass->skill_options ?? '{"max":2,"skills":[]}');

        return [
            'known' => CharSkillResource::collection($this->Skills),
            'racial_known' => CharSkillResource::collection(CharSkill::whereIn('id', $racialSkillIds)->get()),
            'available_count' => $classSkillDetails->max,
            'available' => CharSkillResource::collection(CharSkill::whereIn('id', $classSkillDetails->skills)->get()),
        ];
    }

    private function getHitPoints()
    {
        $level = $this->level ?? 1;
        $hitPoints = ($this->CharacterClass->hit_points_start ?? 0) +
            ($this->CharacterClass->hit_points_per_level ?? 0) * ($level - 1);

        if ($this->CharacterRace) {
            $this->CharacterRace->RaceTraits->where('type', 'ability_increase')->each(function ($trait) use (&$hitPoints, $level) {
                ;
                $details = json_decode($trait->ability_details);
                if (property_exists($details, 'hitPoints')) {
                    $hitPoints += ($details->hitPoints * $level);
                }
            });
        }

        return [
            'max' => $hitPoints,
            'current' => $this->hit_points,
            'hit_points_temp' => $this->hit_points_temp,
        ];
    }
}
