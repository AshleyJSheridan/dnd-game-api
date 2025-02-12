<?php

namespace App\Services\Items;

use App\Models\CharProficiency;
use App\Models\GameItem;
use Illuminate\Http\Request;

class ArmorItemService implements iItemService
{
    public function __construct(private Request $request)
    {}

    public function getItem(int $rarity)
    {
        $armor = $this->getBaseArmor();

        // 20% chance of magic armor
        if (rand(1, 100) <= 30)
        {
            // as the armor sets with modifiers already exist in the DB (more can be added in the future), just return a more rare set
            $roll = rand(1, 100);

            if ($roll % 4 === 0)
            {
                $armor = $this->getBaseArmor('very rare');
            }
            elseif ($roll % 3 === 0)
            {
                $armor = $this->getBaseArmor('rare');
            }
            elseif ($roll % 2 === 0)
            {
                $armor = $this->getBaseArmor('uncommon');
            }
        }

        return $armor;
    }

    private function getBaseArmor(string $rarity = 'common'): GameItem
    {
        $armorProficiency = $this->request->get('proficiency', '');
        $armor = null;

        if ($armorProficiency !== '')
        {
            $proficiencyIds = CharProficiency::where('type', 'armor')
                ->where('name', 'like', strtolower($armorProficiency) . '%')
                ->pluck('id')->toArray();
            $armor = GameItem::where('type', 'armor')
                ->where('rarity', $rarity)
                ->whereIn('proficiency_id', $proficiencyIds)
                ->inRandomOrder()->first();
        }

        if ($armorProficiency === '' || is_null($armor))
        {
            $armor = GameItem::where('type', 'armor')
                ->where('rarity', $rarity)
                ->inRandomOrder()->first();
        }

        return $armor;
    }
}
