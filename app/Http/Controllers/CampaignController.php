<?php

namespace App\Http\Controllers;

use App\Http\Resources\CampaignMapResource;
use App\Http\Resources\CampaignResourceForOwner;
use App\Http\Resources\CampaignResourceForPlayer;
use App\Models\Campaign;
use App\Models\CampaignMap;
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

        Image::useImageDriver(ImageDriver::Gd)
            ->loadFile(storage_path('images/' . $imageName))
            ->resize($width, $height)
            ->save(storage_path('thumbs/') . $imageName);
        Image::useImageDriver(ImageDriver::Gd)
            ->loadFile(storage_path('images/' . $imageName))
            ->save(storage_path('images/' . $imageName));

        $campaignMap = CampaignMap::create([
            'guid' => Str::uuid()->toString(),
            'name' => $request->input('name'),
            'description' => $request->input('description'),
            'image' => $imageName,
            'game_id' => $campaign->id,
            'created_at' => Carbon::now(),
            'width' => $width,
            'height' => $height,
        ]);

        return CampaignMapResource::make($campaignMap);
    }

    public function getMap(string $guid)
    {
        $campaignMap = CampaignMap::where('guid', $guid)->first();

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

    public function updateMap(string $guid, Request $request)
    {
        $allowedUpdates = ['show_grid', 'grid_size', 'grid_colour'];
        $data = [];
        $campaignMap = CampaignMap::where('guid', $guid)->first();

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

            if (!$campaign->Characters->contains($character->id))
            {
                $campaign->Characters()->attach($character->id);

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
}
