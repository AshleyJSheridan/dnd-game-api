<?php

namespace App\Http\Controllers;

use App\Http\Resources\CharacterResource;
use App\Http\Resources\NameSuggestionsResource;
use App\Models\Character;
use App\Services\NameGeneratorService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Str;

class CharactersController extends Controller
{
    public function getUserCharacters()
    {
        $userId = 1; // TODO this will come from user auth/session
        return CharacterResource::collection(Character::where('user_id', $userId)->get());
    }

    public function generateName(string $nameType = 'generic')
    {
        $numberOfNames = 6;
        $nameGeneratorService = App::make(NameGeneratorService::class, ['nameType' => $nameType]);

        $names = [];
        for($i = 0; $i < $numberOfNames; $i ++)
        {
            $names[] = $nameGeneratorService->generateName();
        }

        return NameSuggestionsResource::make(collect(['style' => $nameType, 'names' => $names]));
    }

    public function createCharacter(Request $request)
    {
        $userId = 1; // TODO this will come from user auth/session

        try {
            $jsonData = json_decode($request->getContent());

            $character = Character::create([
                'guid' => Str::uuid()->toString(),
                'name' => $jsonData->charName,
                'user_id' => $userId,
                'created_at' => Carbon::now(),
                'level' => $jsonData->charLevel,
            ]);

            return CharacterResource::make($character);
        } catch (\Exception $e) {
            // TODO do something here, probably means invalid JSON input
        }
    }

    public function updateCharacter(Request $request)
    {
        $userId = 1; // TODO this will come from user auth/session
        
        try {
            $jsonData = json_decode($request->getContent());

            $character = Character::where('guid', $jsonData->charGuid)->first();

            switch ($jsonData->updateType)
            {
                case 'class':
                    if ($character->class_id === 0)
                    {
                        $character->class_id = $jsonData->charClassId;
                    }
                    break;
            }

            $character->save();
        } catch (\Exception $e) {
            var_dump($e->getMessage());
            // TODO do something here, probably means invalid JSON input
        }
    }
}
