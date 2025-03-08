<?php

namespace App\Http\Controllers;

use App\Http\Resources\CreatureResource;
use App\Models\Character;
use App\Models\GameCreature;
use App\Models\User;
use App\Services\CreatureService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
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

            return CreatureResource::make($encounter['creature'])->additional([
                'amount' => $encounter['amount'],
                'difficulty' => $encounter['difficulty'],
                'partyDifficulty' => $encounter['partyDifficulty'],
            ]);
        } catch (\Exception $e) {
            // TODO do something here, probably means invalid JSON input
            var_dump($e->getMessage());
        }
    }
}
