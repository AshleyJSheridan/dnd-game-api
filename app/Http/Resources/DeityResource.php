<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DeityResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'god_of' => $this->god_of,
            'alignment' => $this->Alignment->alignment,
            'domains' => array_map('trim', explode(',', $this->domains)),
            'symbol' => $this->symbol,
        ];
    }
}
