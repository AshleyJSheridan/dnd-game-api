<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CharBackgroundResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'extra_languages' => $this->extra_languages,
            'gold' => $this->gold,
            'proficiencies' => [
                CharSkillResource::make($this->ProficiencySkill1),
                CharSkillResource::make($this->ProficiencySkill2),
            ],
            'characteristics' => CharBackgroundCharacteristicsResource::make($this->Characteristics),
        ];
    }
}
