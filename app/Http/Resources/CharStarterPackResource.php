<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CharStarterPackResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'choice_name' => $this->choice_name,
            'type' => $this->type,
            'gold' => $this->gold,
            'items' => CharStarterPackItemResource::collection($this->items),
        ];
    }
}
