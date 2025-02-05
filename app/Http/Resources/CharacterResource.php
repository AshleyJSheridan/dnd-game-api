<?php

namespace App\Http\Resources;

use App\Models\CharAbility;
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
            'charBackground' => [
                'name' => $this->CharacterBackground->name ?? '',
                'characteristics' => CharBackgroundCharacteristicResource::collection($this->CharacterBackgroundCharacteristics) ?? [],
            ],
            'charRace' => $this->CharacterRace->name ?? '',
            'abilities' => $this->parseAbilities($this->abilities),
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
                if (isset($abilitiesJson[$ability->short_name]))
                {
                    $ability->value = $abilitiesJson[$ability->short_name];
                    $ability->modifier = floor(($ability->value - 10) / 2);
                }
                else
                {
                    $ability->value = 0;
                    $ability->modifier = 0;
                }
            }
        } catch (\Exception $e) {

        }

        return $abilities;
    }
}
