<?php

namespace App\Http\Controllers;

use App\Http\Factories\ItemFactory;
use App\Http\Resources\GameItemResource;
use App\Models\GameItem;

class ItemController extends Controller
{
    public function getItems(string $itemType = null)
    {
        if (!$itemType)
            return GameItemResource::collection(GameItem::where('generated', 'no')->get());

        return GameItemResource::collection(GameItem::where('generated', 'no')->where('type', $itemType)->get());
    }

    public function getRandomItem(string $itemType)
    {
        $rarity = rand(1, 100);
        /*for ($i = 65; $i < 100; $i ++)
        {
            $item = ItemFactory::create($itemType)->getItem($i);
        }*/

        $item = ItemFactory::create($itemType)->getItem($rarity);

    }
}
