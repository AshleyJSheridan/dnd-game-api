<?php

namespace App\Http\Resources;

use App\Enums\AmmoTypeEnum;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GameItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->type,
            'description' => $this->description,
            'weight' => $this->weight,
            'cost' => [
                'value' => $this->cost,
                'unit' => $this->cost_unit
            ],
            'proficiency' => $this->proficiency_id,
            'armor_props' => json_decode($this->armor_props),
            'weapon_props' => [
                'ammo_type' => $this->ammo_type ? AmmoTypeEnum::from($this->ammo_type)->name : null,
                'damage' => [
                    'amount' => $this->damage,
                    'type' => $this->damage_type,
                ],
                'range' => [
                    'normal' => $this->range_normal,
                    'long' => $this->range_long,
                ],
                'weapon_versatility' => $this->weapon_versatility,
            ]
        ];
    }
}
