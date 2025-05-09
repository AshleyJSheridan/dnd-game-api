<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CharClassResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'hit_points_per_level' => $this->hit_points_per_level,
            'hit_points_at_first_level' => $this->hit_points_start,
            'primary_abilities' => $this->getPrimaryAbilities(),
            'saving_throws' => $this->getSavingThrows(),
            'armour_proficiencies' => CharProficiencyResource::collection($this->ArmourProficiencies),
            'weapon_proficiencies' => CharProficiencyResource::collection($this->WeaponProficiencies),
            'tool_proficiencies' => CharToolOptionsProficiencyResource::make($this),
            'class_features' => CharFeatureResource::collection($this->ClassFeatures),
            'path' => [
                'name' => $this->path_name,
                'description' => $this->path_description,
                'level' => $this->path_level,
                'paths' => CharClassPathResource::collection($this->Paths),
            ],
            'starting_equipment' => CharStarterPackResource::collection($this->StartingEquipmentPacks),
        ];
    }

    private function getPrimaryAbilities(): array
    {
        $abilities = [CharShortAbilityResource::make($this->getPrimaryAbility1)];

        if($this->getPrimaryAbility2)
        {
            $abilities[] = CharShortAbilityResource::make($this->getPrimaryAbility2);
        }

        return $abilities;
    }

    private function getSavingThrows(): array
    {
        return [
            CharShortAbilityResource::make($this->getSavingThrowProficiency1),
            CharShortAbilityResource::make($this->getSavingThrowProficiency2),
        ];
    }
}
