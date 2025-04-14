<?php

namespace App\Http\Controllers;

use App\Http\Resources\AvailableSpellsResource;
use App\Http\Resources\CharacterResource;
use App\Http\Resources\CharStarterPackResource;
use App\Http\Resources\NameSuggestionsResource;
use App\Models\CharAbility;
use App\Models\Character;
use App\Models\CharClass;
use App\Models\DiceRoll;
use App\Models\User;
use App\Services\MagicService;
use App\Services\NameGeneratorService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Str;
use Spatie\Image\Enums\ImageDriver;
use Spatie\Image\Image;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Http\Response;

class CharactersController extends Controller
{
    private User $user;

    public function __construct(private MagicService $magicService)
    {
        try {
            if (! $this->user = JWTAuth::parseToken()->authenticate())
                return response()->json(['error' => 'User not found'], Response::HTTP_NOT_FOUND);
        } catch (JWTException $e) {
            return response()->json(['error' => 'Invalid token'], Response::HTTP_BAD_REQUEST);
        }
    }

    public function getUserCharacters()
    {
        return CharacterResource::collection(Character::where('user_id', $this->user->id)->get());
    }

    public function generateName(string $nameType = 'generic')
    {
        // TODO move this out to its own controller
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
        try {
            $jsonData = json_decode($request->getContent());

            $character = Character::create([
                'guid' => Str::uuid()->toString(),
                'name' => $jsonData->charName,
                'user_id' => $this->user->id,
                'created_at' => Carbon::now(),
                'level' => $jsonData->charLevel,
            ]);

            return CharacterResource::make($character);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Bad Request' . $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    public function updateCharacter(string $guid, Request $request)
    {
        try {
            $jsonData = json_decode($request->getContent());

            $character = Character::where('guid', $guid)->where('user_id', $this->user->id)->first();

            // TODO: pass this off to something else, shouldn't be doing it in the controller!
            switch ($jsonData->updateType)
            {
                case 'class':
                    $character->class_id = $jsonData->charClassId;

                    $character->selected_path = $jsonData->classPathId;

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
                case 'skills':
                    $skills = $jsonData->skills;
                    $skillOptions = json_decode($character->CharacterClass->skill_options) ?? null;
                    $availableCount = $skillOptions ? $skillOptions->max : 0;
                    if (count($skills) > $availableCount)
                    {
                        $skills = array_slice($skills, 0, $availableCount);
                    }
                    $character->Skills()->sync($skills);
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
                    $character->Languages()->sync($languages);
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
            return CharacterResource::make(Character::where('guid', $guid)->where('user_id', $this->user->id)->first());
        } catch (\Exception $e) {
            return response()->json(['error' => 'Bad Request'], Response::HTTP_BAD_REQUEST);
        }
    }

    public function getCharacter(string $guid)
    {
        return CharacterResource::make(Character::where('guid', $guid)->where('user_id', $this->user->id)->first());
    }

    public function getCharacterAvailableSpells(string $guid)
    {
        $character = Character::where('guid', $guid)->where('user_id', $this->user->id)->first();

        $availableSpells = $this->magicService->getAvailableSpells($character);

        return AvailableSpellsResource::make(collect($availableSpells));
    }

    public function uploadPortrait(string $guid)
    {
        $character = Character::where('guid', $guid)->where('user_id', $this->user->id)->first();

        $portraitSize = 200; // square image

        $imageName = Str::uuid()->toString() . '.' . request()->image->getClientOriginalExtension();
        request()->image->move(storage_path('portraits'), $imageName);

        $image = Image::useImageDriver(ImageDriver::Gd)
            ->loadFile(storage_path('portraits/' . $imageName))
            ->resize($portraitSize, $portraitSize)
            ->save(storage_path('portraits/') . $imageName);

        $character->custom_portrait = $imageName;
        $character->save();

        return CharacterResource::make($character);
    }

    public function getPortraitImage(string $guid)
    {
        $character = Character::where('guid', $guid)->first();

        return response()->file(storage_path('portraits/' . $character->custom_portrait));
    }

    public function getStartingEquipment(string $guid)
    {
        $character = Character::where('guid', $guid)->first();

        if (!$character->CharacterClass)
            return CharStarterPackResource::collection([]);

        return CharStarterPackResource::collection($character->CharacterClass->StartingEquipmentPacks);
    }
}
