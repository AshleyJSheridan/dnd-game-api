<?php

namespace App\Services\Items;

use App\Models\GameItem;

interface iItemService
{
    public function getRandomItem(): GameItem;

    public function getRandomItemByRarity(string $rarity): GameItem;
}
