<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CampaignResourceForPlayerSimple extends JsonResource
{
    public function toArray($request)
    {
        return [
            'guid' => $this->guid,
            'name' => $this->name,
            'description' => $this->description,
            'state' => $this->state,
            'created_at' => $this->created_at,
            'maps' => CampaignMapResourceSimple::collection($this->Maps->where('hidden', 0)->where('active', 1)),
            'owner' => false,
        ];
    }
}
