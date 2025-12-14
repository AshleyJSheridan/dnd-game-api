<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CampaignLoreResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'guid' => $this->guid,
            'name' => $this->name,
            'type' => $this->type,
            'created_at' => $this->created_at,
            'content' => $this->parsed_content,
            'content_raw' => $this->raw_content,
            'url' => $this->url,
            'is_file' => $this->file !== null,
            'is_image' => (boolean) $this->is_image,
            'lore_group' => $this->LoreGroup->name ?? null,
        ];
    }
}
