<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CampaignController;
use App\Http\Controllers\CharactersController;
use App\Http\Controllers\CharBackgroundController;
use App\Http\Controllers\CharRaceController;
use App\Http\Controllers\CreaturesController;
use App\Http\Controllers\DiceController;
use App\Http\Controllers\EncountersController;
use App\Http\Controllers\HeartbeatController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\LanguageController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\SpellController;
use App\Http\Middleware\JwtMiddleware;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CharClassController;

// users
Route::post('user/register', [AuthController::class, 'register']);
Route::post('user/login', [AuthController::class, 'login']);

Route::middleware([JwtMiddleware::class])->group(function () {
    // heartbeat
    Route::get('/heartbeat', [HeartbeatController::class, 'heartbeat']);

    // users
    Route::get('user', [AuthController::class, 'getUser']);
    Route::post('user/logout', [AuthController::class, 'logout']);
    Route::delete('user', [AuthController::class, 'deleteUser']);

    // characters
    Route::get('/characters', [CharactersController::class, 'getUserCharacters']);
    Route::post('/characters', [CharactersController::class, 'createCharacter']);
    Route::get('/characters/classes', [CharClassController::class, 'getCharacterClasses']);
    Route::get('/characters/backgrounds', [CharBackgroundController::class, 'getCharacterBackgrounds']);
    Route::get('/characters/races', [CharRaceController::class, 'getCharacterRaces']);
    Route::get('/characters/{guid}', [CharactersController::class, 'getCharacter']);
    Route::patch('/characters/{guid}', [CharactersController::class, 'updateCharacter']);
    Route::get('/characters/{guid}/spells/available', [CharactersController::class, 'getCharacterAvailableSpells']);

    // languages
    Route::get('/game/languages', [LanguageController::class, 'getLanguages']);

    // names
    Route::get('/names', [CharactersController::class, 'generateName']);
    Route::get('/names/{nameType}', [CharactersController::class, 'generateName'])
        ->where('nameType', '(generic|goblin|orc|ogre|dwarf|halfling|gnome|elf|fey|demon|angel|human|tiefling)');

    // items
    Route::get('/game/items', [ItemController::class, 'getItems']);
    Route::get('/game/items/{itemType}', [ItemController::class, 'getItems'])
        ->where('itemType', '(armor|book|clothing|food|other|pack|potion|projectile|weapon|gemstone|art object)');
    Route::get('/game/items/{itemType}/random', [ItemController::class, 'getRandomItem'])
        ->where('itemType', '(armor|book|clothing|food|other|potion|projectile|weapon|gemstone|art object)');

    // spells
    Route::get('/game/spells', [SpellController::class, 'getSpells']);
    Route::get('/game/spells/level/{level}', [SpellController::class, 'getSpells'])
        ->where('level', '[0-9]+');
    Route::get('/game/spells/school/{school}', [SpellController::class, 'getSpellsBySchool'])
        ->where('school', '(abjuration|conjuration|divination|enchantment|evocation|illusion|necromancy|transmutation)');
    Route::get('/game/spells/school/{school}/level/{level}', [SpellController::class, 'getSpellsBySchool'])
        ->where('school', '(abjuration|conjuration|divination|enchantment|evocation|illusion|necromancy|transmutation)')
        ->where('level', '[0-9]+');
    Route::get('/game/spells/class/{classId}', [SpellController::class, 'getSpellsForClass'])
        ->where('classId', '[0-9]+');
    Route::get('/game/spells/class/{classId}/level/{level}', [SpellController::class, 'getSpellsForClass'])
        ->where('classId', '[0-9]+')
        ->where('level', '[0-9]+');

    // dice
    Route::post('/game/dice', [DiceController::class, 'rollDice']);

    // creatures
    Route::get('/creatures/{creatureType}', [CreaturesController::class, 'getCreatures'])
        ->where('creatureType', '(aberration|beast|celestial|construct|demon|devil|dragon|elemental|fey|giant|humanoid|monstrosity|ooze|plant|undead)');

    // encounters
    Route::post('/encounters/', [EncountersController::class, 'createEncounter']);
    Route::get('encounters/{guid}', [EncountersController::class, 'getEncounterByGuid']);

    // campaign maps
    Route::post('/campaigns/maps', [CampaignController::class, 'createMap']);
    Route::get('/campaigns/maps/{guid}', [CampaignController::class, 'getMap']);
    Route::patch('/campaigns/maps/{guid}', [CampaignController::class, 'updateMap']);

    // campaigns
    Route::get('/campaigns', [CampaignController::class, 'getCampaigns']);
    Route::post('/campaigns', [CampaignController::class, 'createCampaign']);
    Route::get('/campaigns/{guid}', [CampaignController::class, 'getCampaign']);
});

// campaign map images - no auth required so the images can be used in <img> tags
Route::get('/campaigns/maps/{guid}/image', [CampaignController::class, 'getMapImage']);
Route::get('/campaigns/maps/{guid}/thumb', [CampaignController::class, 'getMapThumb']);



// locations
Route::any('/location/create/{type}', [LocationController::class, 'generateLocation'])
    ->where('type', '(tavern)');
Route::get('/location/{guid}/map', [LocationController::class, 'getMap']);
Route::get('/location/{guid}/map/{floor}', [LocationController::class, 'getMap']);
