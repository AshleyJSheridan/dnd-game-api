<?php

namespace App\Http\Controllers;

use App\Http\Resources\CharRacesResource;
use App\Models\CharRace;

class CharRaceController extends Controller
{
    public function getCharacterRaces()
    {
        $charRaces = CharRacesResource::collection(CharRace::where('parent_race_id', 0)->get());

        return response()->json($charRaces);
    }
}
