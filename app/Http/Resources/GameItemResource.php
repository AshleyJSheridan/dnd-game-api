<?php

namespace App\Http\Resources;

use App\Enums\AmmoTypeEnum;
use App\Models\CharClass;
use App\Models\DamageType;
use App\Models\GameSpell;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GameItemResource extends JsonResource
{
    static array $damageTypes = [];
    static  $classes = [];
    static Collection $itemSpells;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'base_name' => $this->base_name,
            'type' => $this->type,
            'description' => $this->description,
            'weight' => $this->weight,
            'cost' => [
                'value' => $this->cost,
                'unit' => $this->cost_unit
            ],
            'rarity' => $this->rarity,
            'proficiency' => $this->Proficiency->name ?? null,
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
            ],
            'special_properties' => $this->getParsedSpecialProperties(),
        ];
    }

    private function getParsedSpecialProperties(): array
    {
        $specialPropertiesRaw = json_decode($this->special, true);
        $specialProperties = [];

        if ($specialPropertiesRaw)
        {
            foreach (array_keys($specialPropertiesRaw) as $key)
            {
                // parse any damage type values into their full
                if (in_array($key, ['resistances', 'immunities']))
                {
                    $specialProperties[$key] = $this->getDamageTypesFromKeys($specialPropertiesRaw[$key]);
                    continue;
                }

                // parse single value damage types
                if ($key === 'damage_type')
                {
                    $specialProperties[$key] = $this->getDamageTypesFromKeys([$specialPropertiesRaw[$key]])[0] ?? [];
                    continue;
                }

                // parse any spell values into their full spell details
                if ($key === 'spells')
                {
                    $specialProperties[$key] = GameSpellResource::collection($this->getSpellsFromKeys($specialPropertiesRaw[$key]));
                    continue;
                }

                // parse any class limits this item may have
                if ($key === 'classes')
                {
                    $specialProperties[$key] = $this->getClassesFromKeys($specialPropertiesRaw[$key]);
                    continue;
                }

                // special case for ability, which _may_ have a damage_type property of its own
                if ($key === 'ability' && isset($specialPropertiesRaw[$key]['damage_type']))
                {
                    $specialPropertiesRaw[$key]['damage_type'] = $this->getDamageTypesFromKeys([$specialPropertiesRaw[$key]['damage_type']])[0] ?? [];
                }

                $specialProperties[$key] = $specialPropertiesRaw[$key];
            }

            return $specialProperties;
        }

        return $specialProperties;
    }

    private function getClassesFromKeys(array $ids): array
    {
        if (empty(self::$classes)) {
            self::$classes = CharClass::select('id', 'name')->get()->keyBy('id')->toArray();
        }

        $classes = [];
        foreach ($ids as $id)
        {
            if (isset(self::$classes[$id])) {
                $classes[] = self::$classes[$id];
            }
        }

        return $classes;
    }

    private function getSpellsFromKeys(array $ids): array
    {
        $spells = [];
        foreach ($ids as $id)
        {
            $spells[] = GameSpell::where('id', $id)->first();
        }

        return $spells;
    }

    private function getDamageTypesFromKeys(array $ids): array
    {
        if (empty(self::$damageTypes)) {
            self::$damageTypes = DamageType::all()->keyBy('id')->toArray();
        }

        $damageValues = [];
        foreach ($ids as $id)
        {
            if (isset(self::$damageTypes[$id])) {
                $damageValues[] = self::$damageTypes[$id];
            }
        }

        return $damageValues;
    }
}
