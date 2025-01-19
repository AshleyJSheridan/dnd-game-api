<?php

namespace App\Http\Controllers;

use App\Http\Resources\GameSpellResource;
use App\Models\GameSpell;

class SpellController extends Controller
{
    public function getSpells(string $school = null)
    {
        if (!$school)
            return GameSpellResource::collection(GameSpell::all());

        //return GameSpellResource::collection(GameSpell::where('school', $school)->get());
    }
}
