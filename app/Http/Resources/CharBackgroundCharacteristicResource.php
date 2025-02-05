<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CharBackgroundCharacteristicResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'characteristic_type' => $this->label,
            'details' => $this->characteristic,
        ];
    }
}
