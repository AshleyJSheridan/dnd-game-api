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
            'pack_name' => $this->pack_name,
            'gold' => $this->gold,
            'items' => CharStarterPackItemResource::collection($this->items),
        ];
    }
}
