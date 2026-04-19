<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EncounterResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'guid' => $this->guid,
            'type' => $this->type,
            'description' => $this->description,
            'environment' => $this->environment,
            'difficulty' => $this->difficulty,
            'party_difficulty' => $this->party_difficulty,
            'creatures' => EncounterCreatureResource::collection($this->Creatures),
            'created_at' => $this->created_at,
        ];
    }
}
