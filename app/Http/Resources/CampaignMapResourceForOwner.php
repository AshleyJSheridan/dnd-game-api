<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CampaignMapResourceForOwner extends JsonResource
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
            'hidden' => $this->hidden == 1,
            'active' => $this->active == 1,
            'players' => CampaignMapCharacterResource::collection($this->Players),
            'creatures' => CampaignMapCreatureResource::collection($this->Creatures),
            'drawings' => CampaignMapDrawingResource::collection($this->Drawings),
        ];
    }
}
