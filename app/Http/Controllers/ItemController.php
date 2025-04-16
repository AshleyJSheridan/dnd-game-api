<?php

namespace App\Http\Controllers;

use App\Http\Factories\ItemFactory;
use App\Http\Resources\CharacterResource;
use App\Http\Resources\CharStarterPackResource;
use App\Http\Resources\GameItemResource;
use App\Models\Character;
use App\Models\CharInventory;
use App\Models\GameItem;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
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

    public function setStartingEquipment(string $charGuid, Request $request)
    {
        $character = Character::where('guid', $charGuid)->where('user_id', $this->user->id)->first();

        if (!$character)
            return response()->json(['error' => 'Character not found'], Response::HTTP_NOT_FOUND);

        if (!$character->CharacterClass || !$character->CharacterBackground)
            return response()->json(['error' => 'Unable to save initial inventory while class and background are unset'], Response::HTTP_BAD_REQUEST);

        try {
            $jsonData = json_decode($request->getContent());
            $classStartingSetId = $jsonData->selectedClassSet;

            $backgroundEquipment = $character->CharacterBackground->StartingEquipmentPacks->first();
            $classEquipment = $character->CharacterClass->StartingEquipmentPacks->where('id', $classStartingSetId)->first();

            if (!$classEquipment)
                return response()->json(['error' => 'Selected set does not belong to your chosen class'], Response::HTTP_BAD_REQUEST);

            // starter equipment only has starting gold, no other denominations
            $character->money = ['gold' => $backgroundEquipment->gold + $classEquipment->gold];
            $character->save();

            // add all starter items, including those within starter packs
            $allItems = $backgroundEquipment->items->merge($classEquipment->items);
            foreach ($allItems as $item)
            {
                // ignores things like generic artisans tools and musical instrument which will be added specifically after
                if ($item->generic === 'yes')
                    continue;

                // add item to char_inventory table
                $inventoryItem = CharInventory::create([
                    'guid' => Str::uuid()->toString(),
                    'char_id' => $character->id,
                    'base_item_id' => $item->id,
                    'quantity' => $item->pivot->quantity,
                    'name' => $item->name,
                    'parent_id' => 0,
                    'created_at' => Carbon::now(),
                ]);

                if($item->isContainer())
                {
                    foreach ($item->starterItems as $subItem)
                    {
                        $bagItem = CharInventory::create([
                            'guid' => Str::uuid()->toString(),
                            'char_id' => $character->id,
                            'base_item_id' => $subItem->id,
                            'quantity' => $subItem->pivot->quantity,
                            'name' => $subItem->name,
                            'parent_id' => $inventoryItem->id,
                            'created_at' => Carbon::now(),
                        ]);
                    }
                }
            }

            foreach ($jsonData->selectedInstruments as $instrumentId)
            {
                $instrument = GameItem::where('id', $instrumentId)->first();

                if ($instrument)
                {
                    $inventoryItem = CharInventory::create([
                        'guid' => Str::uuid()->toString(),
                        'char_id' => $character->id,
                        'base_item_id' => $instrumentId,
                        'quantity' => 1,
                        'name' => $instrument->name,
                        'parent_id' => 0,
                        'created_at' => Carbon::now(),
                    ]);
                }
            }

            foreach ($jsonData->selectedTools as $toolId)
            {
                $tool = GameItem::where('id', $toolId)->first();

                if ($tool)
                {
                    $inventoryItem = CharInventory::create([
                        'guid' => Str::uuid()->toString(),
                        'char_id' => $character->id,
                        'base_item_id' => $toolId,
                        'quantity' => 1,
                        'name' => $tool->name,
                        'parent_id' => 0,
                        'created_at' => Carbon::now(),
                    ]);
                }
            }

            return CharacterResource::make(Character::where('guid', $charGuid)->where('user_id', $this->user->id)->first());
        } catch (\Exception $e) {
            var_dump($e->getMessage());
        }


        /**
         * iterate through items in selected class and background starting equipment
         * add to char_inventory table, referencing the original item_id
         * return char_inventory response
         */
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
