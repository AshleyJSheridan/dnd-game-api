<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CampaignInviteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'created_at' => $this->created_at,
            'campaign' => [
                'guid' => $this->Campaign->guid,
                'name' => $this->Campaign->name,
            ],
            'owner' => [
                'name' => $this->Campaign->Owner->name,
            ],
        ];
    }
}
