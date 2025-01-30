<?php

namespace App\Http\Resources;

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
            'charBackground' => $this->CharacterBackground->name ?? '',
            'charRace' => $this->CharacterRace->name ?? '',
            'custom_portrait' => $this->whenLoaded('custom_portrait'),
            'created_at' => $this->created_at,
        ];
    }
}
