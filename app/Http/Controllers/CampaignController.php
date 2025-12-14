<?php

namespace App\Http\Controllers;

use App\Http\Resources\CampaignLoreGroupResource;
use App\Http\Resources\CampaignLoreResource;
use App\Http\Resources\CampaignMapResource;
use App\Http\Resources\CampaignResourceForOwner;
use App\Http\Resources\CampaignResourceForPlayer;
use App\Models\Campaign;
use App\Models\CampaignLore;
use App\Models\CampaignLoreGroup;
use App\Models\CampaignMap;
use App\Models\CampaignMapCharacterEntity;
use App\Models\CampaignMapCreatureEntity;
use App\Models\CampaignMapDrawingEntity;
use App\Models\Character;
use App\Models\GameCreature;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Spatie\Image\Enums\ImageDriver;
use Spatie\Image\Image;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;
use Parsedown;

class CampaignController extends Controller
{
    private User $user;
    private array $defaultLoreGroups = [
        'Locations',
        'Organizations',
        'NPCs',
        'Events',
        'History',
        'Religions',
    ];

    public function __construct()
    {
        try {
            $this->user = JWTAuth::parseToken()->authenticate();
        } catch (JWTException $e) {}
    }

    public function getCampaigns()
    {
        return CampaignResourceForOwner::collection(Campaign::where('user_id', $this->user->id)->get());
    }

    public function createCampaign(Request $request)
    {
        try {
            $jsonData = json_decode($request->getContent());

            $campaign = Campaign::create([
                'guid' => Str::uuid()->toString(),
                'name' => $jsonData->name,
                'description' => $jsonData->description,
                'user_id' => $this->user->id,
                'created_at' => Carbon::now(),
                'state' => 'paused',
            ]);

            return CampaignResourceForOwner::make($campaign);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Bad Request'], Response::HTTP_BAD_REQUEST);
        }
    }

    public function getCampaign(string $guid)
    {
        // no user check here, as we want campaigns to be shared to other users by the guid to facilitate a multiplayer game
        $campaign = Campaign::where('guid', $guid)->first();
        if (is_null($campaign))
            return response()->json(['error' => 'Campaign not found'], Response::HTTP_NOT_FOUND);

        if ($campaign->user_id === $this->user->id)
            return CampaignResourceForOwner::make($campaign);
        else
            return CampaignResourceForPlayer::make($campaign);
    }

    public function getLoreGroups()
    {
        $allLoreGroups = $this->defaultLoreGroups;
        $userLoreGroups = CampaignLoreGroup::where('user_id', $this->user->id)->get();
        foreach ($userLoreGroups as $userLoreGroup) {
            array_push($allLoreGroups, $userLoreGroup->name);
        }

        return CampaignLoreGroupResource::make(collect($allLoreGroups));
    }

    public function getCampaignLoreItem(string $guid, string $loreGuid)
    {
        $campaign = Campaign::where('guid', $guid)->first();
        if (!$campaign)
            return response()->json(['error' => 'Campaign not found'], Response::HTTP_NOT_FOUND);

        $loreItem = CampaignLore::where('guid', $loreGuid)
            ->where('game_id', $campaign->id)
            ->first();
        if (!$loreItem)
            return response()->json(['error' => 'Lore item not found'], Response::HTTP_NOT_FOUND);

        if (!$loreItem->file)
        {
            return response()->json(['error' => 'Lore item is not a file'], Response::HTTP_BAD_REQUEST);
        }

        $filePath = storage_path('lore_files/' . $loreItem->file);

        if (!file_exists($filePath))
            return response()->json(['error' => 'Image file not found'], Response::HTTP_NOT_FOUND);

        // TODO check to see if returning a large PDF file needs special handling.
        return response()->file($filePath);
    }

    public function deleteCampaignLoreItem(string $guid, string $loreGuid)
    {
        $campaign = Campaign::where('guid', $guid)->first();
        if (!$campaign)
            return response()->json(['error' => 'Campaign not found'], Response::HTTP_NOT_FOUND);

        $loreItem = CampaignLore::where('guid', $loreGuid)
            ->where('game_id', $campaign->id)
            ->first();
        if (!$loreItem)
            return response()->json(['error' => 'Lore item not found'], Response::HTTP_NOT_FOUND);

        $loreItem->delete();

        return CampaignLoreResource::collection($campaign->lore);
    }

    public function editCampaignLoreItem(string $guid, string $loreGuid, Request $request)
    {
        $campaign = Campaign::where('guid', $guid)->first();
        if (!$campaign)
            return response()->json(['error' => 'Campaign not found'], Response::HTTP_NOT_FOUND);

        $loreItem = CampaignLore::where('guid', $loreGuid)
            ->where('game_id', $campaign->id)
            ->first();
        if (!$loreItem)
            return response()->json(['error' => 'Lore item not found'], Response::HTTP_NOT_FOUND);

        if ($request->input('content')) {
            $parsedown = new Parsedown();
            $parsedContent = $parsedown->text($request->input('content'));

            $loreItem->raw_content = $request->input('content');
            $loreItem->parsed_content = $parsedContent;
            $loreItem->save();
        }

        return CampaignLoreResource::collection($campaign->lore);
    }

    public function createCampaignLore(string $guid, Request $request)
    {
        $campaign = Campaign::where('guid', $guid)->first();
        if (!$campaign)
            return response()->json(['error' => 'Campaign not found'], Response::HTTP_NOT_FOUND);
        if ($campaign->user_id !== $this->user->id)
            return response()->json(['error' => 'Not your campaign'], Response::HTTP_UNAUTHORIZED);

        $width = 200;
        $height = 200;

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:250'],
            'type' => ['required', 'in:text,link,file'],
            'file' => [
                'exclude_unless:type,file',
                'required',
                'file',
                'mimetypes:image/jpeg,image/png,image/gif,image/webp,application/pdf',
                'max:20480',
            ],
            'url' => [
                'exclude_unless:type,link',
                'required',
                'url',
            ],
            'content' => [
                'exclude_unless:type,text',
                'required',
                'string',
            ],
            'group' => ['required', 'string'],
            'hide' => ['sometimes', 'string'],
        ]);

        $fileIsImage = false;
        $fileName = null;
        if ($validated['type'] === 'file' && $request->hasFile('file'))
        {
            if (request()->file->getClientMimeType() == 'application/pdf') {
                $fileName = Str::uuid()->toString() . '.' . request()->file->getClientOriginalExtension();
                request()->file->move(storage_path('lore_files'), $fileName);
            }
            else
            {
                $fileIsImage = true;
                $fileName = Str::uuid()->toString() . '.' . request()->file->getClientOriginalExtension();
                request()->file->move(storage_path('lore_files'), $fileName);

                $image = Image::useImageDriver(ImageDriver::Gd)
                    ->loadFile(storage_path('lore_files/' . $fileName));

                $image->save(storage_path('lore_files/' . $fileName));
                $image->resize($width, $height)
                    ->save(storage_path('lore_thumbs/') . $fileName);

            }
        }

        $loreGroupId = $this->getLoreGroupId($validated['group']);

        $parsedContent = '';
        if (strlen($validated['content'])) {
            $parsedown = new Parsedown();
            $parsedContent = $parsedown->text($validated['content']);
        }

        $item = CampaignLore::create([
            'guid' => Str::uuid()->toString(),
            'game_id' => $campaign->id,
            'name' => $validated['name'],
            'type' => $validated['type'],
            'created_at' => Carbon::now(),
            'raw_content' => $validated['content'] ?? null,
            'parsed_content' => $parsedContent,
            'url' => $validated['url'] ?? null,
            'file' => $fileName,
            'is_image'=> $fileIsImage,
            'player_visible' => empty($validated['hide']) ? 1 : 0,
            'lore_group' => $loreGroupId,
        ]);

        return CampaignLoreResource::make($item);
    }

    public function getCampaignLoreThumb(string $guid, string $loreGuid)
    {
        $campaign = Campaign::where('guid', $guid)->first();
        if (!$campaign)
            return response()->json(['error' => 'Campaign not found'], Response::HTTP_NOT_FOUND);

        $loreItem = CampaignLore::where('guid', $loreGuid)
            ->where('game_id', $campaign->id)
            ->first();
        if (!$loreItem)
            return response()->json(['error' => 'Lore item not found'], Response::HTTP_NOT_FOUND);

        $imagePath = storage_path('lore_thumbs/' . $loreItem->file);

        if (! file_exists($imagePath))
            return response()->json(['error' => 'Image file not found'], Response::HTTP_NOT_FOUND);

        return response()->file($imagePath);
    }

    private function getLoreGroupId(string $groupName): ?int
    {
        $loreGroup = CampaignLoreGroup::where('name', $groupName)
            ->where('user_id', $this->user->id)
            ->first();

        if (!$loreGroup)
        {
            $loreGroup = CampaignLoreGroup::create([
                'name' => $groupName,
                'user_id' => $this->user->id,
                'created_at' => Carbon::now(),
            ]);
        }

        return $loreGroup->id;
    }

    public function createMap(string $guid, Request $request)
    {
        $campaign = Campaign::where('guid', $guid)->first();
        if (!$campaign)
            return response()->json(['error' => 'Campaign not found'], Response::HTTP_NOT_FOUND);
        if ($campaign->user_id !== $this->user->id)
            return response()->json(['error' => 'Not your campaign'], Response::HTTP_UNAUTHORIZED);

        $width = 200;
        $height = 200;

        request()->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        $imageName = Str::uuid()->toString() . '.' . request()->image->getClientOriginalExtension();
        request()->image->move(storage_path('images'), $imageName);

        $image = Image::useImageDriver(ImageDriver::Gd)
            ->loadFile(storage_path('images/' . $imageName));

        $image->save(storage_path('images/' . $imageName));
        $image->resize($width, $height)
            ->save(storage_path('thumbs/') . $imageName);

        $campaignMap = CampaignMap::create([
            'guid' => Str::uuid()->toString(),
            'name' => $request->input('name'),
            'description' => $request->input('description'),
            'image' => $imageName,
            'game_id' => $campaign->id,
            'created_at' => Carbon::now(),
            'width' => $image->getWidth(),
            'height' => $image->getHeight(),
        ]);

        return CampaignMapResource::make($campaignMap);
    }

    public function getMap(string $campaignGuid, string $mapGuid)
    {
        $campaignMap = CampaignMap::where('guid', $mapGuid)
            ->whereHas('Campaign', function ($query) use ($campaignGuid) {
                $query->where('guid', $campaignGuid);
            })
            ->first();

        if (!$campaignMap)
            return response()->json(['error' => 'Campaign map not found'], Response::HTTP_NOT_FOUND);

        return CampaignMapResource::make($campaignMap);
    }

    public function getMapImage(string $guid)
    {
        $campaignMap = CampaignMap::where('guid', $guid)->first();
        if (!$campaignMap)
            return response()->json(['error' => 'Campaign map not found'], Response::HTTP_NOT_FOUND);

        $imagePath = storage_path('images/' . $campaignMap->image);

        if (! file_exists($imagePath))
            return response()->json(['error' => 'Image file not found'], Response::HTTP_NOT_FOUND);

        return response()->file($imagePath);
    }

    public function getMapThumb(string $guid)
    {
        $campaignMap = CampaignMap::where('guid', $guid)->first();
        if (!$campaignMap)
            return response()->json(['error' => 'Campaign map not found'], Response::HTTP_NOT_FOUND);

        $imagePath = storage_path('thumbs/' . $campaignMap->image);

        if (! file_exists($imagePath))
            return response()->json(['error' => 'Image file not found'], Response::HTTP_NOT_FOUND);

        return response()->file($imagePath);
    }

    public function updateMap(string $campaignGuid, string $mapGuid, Request $request)
    {
        $allowedUpdates = ['show_grid', 'grid_size', 'grid_colour'];
        $data = [];
        $campaignMap = CampaignMap::where('guid', $mapGuid)
            ->whereHas('Campaign', function ($query) use ($campaignGuid) {
                $query->where('guid', $campaignGuid);
            })
            ->first();

        if (!$campaignMap)
            return response()->json(['error' => 'Campaign map not found'], Response::HTTP_NOT_FOUND);

        // only allow certain fields to be updated
        $jsonData = json_decode($request->getContent());
        foreach ($jsonData as $key => $value) {
            if (in_array($key, $allowedUpdates)) {
                $data[$key] = $value;
            }
        }
        $campaignMap->update($data);

        return CampaignMapResource::make($campaignMap);
    }

    public function addCharacterToCampaign(string $campaignGuid, Request $request)
    {
        try {
            $campaign = Campaign::where('guid', $campaignGuid)->first();
            if (!$campaign)
                return response()->json(['error' => 'Campaign not found'], Response::HTTP_NOT_FOUND);

            $jsonData = json_decode($request->getContent());
            $character = Character::where('guid', $jsonData->character_guid)->first();

            $campaign->Characters()->attach($character->id);
            $campaign->save();

            if ($campaign->user_id === $this->user->id)
                return CampaignResourceForOwner::make($campaign);
            else
                return CampaignResourceForPlayer::make($campaign);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Bad Request'], Response::HTTP_BAD_REQUEST);
        }
    }

    public function removeCharacterFromCampaign(string $campaignGuid, string $charGuid)
    {
        try {
            $campaign = Campaign::where('guid', $campaignGuid)->first();
            $character = Character::where('guid', $charGuid)->first();

            if (! $campaign || ! $character)
                throw new \Exception("Invalid campaign or character");

            $userCharGuids = Character::where('user_id', $this->user->id)->pluck('guid')->toArray();

            // owner can remove any player, individual player can only remove themselves and not others
            if ($campaign->user_id === $this->user->id || in_array($charGuid, $userCharGuids))
            {
                $campaign->Characters()->detach($character->id);
                $campaign->save();
            }

            if ($campaign->user_id === $this->user->id)
                return CampaignResourceForOwner::make($campaign);
            else
                return CampaignResourceForPlayer::make($campaign);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Bad Request'], Response::HTTP_BAD_REQUEST);
        }
    }

    public function addEntityToMap(string $campaignGuid, string $mapGuid, Request $request)
    {
        try {
            $campaign = Campaign::where('guid', $campaignGuid)->first();
            $map = CampaignMap::where('guid', $mapGuid)->first();
            $jsonData = json_decode($request->getContent());

            switch ($jsonData->type) {
                case 'character':
                    $character = Character::where('guid', $jsonData->linked_id)->first();

                    // only allow a character to be added to each map once
                    if (is_null($map->Players->where('linked_id', $character->id)->first())) {
                        $player = CampaignMapCharacterEntity::create([
                            'guid' => Str::uuid()->toString(),
                            'map_id' => $map->id,
                            'linked_id' => $character->id,
                            'x' => $jsonData->x,
                            'y' => $jsonData->y,
                            'created_at' => Carbon::now(),
                        ]);
                    }

                    break;
                case 'creature':
                    $baseCreature = GameCreature::where('id', $jsonData->linked_id)->first();
                    // create some new stats for the creature from the base
                    $numberOfDice = $baseCreature->hit_points_dice;
                    $diceSides = $baseCreature->hit_points_dice_sides;
                    $additionalHitPoints = $baseCreature->hit_point_additional;
                    $hitPoints = $this->getCreatureHp($numberOfDice, $diceSides, $additionalHitPoints);

                    $mapCreature = CampaignMapCreatureEntity::create([
                        'guid' => Str::uuid()->toString(),
                        'map_id' => $map->id,
                        'type' => 'creature',
                        'entity_name' => $baseCreature->name,
                        'linked_id' => $baseCreature->id,
                        'x' => $jsonData->x,
                        'y' => $jsonData->y,
                        'created_at' => Carbon::now(),
                        'stats' => json_encode(['hp' => $hitPoints, 'max_hp' => $hitPoints]),
                    ]);

                    break;
                case 'drawing':
                    $mapDrawing = CampaignMapDrawingEntity::create([
                        'guid' => Str::uuid()->toString(),
                        'map_id' => $map->id,
                        'type' => 'drawing',
                        'linked_id' => 0,
                        'x' => $jsonData->startX,
                        'y' => $jsonData->startY,
                        'orientation' => $jsonData->orientation ?? 0,
                        'highlight_colour' => $jsonData->colour ?? '#000000',
                        'stats' => json_encode([
                            'type' => $jsonData->shape ?? '',
                            'r' => intval($jsonData->distance ?? 0),
                            'width' => intval($jsonData->width ?? 0),
                            'height' => intval($jsonData->height ?? 0),
                            'angle' => intval($jsonData->angle ?? 0),
                            'length' => intval($jsonData->distance ?? 0),
                            'pattern' => $jsonData->fillSymbol ?? 'none',
                        ]),
                        'created_at' => Carbon::now(),
                    ]);

                    break;
                default:
                    // object
            }

            return CampaignMapResource::make(CampaignMap::where('guid', $mapGuid)->first());

        } catch (\Exception $e) {
            //var_dump($e->getMessage());
        }
    }

    public function updateMapEntity(string $campaignGuid, string $mapGuid, string $entityGuid, Request $request)
    {
        try {
            $map = CampaignMap::where('guid', $mapGuid)->first();
            $jsonData = json_decode($request->getContent());

            // TODO refactor this to avoid repetition
            switch ($jsonData->type) {
                case 'character':
                    $entity = CampaignMapCharacterEntity::where('guid', $entityGuid)->where('map_id', $map->id)->first();

                    if ($entity)
                    {
                        $entity->update([
                            'x' => $jsonData->x ?? $entity->x,
                            'y' => $jsonData->y ?? $entity->y,
                            'highlight_colour' => $jsonData->highlight_colour ?? $entity->highlight_colour,
                        ]);
                        $entity->save();
                    }
                    break;
                case 'creature':
                    $entity = CampaignMapCreatureEntity::where('guid', $entityGuid)->where('map_id', $map->id)->first();

                    if ($entity)
                    {
                        $entity->update([
                            'x' => $jsonData->x ?? $entity->x,
                            'y' => $jsonData->y ?? $entity->y,
                            'highlight_colour' => $jsonData->highlight_colour ?? $entity->highlight_colour,
                            'entity_name' => $jsonData->entity_name ?? $entity->entity_name,
                        ]);
                        $entity->save();
                    }
                    break;
                case 'drawing':
                    $entity = CampaignMapDrawingEntity::where('guid', $entityGuid)->where('map_id', $map->id)->first();

                    if ($entity)
                    {
                        $entity->update([
                            'x' => $jsonData->x ?? $entity->x,
                            'y' => $jsonData->y ?? $entity->y,
                            'highlight_colour' => $jsonData->highlight_colour ?? $entity->highlight_colour,
                        ]);
                    }
                    break;
            }

            return CampaignMapResource::make(CampaignMap::where('guid', $mapGuid)->first());
        } catch (\Exception $e) {
            //var_dump($e->getMessage());
        }
    }

    public function deleteMapEntity(string $campaignGuid, string $mapGuid, string $entityGuid, Request $request)
    {
        $map = CampaignMap::where('guid', $mapGuid)->first();
        if (! $map)
            return response()->json(['error' => 'Campaign map not found'], 404);

        $rawEntity = $map->RawEntities()->where('guid', $entityGuid)->first();
        if ($rawEntity)
        {
            // TODO possibly make a raw entity model to allow delete() method instead of setting the date manually?
            $rawEntity->deleted_at = Carbon::now();
            $rawEntity->save();
        }

        return CampaignMapResource::make(CampaignMap::where('guid', $mapGuid)->first());
    }

    public function updateCampaign(string $campaignGuid, Request $request)
    {
        try {
            $campaign = Campaign::where('guid', $campaignGuid)->first();
            if ($campaign->user_id !== $this->user->id)
                return response()->json(['error' => 'Not your campaign'], Response::HTTP_UNAUTHORIZED);

            $jsonData = json_decode($request->getContent());

            foreach ($jsonData as $key => $value)
            {
                // Only allow updating of specific campaign fields.
                if (in_array($key, ['name', 'description', 'state']))
                {
                    $campaign->{$key} = $value;
                }
            }

            $campaign->save();
        } catch (\Exception $e) {
            return response()->json(['error' => 'Bad Request'], Response::HTTP_BAD_REQUEST);
        }

        return CampaignResourceForOwner::make($campaign);
    }

    // TODO move this to a helper or something
    private function getCreatureHp(int $diceAmount, string $sides, int $additionalFixedValue): int
    {
        $hp = $additionalFixedValue;
        $sides = intval(substr($sides, 1));

        for ($i = 0; $i < $diceAmount; $i++)
        {
            $hp += rand(1, $sides);
        }

        // force the returned value to be a minimum of 1, as some creatures have a negative additional hp
        return max(1, $hp);
    }
}
