<?php

namespace App\Http\Controllers;

use App\Http\Resources\CampaignMapResource;
use App\Http\Resources\CampaignResourceForOwner;
use App\Http\Resources\CampaignResourceForPlayer;
use App\Models\Campaign;
use App\Models\CampaignMap;
use App\Models\CampaignMapCharacterEntity;
use App\Models\Character;
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

        return $campaignMap;
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
            //$campaign = Campaign::where('guid', $campaignGuid)->first();
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

                    break;
                default:
                    // object
            }

            $map = CampaignMap::where('guid', $mapGuid)->first();

            return CampaignMapResource::make($map);

        } catch (\Exception $e) {
            var_dump($e->getMessage());
        }
    }
}
