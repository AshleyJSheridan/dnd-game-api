<?php

namespace App\Http\Controllers;

use App\Http\Resources\AvailableSpellsResource;
use App\Http\Resources\CharacterResource;
use App\Http\Resources\NameSuggestionsResource;
use App\Models\CharAbility;
use App\Models\Character;
use App\Models\DiceRoll;
use App\Services\MagicService;
use App\Services\NameGeneratorService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Str;

class CharactersController extends Controller
{
    public function __construct(private MagicService $magicService)
    {}

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

    public function updateCharacter(string $guid, Request $request)
    {
        $userId = 1; // TODO this will come from user auth/session

        try {
            $jsonData = json_decode($request->getContent());

            $character = Character::where('guid', $guid)->first();

            // TODO: pass this off to something else, shouldn't be doing it in the controller!
            switch ($jsonData->updateType)
            {
                case 'class':
                    if ($character->class_id === 0 && $jsonData->charClassId)
                    {
                        $character->class_id = $jsonData->charClassId;
                    }

                    if ($character->selected_path === 0 && $jsonData->classPathId)
                    {
                        $character->selected_path = $jsonData->classPathId;
                    }
                    // TODO add languages for druids, thieves, and monks appropriate to level
                    break;
                case 'background':
                    if ($character->background_id === 0 && $jsonData->charBackgroundId && $jsonData->characteristics)
                    {
                        $character->background_id = $jsonData->charBackgroundId;
                        $character->CharacterBackgroundCharacteristics()->attach($jsonData->characteristics);
                    }
                    break;
                case 'race':
                    if ($character->race_id === 0 && $jsonData->charRaceId)
                    {
                        $character->race_id = $jsonData->charRaceId;
                    }
                    // TODO add languages applicable to each race
                    break;
                case 'abilities':
                    /*
                     * ensure that all guids are the unique and exist
                     * ensure all ability ids are unique and 0-5 are accounted for
                     */
                    // TODO tidy this all up
                    $abilityIds = $rollGuids = [];
                    foreach ($jsonData->abilityRolls as $roll)
                    {
                        $abilityIds[] = $roll->abilityId;
                        $rollGuids[] = $roll->guid;
                    }
                    $abilityIds = array_unique($abilityIds);
                    $rollGuids = array_unique($rollGuids);
                    $storedRolls = DiceRoll::whereIn('guid', $rollGuids)->get();
                    $charData = [];

                    if (count($abilityIds) === 6 && count($storedRolls) === 6)
                    {
                        foreach ($jsonData->abilityRolls as $roll)
                        {
                            $abilityName = CharAbility::where('id', $roll->abilityId)->pluck('short_name')->first();
                            $rollData = json_decode($storedRolls->where('guid', $roll->guid)->pluck('roll_data')->first())->d6;
                            rsort($rollData);
                            array_pop($rollData);
                            $total = array_sum($rollData);

                            $charData[$abilityName] = $total;
                        }

                        $character->abilities = json_encode($charData);
                    }

                    break;
                case 'languages':
                    $languages = $jsonData->languages;
                    $availableCount = $character->AvailableLanguageCount() - count($character->languages);
                    if (count($languages) > $availableCount)
                    {
                        $languages = array_slice($languages, 0, $availableCount);
                    }
                    $character->Languages()->attach($languages);
                    break;
                case 'spells':
                    $spellIds = $jsonData->spells;
                    $availableCount = $this->magicService->getAvailableSpellsTotal($character);
                    if (count($spellIds) > $availableCount)
                    {
                        $spellIds = array_slice($spellIds, 0, $availableCount);
                    }
                    $character->Spells()->sync($spellIds);

                    break;
            }

            $character->save();

            // retrieve character again as calculated and related values may have changed since update
            return CharacterResource::make(Character::where('guid', $guid)->first());
        } catch (\Exception $e) {
            var_dump($e->getMessage());
            // TODO do something here, probably means invalid JSON input
        }
    }

    public function getCharacter(string $guid)
    {
        $userId = 1; // TODO this will come from user auth/session

        return CharacterResource::make(Character::where('guid', $guid)->first());
    }

    public function getCharacterAvailableSpells(string $guid)
    {
        $userId = 1; // TODO this will come from user auth/session

        $character = Character::where('guid', $guid)->first();

        $availableSpells = $this->magicService->getAvailableSpells($character);

        return AvailableSpellsResource::make(collect($availableSpells));
    }
}
