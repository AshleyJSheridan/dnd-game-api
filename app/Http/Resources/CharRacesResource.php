<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CharRacesResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'max_age' => $this->max_age,
            'max_height' => $this->max_height,
            'languages' => CharLanguageResource::collection($this->RaceLanguages),
            'speed' => $this->speed,
            'race_traits' => CharTraitsResource::collection($this->RaceTraits),
            'sub_races' => CharRacesResource::collection($this->SubRaces),
        ];
    }
}
