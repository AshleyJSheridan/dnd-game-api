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
            'player' => [
                'name' => $this->Player->name,
                'guid' => $this->Player->guid,
                'custom_portrait' => $this->Player->custom_portrait ?? '',
            ]
        ];
    }
}
