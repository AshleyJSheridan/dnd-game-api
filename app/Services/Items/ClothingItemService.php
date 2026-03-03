<?php

namespace App\Services\Items;

use App\Models\GameItem;

class ClothingItemService extends BaseItemService implements iItemService
{
    public function getRandomItem(): GameItem
    {
        return GameItem::where('type', 'clothing')
            ->inRandomOrder()->first();
    }

    public function getRandomItemByRarity(string $rarity): GameItem
    {
        return $this->getRandomItemByTypeAndRarity('clothing', $rarity);
    }
}
