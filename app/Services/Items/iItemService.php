<?php

namespace App\Services\Items;

use App\Models\GameItem;

interface iItemService
{
    public function getItem(int $rarity): GameItem;
}
