<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CampaignMapResource extends JsonResource
{
    public function toArray(Request $request)
    {
        return [
            'guid' => $this->guid,
            'name' => $this->name,
            'description' => $this->description,
            'image' => $this->image,
            'created_at' => $this->created_at,
            'width' => $this->width,
            'height' => $this->height,
            'show_grid' => $this->show_grid,
            'grid_size' => $this->grid_size,
            'grid_colour' => $this->grid_colour,
            'players' => CampaignMapCharacterResource::collection($this->Players),
        ];
    }
}
