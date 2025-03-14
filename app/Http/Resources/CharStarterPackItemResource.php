<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CharStarterPackItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return array_filter([
            'name' => $this->name,
            'description' => $this->description,
            'type' => $this->type,
            'cost' => $this->cost,
            'cost_unit' => $this->cost_unit,
            'weight' => $this->weight,
            'weapon_properties' => $this->getWeaponProperties(),
            'armor_properties' => $this->whenLoaded('armor_props,')
        ]);
    }

    private function getWeaponProperties(): array|null
    {
        if ($this->type !== 'weapon')
            return null;

        $props = [];

        $propTypes = ['damage', 'damage_type', 'weapon_versatility'];
        foreach ($propTypes as $propType)
        {
            if ($this->$propType)
            {
                $props[$propType] = $this->$propType;
            }
        }

        if (isset($this->range_normal) && isset($this->range_long))
        {
            $props['range'] = [$this->range_normal, $this->range_long];
        }

        return $props;
    }
}
