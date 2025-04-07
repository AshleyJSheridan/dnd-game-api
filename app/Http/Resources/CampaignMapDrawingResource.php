<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CampaignMapDrawingResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'guid' => $this->guid,
            'type' => $this->type,
            'entity_name' => $this->entity_name,
            'x' => $this->x,
            'y' => $this->y,
            'highlight_colour' => $this->highlight_colour,
            'orientation' => $this->orientation,
            'stats' => json_decode($this->stats),
            'linked_id' => 0,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
