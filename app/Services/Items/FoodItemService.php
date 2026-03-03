<?php

namespace App\Services\Items;

use App\Models\GameItem;

class FoodItemService extends BaseItemService implements iItemService
{
    public function getRandomItem(): GameItem
    {
        return GameItem::where('type', 'food')
            ->inRandomOrder()->first();
    }

    public function getRandomItemByRarity(string $rarity): GameItem
    {
        return $this->getRandomItemByTypeAndRarity('food', $rarity);
    }
}
