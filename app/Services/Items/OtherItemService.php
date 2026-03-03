<?php

namespace App\Services\Items;

use App\Models\GameItem;

class OtherItemService extends BaseItemService implements iItemService
{
    public function getRandomItem(): GameItem
    {
        return GameItem::where('type', 'other')
            ->inRandomOrder()->first();
    }

    public function getRandomItemByRarity(string $rarity): GameItem
    {
        return $this->getRandomItemByTypeAndRarity('other', $rarity);
    }
}
