<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CampaignResourceForOwner extends JsonResource
{
    public function toArray($request)
    {
        return [
            'guid' => $this->guid,
            'name' => $this->name,
            'description' => $this->description,
            'state' => $this->state,
            'created_at' => $this->created_at,
            'maps' => CampaignMapResource::collection($this->Maps),
            'owner' => true,
        ];
    }
}
