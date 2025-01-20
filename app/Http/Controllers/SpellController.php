<?php

namespace App\Http\Controllers;

use App\Http\Resources\GameSpellResource;
use App\Http\Resources\GameSpellSchoolResource;
use App\Models\GameSpell;
use App\Models\GameSpellSchool;

class SpellController extends Controller
{
    public function getSpells(string $school = null)
    {
        if (!$school)
            return GameSpellResource::collection(GameSpell::all());

        return GameSpellResource::collection(GameSpellSchool::where('name', ucfirst($school))->first()->Spells);
    }
}
