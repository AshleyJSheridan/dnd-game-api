<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EncounterCreatureResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'guid' => $this->guid,
            'creature_id' => $this->creature_id,
            'creature_details' => [
                'unique_name' => $this->unique_name,
                'type' => $this->Creature->type,
                'name' => $this->Creature->name,
                'max_hp' => $this->max_hp,
                'current_hp' => $this->current_hp,
                'size' => $this->Creature->size,
                'speed' => $this->Creature->speed,
                'abilities' => json_decode($this->Creature->abilities),
                'overrides' => $this->overrides,
            ],
        ];
    }
}
