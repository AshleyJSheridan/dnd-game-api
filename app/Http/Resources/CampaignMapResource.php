<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CampaignMapResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'guid' => $this->guid,
            'name' => $this->name,
            'description' => $this->description,
            'image' => $this->image,
            'created_at' => $this->created_at,
            'width' => $this->width,
            'height' => $this->height,
        ];
    }
}
