<?php

namespace App\Http\Controllers;

use App\Http\Resources\GameDiceRollResource;
use App\Services\DiceRollService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class DiceController extends Controller
{
    public function __construct(private DiceRollService $diceRollService)
    {}

    public function rollDice(Request $request)
    {
        var_dump($request->getContent());
        if ($request->getContent() === '')
            return response()->json(['error' => 'No dice'], Response::HTTP_BAD_REQUEST);

        $rollResponse = $this->diceRollService->getRollsFromDiceRequest($request);

        return GameDiceRollResource::make($rollResponse);
    }
}
