<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CampaignMapCharacterResource extends JsonResource
{
    public function toArray(Request $request)
    {
        return [
            'guid' => $this->guid,
            'type' => $this->type,
            'entity_name' =>$this->Player->name,
            'x' => $this->x,
            'y' => $this->y,
            'highlight_colour' => $this->highlight_colour,
            'entity' => CharacterResource::make($this->Player),
        ];
    }
}
