<?php

namespace App\Services\Items;

use App\Models\GameItem;

class PotionItemService implements iItemService
{
    public $rarityTable = [
        '1-11' => 'common',
        '12-17' => 'uncommon',
        '18-19' => 'rare',
        '20' => 'very rare',
    ];

    public function getItem(int $rarity): GameItem
    {
        $rarityStr = $this->getRarityString($rarity);

        return GameItem::where('type', 'potion')
            ->where('rarity', $rarityStr)
            ->inRandomOrder()->first();
    }

    private function getRarityString(int $rarity): string
    {
        $roll = floor($rarity / 5);

        foreach ($this->rarityTable as $range => $outcome)
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
        return reset($this->rarityTable);
    }
}
