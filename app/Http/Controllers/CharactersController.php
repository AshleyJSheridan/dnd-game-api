<?php

namespace App\Http\Controllers;

use App\Http\Resources\CharacterResource;
use App\Models\Character;

class CharactersController extends Controller
{
    public function getUserCharacters()
    {
        $userId = 1; // TODO this will come from user auth/session
        return CharacterResource::collection(Character::where('user_id', $userId)->get());
    }
}
