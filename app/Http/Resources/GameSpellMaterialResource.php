<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GameSpellMaterialResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'component' => $this->component,
            'consume_on_use' => !!$this->consume_on_use,
            'cost' => [
                'at_least' => $this->cost_at_least_amount,
                'unit' => $this->cost_at_least_unit,
                'cost_per_target' => !!$this->cost_per_target,
            ]
        ];
    }
}
