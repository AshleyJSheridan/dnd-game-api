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
use Illuminate\Support\Facades\Validator;
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
            $jsonData = json_decode($request->getContent(), true);

            $rules = [
                'characters' => 'array|min:1',
                'characters.*' => 'string|exists:characters,guid',
                'levels' => 'array|min:1',
                'levels.*' => 'integer|min:1|max:20',
                'difficulty' => 'integer|min:1|max:4',
                'environment' => 'string|in:arctic,coast,desert,forest,grassland,hill,mountain,swamp,underdark,underwater,urban',
            ];
            $validator = Validator::make($jsonData, $rules);

            if (!$validator->passes()) {
                return response()->json(['error' => 'Invalid input', 'details' => $validator->errors()], Response::HTTP_BAD_REQUEST);
            }

            if (!isset($jsonData['characters']) && !isset($jsonData['levels']))
            {
                return response()->json(['error' => 'Either characters or levels must be provided'], Response::HTTP_BAD_REQUEST);
            }

            $charLevels = [1];
            if (!empty($jsonData['characters']))
            {
                $charLevels = Character::whereIn('guid', $jsonData['characters'])->where('user_id', $this->user->id)->pluck('level')->toArray();
            }
            elseif (!empty($jsonData['levels']))
            {
                $charLevels = $jsonData['levels'];
            }
            $difficulty = $jsonData['difficulty'] ?? 1;
            $environment = $jsonData['environment'] ?? 'forest';

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

            // create the encounter
            $creatureEncounter = GameEncounter::create([
                'guid' => Str::uuid()->toString(),
                'user_id' => $this->user->id,
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
                    'unique_name' => $creature->name,
                    'max_hp' => $creature->hp,
                    'current_hp' => $creature->hp,
                    'created_at' => Carbon::now(),
                ]);
            }
            $creatureEncounter->save();

            return EncounterResource::make($creatureEncounter);
        } catch (\Exception $e) {
            // TODO do something here, probably means invalid JSON input
            return response()->json(['error' => 'Bad Request' . $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    public function getEncounterByGuid(string $guid)
    {
        $encounter = GameEncounter::where('guid', $guid)->where('user_id', $this->user->id)->first();

        if (! $encounter)
            return response()->json(['error' => 'Encounter not found'], 404);

        return EncounterResource::make($encounter);
    }

    public function deleteEncounter(string $guid)
    {
        $encounter = GameEncounter::where('guid', $guid)->where('user_id', $this->user->id)->first();

        if (! $encounter)
            return response()->json(['error' => 'Encounter not found'], 404);

        $encounter->delete();

        return response()->json(['message' => 'Encounter deleted successfully'], 200);
    }

    public function getUserEncounters()
    {
        // Get only encounters where the user is owner and at least one creature has a current HP greater than 0 (i.e. the encounter is still active).
        $encounters = GameEncounter::where('user_id', $this->user->id)->whereHas('Creatures', function ($query) {;
            $query->where('current_hp', '>', 0);
        })->get();

        return EncounterResource::collection($encounters);
    }

    public function updateEncounter(Request $request, string $encounterGuid)
    {
        $encounter = GameEncounter::where('guid', $encounterGuid)->where('user_id', $this->user->id)->first();
        if (! $encounter)
            return response()->json(['error' => 'Encounter not found'], 404);

        try {
            $jsonData = json_decode($request->getContent(), true);
            $rules = [
                'encounter_name' => 'string|min:1',
            ];
            $validator = Validator::make($jsonData, $rules);

            if (!$validator->passes())
                return response()->json(['error' => 'Invalid input', 'details' => $validator->errors()], Response::HTTP_BAD_REQUEST);

            $encounter->description = $jsonData['encounter_name'];
            $encounter->save();

            return EncounterResource::make($encounter);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Bad Request'], Response::HTTP_BAD_REQUEST);
        }
    }

    public function updateEncounterCreature(Request $request, string $encounterGuid, string $creatureGuid)
    {
        $encounter = GameEncounter::where('guid', $encounterGuid)->where('user_id', $this->user->id)->first();
        if (! $encounter)
            return response()->json(['error' => 'Encounter not found'], 404);

        $creature = $encounter->Creatures()->where('guid', $creatureGuid)->where('encounter_id', $encounter->id)->first();
        if (! $creature)
            return response()->json(['error' => 'Creature not found in encounter'], 404);

        try {
            $jsonData = json_decode($request->getContent(), true);
            $rules = [
                'creature_details.hit_points.hp' => 'required|integer|lte:creature_details.hit_points.max_hp',
                'creature_details.hit_points.max_hp' => 'required|integer|gte:creature_details.hit_points.hp',
            ];

            $validator = Validator::make($jsonData, $rules);

            if (!$validator->passes())
                return response()->json(['error' => 'Invalid input', 'details' => $validator->errors()], Response::HTTP_BAD_REQUEST);

            $creatureDetails = $jsonData['creature_details'];

            // Only update specific details.
            $creature->max_hp = $creatureDetails['hit_points']['max_hp'];
            $creature->current_hp = $creatureDetails['hit_points']['hp'];
            $creature->unique_name = $creatureDetails['unique_name'];

            $creature->save();

            return EncounterResource::make($encounter);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Bad Request'], Response::HTTP_BAD_REQUEST);
        }
    }

    public function removeEncounterCreature(string $encounterGuid, string $creatureGuid)
    {
        $encounter = GameEncounter::where('guid', $encounterGuid)->where('user_id', $this->user->id)->first();
        if (! $encounter)
            return response()->json(['error' => 'Encounter not found'], 404);

        $creature = $encounter->Creatures()->where('guid', $creatureGuid)->where('encounter_id', $encounter->id)->first();
        if (! $creature)
            return response()->json(['error' => 'Creature not found in encounter'], 404);

        $creature->delete();

        return response()->json(['message' => 'Creature removed from encounter successfully'], 200);
    }

    public function addCreatureToEncounter(string $encounterGuid, Request $request)
    {
        $encounter = GameEncounter::where('guid', $encounterGuid)->where('user_id', $this->user->id)->first();
        if (! $encounter)
            return response()->json(['error' => 'Encounter not found'], 404);

        try {
            $jsonData = json_decode($request->getContent(), true);

            $rules = [
                'creature_id' => 'required|integer',
            ];

            $validator = Validator::make($jsonData, $rules);

            if (!$validator->passes())
                return response()->json(['error' => 'Invalid input', 'details' => $validator->errors()], Response::HTTP_BAD_REQUEST);

            $creature = GameCreature::where('id', $jsonData['creature_id'])->first();

            $hp = $this->creatureService->getCreatureHp(
                $creature->hit_points_dice,
                $creature->hit_points_dice_sides,
                $creature->hit_point_additional
            );

            $encounter->Creatures()->create([
                'guid' => Str::uuid()->toString(),
                'creature_id' => $creature->id,
                'unique_name' => $creature->name,
                'max_hp' => $hp,
                'current_hp' => $hp,
                'created_at' => Carbon::now(),
            ]);

            $encounter->save();

            return EncounterResource::make($encounter);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Bad Request'], Response::HTTP_BAD_REQUEST);
        }
    }

}
