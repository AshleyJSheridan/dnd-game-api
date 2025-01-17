<?php

namespace App\Http\Controllers;

use App\Http\Resources\CharBackgroundResource;
use App\Models\CharBackground;

class CharBackgroundController extends Controller
{
    public function getCharacterBackgrounds()
    {
        $charBackgrounds = CharBackgroundResource::collection(CharBackground::all());

        return response()->json($charBackgrounds);
    }
}
