<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EncounterResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /*return [
            'environment' => $this['environment'],
            'difficulty' => $this['difficulty'],
            'partyDifficulty' => $this['partyDifficulty'],
            'amount' => count($this['creatures']),
            'creatures' => CreatureResource::collection($this['creatures']),
        ];*/

        return [
            'guid' => $this->guid,
            'type' => $this->type,
            'description' => $this->description,
            'environment' => $this->environment,
            'difficulty' => $this->difficulty,
            'party_difficulty' => $this->party_difficulty,
            'creatures' => EncounterCreatureResource::collection($this->Creatures),
        ];
    }
}
