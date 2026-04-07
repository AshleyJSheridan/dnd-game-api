<?php

namespace App\Services\Items;

use App\Models\GameItem;

class BaseItemService
{
    protected $rarityLevels = ['common', 'uncommon', 'rare', 'very rare', 'legendary'];

    protected function getRarityString(int $rarity, array $rarityTable): string
    {
        $roll = floor($rarity / 5);

        foreach ($rarityTable as $range => $outcome)
        {
            if (strpos($range, "-") !== false)
            {
                list($min, $max) = explode("-", $range);
                if ($roll >= intval($min) && $roll <= intval($max))
                    return $outcome;
            }
            else
            {
                if ($roll == intval($range))
                    return $outcome;
            }
        }

        // default to common
        return reset($rarityTable);
    }

    protected function getRandomItemByTypeAndRarity(string $type, string $rarity)
    {
        // while loop through rarity levels, starting at $rarity until we find an item of the given type
        $currentRarityIndex = array_search($rarity, $this->rarityLevels);
        while ($currentRarityIndex !== -1)
        {
            $currentRarity = $this->rarityLevels[$currentRarityIndex];
            $item = GameItem::where('type', $type)
                ->where('rarity', $currentRarity)
                ->where('generated', 'no')
                ->inRandomOrder()->first();

            if ($item)
                return $item;

            $currentRarityIndex --;
        }
    }
}
