<?php

namespace App\Http\Controllers;

use App\Http\Factories\ItemFactory;
use App\Http\Resources\CharStarterPackResource;
use App\Http\Resources\GameItemResource;
use App\Models\Character;
use App\Models\GameItem;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;

class ItemController extends Controller
{
    private User $user;
    public function __construct()
    {
        try {
            if (! $this->user = JWTAuth::parseToken()->authenticate())
                return response()->json(['error' => 'User not found'], Response::HTTP_NOT_FOUND);
        } catch (JWTException $e) {
            return response()->json(['error' => 'Invalid token'], Response::HTTP_BAD_REQUEST);
        }
    }

    public function getItems(string $itemType = null)
    {
        if (!$itemType)
            return GameItemResource::collection(GameItem::where('generated', 'no')->get());

        return GameItemResource::collection(GameItem::where('generated', 'no')->where('type', $itemType)->get());
    }

    public function getRandomItem(string $itemType = 'book')
    {
        $rarity = rand(1, 100);

        return GameItemResource::make(ItemFactory::create($itemType)->getItem($rarity));
    }

    public function getStartingEquipment(string $guid)
    {
        $character = Character::where('guid', $guid)->where('user_id', $this->user->id)->first();

        // no point showing starting equipment if no class or background has been selected
        if (!$character->CharacterClass || !$character->CharacterBackground)
            return CharStarterPackResource::collection([]);

        $backgroundEquipment = $character->CharacterBackground->StartingEquipmentPacks;
        $classEquipment = $character->CharacterClass->StartingEquipmentPacks;

        return CharStarterPackResource::collection($backgroundEquipment->merge($classEquipment));
    }

    public function getPlayerInventory(string $charGuid)
    {
        $character = Character::where('guid', $charGuid)->where('user_id', $this->user->id)->first();
    }

    public function addItemsToPlayerInventory(string $charGuid, Request $request)
    {
        $character = Character::where('guid', $charGuid)->where('user_id', $this->user->id)->first();
    }

    public function removeItemsFromPlayerInventory(string $charGuid, Request $request)
    {
        $character = Character::where('guid', $charGuid)->where('user_id', $this->user->id)->first();
    }
}
