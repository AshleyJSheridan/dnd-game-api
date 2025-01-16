<?php

namespace App\Http\Controllers;

use App\Http\Resources\CharClassResource;
use App\Models\CharClasses;

class CharClassController extends Controller
{
    public function getCharacterClasses()
    {
        $charClasses = CharClassResource::collection(CharClasses::all());

        return response()->json($charClasses);
    }
}
