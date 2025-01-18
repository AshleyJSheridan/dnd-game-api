<?php

namespace App\Http\Controllers;

use App\Http\Resources\GameDiceRollResource;
use App\Services\DiceRollService;
use Illuminate\Http\Request;

class DiceController extends Controller
{
    public function __construct(private DiceRollService $diceRollService)
    {}

    public function rollDice(Request $request)
    {
        $rollRequest = $this->diceRollService->getDiceSidesFromRequest($request);

        return GameDiceRollResource::collection(collect($rollRequest));
    }


}
