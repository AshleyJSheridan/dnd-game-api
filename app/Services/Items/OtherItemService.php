<?php

namespace App\Services\Items;

use App\Models\GameItem;

class OtherItemService implements iItemService
{
    public function getItem(int $rarity): GameItem
    {
        return GameItem::where('type', 'other')
            ->inRandomOrder()->first();
    }
}
