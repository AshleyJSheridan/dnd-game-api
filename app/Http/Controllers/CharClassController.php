<?php

namespace App\Http\Controllers;

use App\Http\Resources\CharacterResource;
use App\Http\Resources\CharClassResource;
use App\Http\Resources\CharStarterPackResource;
use App\Models\CharClass;

class CharClassController extends Controller
{
    public function getCharacterClasses()
    {
        $charClasses = CharClassResource::collection(CharClass::all());

        return response()->json($charClasses);
    }

    public function getCharacterClass(string $className)
    {
        $charClass = CharClass::where('name', ucfirst(strtolower($className)))->first();

        if (!$charClass)
            return response()->json(['error' => 'Character class not found'], 404);

        return CharClassResource::make($charClass);
    }

    public function getStartingEquipment(string $className)
    {
        $charClass = CharClass::where('name', ucfirst(strtolower($className)))->first();

        if (!$charClass)
            return response()->json(['error' => 'Character class not found'], 404);

        return CharStarterPackResource::collection($charClass->StartingEquipmentPacks);
    }
}
