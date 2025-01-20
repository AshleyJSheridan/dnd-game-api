<?php

namespace App\Http\Controllers;

use App\Http\Resources\GameSpellResource;
use App\Models\GameSpell;
use App\Models\GameSpellSchool;

class SpellController extends Controller
{
    public function getSpells(int $level = null)
    {
        if (is_null($level))
            return GameSpellResource::collection(GameSpell::all());

        return GameSpellResource::collection(GameSpell::where('level', $level)->get());
    }

    public function getSpellsBySchool(string $school, int $level = null)
    {
        if (is_null($level))
            return GameSpellResource::collection(GameSpellSchool::where('name', ucfirst($school))->first()->Spells);

        return GameSpellResource::collection(GameSpellSchool::where('name', ucfirst($school))->first()->Spells->where('level', $level));
    }

    public function getSpellsForClass(int $classId, int $level = null)
    {
        if (is_null($level))
            return GameSpellResource::collection(GameSpell::whereHas('CharClasses', function ($query) use ($classId) {
                $query->where('class_id', $classId);
            })->orderBy('level')->orderBy('name')->get());

        return GameSpellResource::collection(GameSpell::whereHas('CharClasses', function ($query) use ($classId) {
            $query->where('class_id', $classId);
        })->where('level', '<=', $level)->orderBy('level')->orderBy('name')->get());
    }
}
