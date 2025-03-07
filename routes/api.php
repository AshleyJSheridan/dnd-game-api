<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CharactersController;
use App\Http\Controllers\CharBackgroundController;
use App\Http\Controllers\CharRaceController;
use App\Http\Controllers\CreaturesController;
use App\Http\Controllers\DiceController;
use App\Http\Controllers\EncountersController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\LanguageController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\SpellController;
use App\Http\Middleware\JwtMiddleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CharClassController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

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
Route::get('/characters/name', [CharactersController::class, 'generateName']);
Route::get('/characters/name/{nameType}', [CharactersController::class, 'generateName'])
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
    ->where('level', '[0-9]');
Route::get('/game/spells/school/{school}', [SpellController::class, 'getSpellsBySchool'])
    ->where('school', '(abjuration|conjuration|divination|enchantment|evocation|illusion|necromancy|transmutation)');
Route::get('/game/spells/school/{school}/level/{level}', [SpellController::class, 'getSpellsBySchool'])
    ->where('school', '(abjuration|conjuration|divination|enchantment|evocation|illusion|necromancy|transmutation)')
    ->where('level', '[0-9]');
Route::get('/game/spells/class/{classId}', [SpellController::class, 'getSpellsForClass'])
    ->where('classId', '[0-9]');
Route::get('/game/spells/class/{classId}/level/{level}', [SpellController::class, 'getSpellsForClass'])
    ->where('classId', '[0-9]')
    ->where('level', '[0-9]');

// dice
Route::post('/game/dice', [DiceController::class, 'rollDice']);

// creatures
Route::get('/creatures/{creatureType}', [CreaturesController::class, 'getCreatures'])
    ->where('creatureType', '(aberration|beast|celestial|construct|demon|devil|dragon|elemental|fey|giant|humanoid|monstrosity|ooze|plant|undead)');

// encounters
Route::post('/creatures/encounter/', [EncountersController::class, 'createEncounter']);

// locations
Route::any('/location/create/{type}', [LocationController::class, 'generateLocation'])
    ->where('type', '(tavern)');
Route::get('/location/{guid}/map', [LocationController::class, 'getMap']);
Route::get('/location/{guid}/map/{floor}', [LocationController::class, 'getMap']);

// users
Route::post('users/register', [AuthController::class, 'register']);
Route::post('users/login', [AuthController::class, 'login']);
Route::middleware([JwtMiddleware::class])->group(function () {
    Route::get('users/user', [AuthController::class, 'getUser']);
    Route::post('users/logout', [AuthController::class, 'logout']);
});
