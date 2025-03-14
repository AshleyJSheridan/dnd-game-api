<?php

namespace App\Http\Controllers;

use App\Http\Resources\CreatureResource;
use App\Http\Resources\EncounterResource;
use App\Models\Character;
use App\Models\GameEncounter;
use App\Models\GameCreature;
use App\Models\User;
use App\Services\CreatureService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;

class EncountersController extends Controller
{
    private User $user;

    public function __construct(private CreatureService $creatureService)
    {
        try {
            if (! $this->user = JWTAuth::parseToken()->authenticate())
                return response()->json(['error' => 'User not found'], Response::HTTP_NOT_FOUND);
        } catch (JWTException $e) {
            return response()->json(['error' => 'Invalid token'], Response::HTTP_BAD_REQUEST);
        }
    }

    public function createEncounter(Request $request)
    {
        try {
            $jsonData = json_decode($request->getContent());

            if (is_null($jsonData) || empty($jsonData->characters))
                return response()->json(['error' => 'No characters specified'], Response::HTTP_BAD_REQUEST);

            // TODO better validation on these inputs
            $charLevels = Character::whereIn('guid', $jsonData->characters)->where('user_id', $this->user->id)->pluck('level')->toArray();
            $difficulty = $jsonData->difficulty ?? 1;
            $environment = $jsonData->environment ?? 'forest';

            $encounter = $this->creatureService->createEncounter($charLevels, $difficulty, $environment);
            if (!$encounter)
            {
                // hanky panky with the inputs gives players a Tarasque
                return CreatureResource::make(GameCreature::where('name', 'Tarasque')->first())->additional([
                    'amount' => 1,
                    'difficulty' => 155000,
                    'partyDifficulty' => 155000,
                ]);
            }

            $creatureEncounter = GameEncounter::create([
                'guid' => Str::uuid()->toString(),
                'type' => 'creature',
                'description' => '',
                'difficulty' => $difficulty,
                'party_difficulty' => $encounter['partyDifficulty'],
                'environment' => $environment,
                'created_at' => Carbon::now(),
            ]);
            // add the creatures for the encounter
            foreach ($encounter['creatures'] as $creature)
            {
                $creatureEncounter->Creatures()->create([
                    'guid' => Str::uuid()->toString(),
                    'creature_id' => $creature->id,
                    'unique_name' => '',
                    'max_hp' => $creature->hp,
                    'current_hp' => $creature->hp,
                    'created_at' => Carbon::now(),
                ]);
            }
            $creatureEncounter->save();

            return EncounterResource::make($creatureEncounter);
        } catch (\Exception $e) {
            // TODO do something here, probably means invalid JSON input
            return response()->json(['error' => 'Bad Request'], Response::HTTP_BAD_REQUEST);
        }
    }

    public function getEncounterByGuid(string $guid)
    {
        return EncounterResource::make(GameEncounter::where('guid', $guid)->first());
    }
}
