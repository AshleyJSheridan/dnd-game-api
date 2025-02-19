<?php

namespace App\Http\Controllers;

use App\Http\Resources\CreatureResource;
use App\Models\Character;
use App\Models\GameCreature;
use App\Services\CreatureService;
use Illuminate\Http\Request;

class EncountersController extends Controller
{
    public function __construct(private CreatureService $creatureService)
    {}

    public function createEncounter(Request $request)
    {
        $userId = 1; // TODO this will come from user auth/session

        try {
            $jsonData = json_decode($request->getContent());

            // TODO better validation on these inputs
            $charLevels = Character::whereIn('guid', $jsonData->characters)->pluck('level')->toArray();
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
