<?php

namespace App\Http\Resources;

use App\Models\CharAbility;
use App\Models\CharRace;
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
            'charClass' => $this->CharacterClass->name ?? '',
            'class_path_available' => $this->level >= $this->CharacterClass->path_level,
            'charBackground' => [
                'name' => $this->CharacterBackground->name ?? '',
                'characteristics' => CharBackgroundCharacteristicResource::collection($this->CharacterBackgroundCharacteristics) ?? [],
            ],
            'charRace' => $this->CharacterRace->name ?? '',
            'abilities' => $this->parseAbilities($this->abilities),
            'languages' => [
                'available' => $this->AvailableLanguageCount(),
                'known' => CharLanguageResource::collection($this->Languages),
            ],
            'magic' => [
                'hasMagic' => $this->HasMagic(),
                'learned_spells' => GameSpellResource::collection($this->Spells),
                'other_known_spells' => GameSpellResource::collection($this->getOtherKnownSpells()),
            ],
            'custom_portrait' => $this->whenLoaded('custom_portrait'),
            'created_at' => $this->created_at,
        ];
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
            var_dump($e->getMessage());
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
}
