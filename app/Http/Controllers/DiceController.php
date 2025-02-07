<?php

namespace App\Http\Controllers;

use App\Http\Resources\GameDiceRollResource;
use App\Models\Character;
use App\Services\DiceRollService;
use Illuminate\Http\Request;

class DiceController extends Controller
{
    public function __construct(private DiceRollService $diceRollService)
    {}

    public function rollDice(Request $request)
    {
        $rollResponse = $this->diceRollService->getRollsFromDiceRequest($request);

        return GameDiceRollResource::make($rollResponse);
    }
}
