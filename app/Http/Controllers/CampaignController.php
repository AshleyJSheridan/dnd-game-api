<?php

namespace App\Http\Controllers;

use App\Http\Resources\CampaignMapResource;
use App\Http\Resources\CampaignResourceForOwner;
use App\Http\Resources\CampaignResourceForPlayer;
use App\Models\Campaign;
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

class CampaignController extends Controller
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

        if ($campaign->user_id === $this->user->id)
            return CampaignResourceForOwner::make($campaign);
        else
            return CampaignResourceForPlayer::make($campaign);
    }

    public function createMap(string $guid, Request $request)
    {
        $campaign = Campaign::where('guid', $guid)->first();
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
        $campaignMap = CampaignMap::where('guid', $mapGuid)->first();

        return CampaignMapResource::make($campaignMap);
    }

    public function getMapImage(string $guid)
    {
        $campaignMap = CampaignMap::where('guid', $guid)->first();

        return response()->file(storage_path('images/' . $campaignMap->image));
    }

    public function getMapThumb(string $guid)
    {
        $campaignMap = CampaignMap::where('guid', $guid)->first();

        return response()->file(storage_path('thumbs/' . $campaignMap->image));
    }

    public function updateMap(string $campaignGuid, string $mapGuid, Request $request)
    {
        $allowedUpdates = ['show_grid', 'grid_size', 'grid_colour'];
        $data = [];
        $campaignMap = CampaignMap::where('guid', $mapGuid)->first();

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
            $campaign->Characters()->detach($character->id);

            $campaign->save();

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
            var_dump($e->getMessage());
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
                    $entity->update([
                        'x' => $jsonData->x ?? $entity->x,
                        'y' => $jsonData->y ?? $entity->y,
                        'highlight_colour' => $jsonData->highlight_colour ?? $entity->highlight_colour,
                    ]);
                    $entity->save();
                    break;
                case 'creature':
                    $entity = CampaignMapCreatureEntity::where('guid', $entityGuid)->where('map_id', $map->id)->first();
                    $entity->update([
                        'x' => $jsonData->x ?? $entity->x,
                        'y' => $jsonData->y ?? $entity->y,
                        'highlight_colour' => $jsonData->highlight_colour ?? $entity->highlight_colour,
                        'entity_name' => $jsonData->entity_name ?? $entity->entity_name,
                    ]);
                    $entity->save();
                    break;
                case 'drawing':
                    $entity = CampaignMapDrawingEntity::where('guid', $entityGuid)->where('map_id', $map->id)->first();
                    $entity->update([
                        'x' => $jsonData->x ?? $entity->x,
                        'y' => $jsonData->y ?? $entity->y,
                        'highlight_colour' => $jsonData->highlight_colour ?? $entity->highlight_colour,
                    ]);
                    break;
            }

            return CampaignMapResource::make(CampaignMap::where('guid', $mapGuid)->first());
        } catch (\Exception $e) {
            var_dump($e->getMessage());
        }
    }

    public function deleteMapEntity(string $campaignGuid, string $mapGuid, string $entityGuid, Request $request)
    {
        $rawEntity = CampaignMap::where('guid', $mapGuid)->first()->RawEntities()->where('guid', $entityGuid)->first();
        // TODO possibly make a raw entity model to allow delete() method instead of setting the date manually?
        $rawEntity->deleted_at = Carbon::now();
        $rawEntity->save();

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
                $campaign->{$key} = $value;
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
