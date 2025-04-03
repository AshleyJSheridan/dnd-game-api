<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CampaignResourceForPlayer extends JsonResource
{
    public function toArray($request)
    {
        return [
            'guid' => $this->guid,
            'name' => $this->name,
            'description' => $this->description,
            'state' => $this->state,
            'created_at' => $this->created_at,
            'owner' => false,
            'players' => CharacterResource::collection($this->Characters),
        ];
    }
}
