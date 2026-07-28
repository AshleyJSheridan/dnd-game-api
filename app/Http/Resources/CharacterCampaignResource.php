<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CharacterCampaignResource extends JsonResource
{
    public function toArray(Request $request): array
        {
            return [
                'name' => $this->name,
                'guid' => $this->guid,
                'level' => $this->level,
                'charRace' => $this->CharacterRace->name ?? '',
                'custom_portrait' => $this->custom_portrait ?? '',
            ];
        }
}
