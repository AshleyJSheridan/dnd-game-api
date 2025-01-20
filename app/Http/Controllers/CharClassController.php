<?php

namespace App\Http\Controllers;

use App\Http\Resources\CharClassResource;
use App\Models\CharClass;

class CharClassController extends Controller
{
    public function getCharacterClasses()
    {
        $charClasses = CharClassResource::collection(CharClass::all());

        return response()->json($charClasses);
    }
}
