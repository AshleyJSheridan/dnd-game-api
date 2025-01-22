<?php

namespace App\Http\Controllers;

use App\Http\Resources\CharacterResource;
use App\Http\Resources\NameSuggestionsResource;
use App\Models\Character;
use App\Services\NameGeneratorService;
use Illuminate\Support\Facades\App;

class CharactersController extends Controller
{
    /*public function __construct(private NameGeneratorService $nameGeneratorService)
    {}*/

    public function getUserCharacters()
    {
        $userId = 1; // TODO this will come from user auth/session
        return CharacterResource::collection(Character::where('user_id', $userId)->get());
    }

    public function generateName(string $nameType = 'generic')
    {
        $nameGeneratorService = App::make(NameGeneratorService::class, ['nameType' => $nameType]);

        $names = [];
        for($i = 0; $i < 6; $i ++)
        {
            $names[] = $nameGeneratorService->generateName();
        }

        return NameSuggestionsResource::make(collect(['style' => $nameType, 'names' => $names]));
    }
}
