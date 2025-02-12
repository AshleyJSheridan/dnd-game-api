<?php

namespace App\Services\Items;

use App\Models\GameItem;

class FoodItemService implements iItemService
{
    public function getItem(int $rarity): GameItem
    {
        return GameItem::where('type', 'food')
            ->inRandomOrder()->first();
    }
}
