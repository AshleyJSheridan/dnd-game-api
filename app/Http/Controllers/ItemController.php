<?php

namespace App\Http\Controllers;

use App\Http\Resources\GameItemResource;
use App\Models\GameItem;
use Illuminate\Http\Request;

class ItemController extends Controller
{
    public function getItems(string $itemType = null)
    {
        if (!$itemType)
            return GameItemResource::collection(GameItem::all());

        return GameItemResource::collection(GameItem::where('type', $itemType)->get());
    }
}
