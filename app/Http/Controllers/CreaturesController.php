<?php

namespace App\Http\Controllers;

use App\Http\Resources\CreatureResource;
use App\Models\GameCreature;
use App\Services\CreatureService;

class CreaturesController extends Controller
{
    public function __construct(private CreatureService $creatureService)
    {}

    public function getCreatures(string $creatureType)
    {
        return CreatureResource::collection(
            $this->creatureService->addProcessedFields(
                GameCreature::where('type', $creatureType)->get()
            )
        );
    }
}
