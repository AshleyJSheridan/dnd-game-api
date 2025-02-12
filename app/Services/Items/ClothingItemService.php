<?php

namespace App\Services\Items;

use App\Models\GameItem;

class ClothingItemService implements iItemService
{
    public function getItem(int $rarity): GameItem
    {
        return GameItem::where('type', 'clothing')
            ->inRandomOrder()->first();
    }
}
