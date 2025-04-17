<?php

namespace App\Http\Resources;

use App\Enums\AmmoTypeEnum;
use Illuminate\Http\Resources\Json\JsonResource;

class CharInventoryItemResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'parent_id' => $this->parent_id,
            'guid' => $this->guid,
            'name' => $this->name ?? $this->Item->name,
            'type' => $this->Item->type,
            'description' => $this->description ?? $this->Item->description,
            'isContainer' => $this->Item->isContainer(),
            'weight' => $this->Item->weight,
            'cost' => [
                'value' => $this->Item->cost,
                'unit' => $this->Item->cost_unit
            ],
            'rarity' => $this->Item->rarity,
            'proficiency' => $this->Item->proficiency_id,
            'armor_props' => json_decode($this->Item->armor_props),
            'weapon_props' => [
                'ammo_type' => $this->Item->ammo_type ? AmmoTypeEnum::from($this->Item->ammo_type)->name : null,
                'damage' => [
                    'amount' => $this->Item->damage,
                    'type' => $this->Item->damage_type,
                ],
                'range' => [
                    'normal' => $this->Item->range_normal,
                    'long' => $this->Item->range_long,
                ],
                'weapon_versatility' => $this->Item->weapon_versatility,
            ]
        ];
    }
}
