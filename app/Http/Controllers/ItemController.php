<?php

namespace App\Http\Controllers;

use App\Http\Factories\ItemFactory;
use App\Http\Resources\CharacterResource;
use App\Http\Resources\CharInventoryItemResource;
use App\Http\Resources\CharStarterPackResource;
use App\Http\Resources\GameItemResource;
use App\Models\Character;
use App\Models\CharInventoryItem;
use App\Models\CharProficiency;
use App\Models\DamageType;
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
            $this->user = JWTAuth::parseToken()->authenticate();
        } catch (JWTException $e) {}
    }

    public function getItems(string $itemType = null)
    {
        if (!$itemType)
            return GameItemResource::collection(GameItem::where('generated', 'no')->get());

        return GameItemResource::collection(GameItem::where('generated', 'no')->where('type', $itemType)->get());
    }

    public function getRandomItem(string $itemType)
    {
        // TODO refactor whatever the fuck I was thinking of here.
        $rarity = rand(1, 100);

        return GameItemResource::make(ItemFactory::create($itemType)->getRandomItem($rarity));
    }

    public function getRandomItemByTypeAndRarity(string $itemType, string $rarity)
    {
        return GameItemResource::make(ItemFactory::create($itemType)->getRandomItemByRarity($rarity));
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
                $inventoryItem = CharInventoryItem::create([
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
                        $bagItem = CharInventoryItem::create([
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
                    $inventoryItem = CharInventoryItem::create([
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
                    $inventoryItem = CharInventoryItem::create([
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

            // todo change the resource, no use returning the whole character object
            return CharacterResource::make(Character::where('guid', $charGuid)->where('user_id', $this->user->id)->first());
        } catch (\Exception $e) {
            //var_dump($e->getMessage());
        }
    }

    public function getPlayerInventory(string $charGuid)
    {
        $character = Character::where('guid', $charGuid)->where('user_id', $this->user->id)->first();
    }

    public function addItemsToPlayerInventory(string $charGuid, Request $request)
    {
        try {
            $character = Character::where('guid', $charGuid)->where('user_id', $this->user->id)->first();
            $jsonData = json_decode($request->getContent());

            $itemToAdd = GameItem::where('id', $jsonData->itemId)->first();
            $inventoryItem = CharInventoryItem::create([
                'guid' => Str::uuid()->toString(),
                'char_id' => $character->id,
                'base_item_id' => $itemToAdd->id,
                'quantity' => $jsonData->quantity,
                'name' => $itemToAdd->name,
                'parent_id' => 0,
                'created_at' => Carbon::now(),
            ]);

            return CharInventoryItemResource::collection($character->Inventory);
        } catch (\Exception $e) {

        }
    }

    public function addCustomItemToPlayerInventory(string $charGuid, Request $request)
    {
        try {
            $character = Character::where('guid', $charGuid)->where('user_id', $this->user->id)->first();
            $jsonData = json_decode($request->getContent());
            $rawItemData = $jsonData->item;

            // If the item is marked as tainted, it means it might be custom. If so, it needs a unique name (against the user) before it can be saved.
            if (isset($rawItemData->tainted) && $rawItemData->tainted === true)
            {
                // Before trying to insert the item, check to see if an item of this same name already exists and return an error if so.
                $existingItem = GameItem::where('name', $rawItemData->name)->where('generated_by', $this->user->id)->first();
                if ($existingItem) {
                    return response()->json(['error' => 'You have already created an item with this name. Change the name and try again.'], Response::HTTP_CONFLICT);
                }

                $proficiency = CharProficiency::where('name', $rawItemData->proficiency ?? '')->first() ?? null;

                $item = GameItem::create([
                    'name' => $rawItemData->name,
                    'base_name' => $rawItemData->base_name,
                    'description' => $rawItemData->description,
                    'cost' => $rawItemData->cost->value,
                    'cost_unit' => $rawItemData->cost->unit,
                    'type' => $rawItemData->type,
                    'weight' => $rawItemData->weight,
                    'rarity' => $rawItemData->rarity,
                    'proficiency_id' => $proficiency ? $proficiency->id : null,
                    'ammo_type' => $rawItemData->weapon_props->ammo_type ?? null,
                    'damage' => $rawItemData->weapon_props->damage->amount ?? null,
                    'damage_type' => $rawItemData->weapon_props->damage->type ?? null,
                    'range_normal' => $rawItemData->weapon_props->range->normal ?? null,
                    'range_long' => $rawItemData->weapon_props->range->long ?? null,
                    'weapon_versatility' => $rawItemData->weapon_props->weapon_versatility ?? null,
                    'armor_props' => $rawItemData->armor_props ? json_encode($rawItemData->armor_props) : null,
                    'special' => $rawItemData->special_properties ? $this->getParsedItemSpecialPropertiesJson($rawItemData->special_properties) : null,
                    'generated' => 'yes',
                    'generated_by' => $this->user->id,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ]);
            }
            else
            {
                // Get the existing item (not user generated) to add to the inventory, but only if it exists. If it doesn't exist, return an error.
                $item = GameItem::where('name', $rawItemData->name)->first();

                if (!$item) {
                    return response()->json(['error' => 'Item with that name not found.'], Response::HTTP_NOT_FOUND);
                }
            }

            // If we got here, we have an item (new or existing) to add to a users inventory.
            $inventoryItem = CharInventoryItem::create([
                'guid' => Str::uuid()->toString(),
                'char_id' => $character->id,
                'base_item_id' => $item->id,
                'quantity' => $jsonData->quantity ?? 1,
                'name' => $item->name,
                'parent_id' => 0,
                'created_at' => Carbon::now(),
            ]);

            return response()->json(['message' => 'Item added to inventory successfully'], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            var_dump($e->getMessage());
        }
    }

    public function getGeneratedItems(Request $request)
    {
        $items = GameItem::where('generated', 'yes')->where('generated_by', $this->user->id)->get();

        if (!$items)
            return response()->json(['error' => 'No generated items found for this user.'], Response::HTTP_NOT_FOUND);

        return GameItemResource::collection($items);
    }

    private function getParsedItemSpecialPropertiesJson($specialProps): string
    {
        $parsedProps = [];

        // TODO refactor this to be tidier.
        if (isset($specialProps->classes))
        {
            $parsedProps['classes'] = [];
            foreach ($specialProps->classes as $class)
            {
                $parsedProps['classes'][] = $class->id;
            }
        }
        if (isset($specialProps->resistances))
        {
            $parsedProps['resistances'] = [];
            foreach ($specialProps->resistances as $resistance)
            {
                $parsedProps['resistances'][] = $resistance->id;
            }
        }
        if (isset($specialProps->charges))
        {
            $parsedProps['charges'] = $specialProps->charges;
        }
        if (isset($specialProps->recharges))
        {
            $parsedProps['recharges'] = $specialProps->recharges;
        }
        if (isset($specialProps->recharge_rate))
        {
            $parsedProps['recharge_rate'] = $specialProps->recharge_rate;
        }
        if (isset($specialProps->spells))
        {
            $parsedProps['spells'] = [];
            foreach ($specialProps->spells as $spell)
            {
                $parsedProps['spells'][] = $spell->id;
            }
        }
        if (isset($specialProps->spell))
        {
            $parsedProps['spell'] = $specialProps->spell;
        }
        if (isset($specialProps->extra_damage))
        {
            $parsedProps['extra_damage'] = $specialProps->extra_damage;
        }
        if (isset($specialProps->damage_type))
        {
            $parsedProps['damage_type'] = $specialProps->damage_type->id;
        }
        if (isset($specialProps->slaying))
        {
            $parsedProps['slaying'] = $specialProps->slaying;
        }
        if (isset($specialProps->creature))
        {
            $parsedProps['creature'] = $specialProps->creature;
        }
        if (isset($specialProps->ability))
        {
            if (is_int($specialProps->ability))
            {
                $parsedProps['effects'] = 'ability_set';
                $parsedProps['ability'] = $specialProps->ability;
                $parsedProps['amount'] = $specialProps->amount;
                $parsedProps['duration'] = $specialProps->duration;
            }
            else if (is_object($specialProps->ability))
            {
                $abilityProps = [
                    'type' => $specialProps->ability->type ?? null,
                    'damage_type' => $specialProps->ability->damage_type->id ?? null,
                    'amount' => $specialProps->ability->amount ?? null,
                ];
                $parsedProps['ability'] = $abilityProps;
            }
        }
        if (isset($specialProps->effects))
        {
            $parsedProps['effects'] = $specialProps->effects;
            $parsedProps['amount'] = $specialProps->amount;
        }

        return json_encode($parsedProps);
    }

    public function updateInventoryItem(string $charGuid, string $itemGuid, Request $request)
    {
        try {
            $character = Character::where('guid', $charGuid)->where('user_id', $this->user->id)->first();
            $inventoryItem = CharInventoryItem::where('char_id', $character->id)->where('guid', $itemGuid)->first();

            $jsonData = json_decode($request->getContent());

            foreach ($jsonData as $property => $value)
            {
                $inventoryItem->{$property} = $value;
            }
            $inventoryItem->save();

            return CharInventoryItemResource::collection($character->Inventory);
        } catch (\Exception $e) {

        }
    }

    public function removeInventoryItem(string $charGuid, string $itemGuid)
    {
        try {
            $character = Character::where('guid', $charGuid)->where('user_id', $this->user->id)->first();
            $inventoryItem = CharInventoryItem::where('char_id', $character->id)->where('guid', $itemGuid)->first();

            $inventoryItem->delete();

            return CharInventoryItemResource::collection($character->Inventory);
        } catch (\Exception $e) {

        }
    }
}
