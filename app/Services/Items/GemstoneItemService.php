<?php

namespace App\Services\Items;

use App\Models\GameItem;

class GemstoneItemService extends BaseItemService implements iItemService
{
    public $rarityTable = [
        '1-11' => 'common',
        '12-17' => 'uncommon',
        '18-19' => 'rare',
        '20' => 'very rare',
    ];

    public function getItem(int $rarity): GameItem
    {
        $rarityStr = $this->getRarityString($rarity, $this->rarityTable);

        return GameItem::where('type', 'gemstone')
            ->where('rarity', $rarityStr)
            ->inRandomOrder()->first();
    }
}
