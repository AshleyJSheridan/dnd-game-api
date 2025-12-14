<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CampaignController;
use App\Http\Controllers\CharactersController;
use App\Http\Controllers\CharBackgroundController;
use App\Http\Controllers\CharRaceController;
use App\Http\Controllers\CreatureAlignmentController;
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
Route::post('user/refresh', [AuthController::class, 'refreshToken']);

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
    Route::get('/characters/classes/{className}', [CharClassController::class, 'getCharacterClass']);
    Route::get('/characters/alignments', [CreatureAlignmentController::class, 'getCreatureAlignments']);
    Route::get('/characters/backgrounds', [CharBackgroundController::class, 'getCharacterBackgrounds']);
    Route::get('/characters/races', [CharRaceController::class, 'getCharacterRaces']);
    Route::get('/characters/{guid}', [CharactersController::class, 'getCharacter']);
    Route::delete('/characters/{guid}', [CharactersController::class, 'deleteCharacter']);
    Route::patch('/characters/{guid}', [CharactersController::class, 'updateCharacter']);
    Route::get('/characters/{guid}/startingEquipment', [ItemController::class, 'getStartingEquipment']);
    Route::post('/characters/{guid}/startingEquipment', [ItemController::class, 'setStartingEquipment']);
    Route::get('/characters/{guid}/inventory', [ItemController::class, 'getPlayerInventory']);
    Route::post('/characters/{guid}/inventory', [ItemController::class, 'addItemsToPlayerInventory']);
    Route::get('/characters/{guid}/spells/available', [CharactersController::class, 'getCharacterAvailableSpells']);
    Route::post('/characters/{guid}/portrait', [CharactersController::class, 'uploadPortrait']);
    Route::patch('/characters/{charGuid}/inventory/{itemGuid}', [ItemController::class, 'updateInventoryItem']);
    Route::delete('/characters/{charGuid}/inventory/{itemGuid}', [ItemController::class, 'removeInventoryItem']);

    // languages
    Route::get('/game/languages', [LanguageController::class, 'getLanguages']);

    // names
    Route::get('/names', [CharactersController::class, 'generateName']);
    Route::get('/names/{nameType}', [CharactersController::class, 'generateName'])
        ->where('nameType', '(generic|goblin|orc|ogre|dwarf|halfling|gnome|elf|fey|demon|angel|human|tiefling)');

    // items
    Route::get('/game/items', [ItemController::class, 'getItems']);
    Route::get('/game/items/{itemType}', [ItemController::class, 'getItems'])
        ->where('itemType', '(armor|book|clothing|food|other|pack|potion|projectile|weapon|gemstone|art object|bag|artisan|instrument|gaming)');
    Route::get('/game/items/{itemType}/random', [ItemController::class, 'getRandomItem'])
        ->where('itemType', '(armor|book|clothing|food|other|potion|projectile|weapon|gemstone|art object)');

    // spells
    Route::get('/game/spells', [SpellController::class, 'getSpells']);
    Route::get('/game/spells/level/{level}', [SpellController::class, 'getSpells'])
        ->where('level', '[0-9]+');
    Route::get('/game/spells/school/{school}', [SpellController::class, 'getSpellsBySchool']);
    Route::get('/game/spells/school/{school}/level/{level}', [SpellController::class, 'getSpellsBySchool'])
        ->where('level', '[0-9]+');
    Route::get('/game/spells/class/{classId}', [SpellController::class, 'getSpellsForClass'])
        ->where('classId', '[0-9]+');
    Route::get('/game/spells/class/{classId}/level/{level}', [SpellController::class, 'getSpellsForClass'])
        ->where('classId', '[0-9]+')
        ->where('level', '[0-9]+');

    // dice
    Route::post('/game/dice', [DiceController::class, 'rollDice']);

    // creatures
    Route::get('/creatures', [CreaturesController::class, 'getAllCreatures']);
    Route::get('/creatures/{creatureType}', [CreaturesController::class, 'getCreatures'])
        ->where('creatureType', '(aberration|beast|celestial|construct|demon|devil|dragon|elemental|fey|giant|humanoid|monstrosity|ooze|plant|undead)');

    // encounters
    Route::post('/encounters/', [EncountersController::class, 'createEncounter']);
    Route::get('encounters/{guid}', [EncountersController::class, 'getEncounterByGuid']);

    // campaign maps
    Route::post('/campaigns/{guid}/maps', [CampaignController::class, 'createMap']);
    Route::get('/campaigns/{campaignGuid}/maps/{mapGuid}', [CampaignController::class, 'getMap']);
    Route::patch('/campaigns/{campaignGuid}/maps/{mapGuid}', [CampaignController::class, 'updateMap']);
    Route::post('/campaigns/{campaignGuid}/maps/{mapGuid}/entities', [CampaignController::class, 'addEntityToMap']);
    Route::patch('/campaigns/{campaignGuid}/maps/{mapGuid}/entities/{entityGuid}', [CampaignController::class, 'updateMapEntity']);
    Route::delete('/campaigns/{campaignGuid}/maps/{mapGuid}/entities/{entityGuid}', [CampaignController::class, 'deleteMapEntity']);

    // campaign lore
    Route::get('/campaigns/lore-groups', [CampaignController::class, 'getLoreGroups']);
    Route::get('/campaigns/{guid}/lore', [CampaignController::class, 'getCampaignLore']);
    Route::post('/campaigns/{guid}/lore', [CampaignController::class, 'createCampaignLore']);
    Route::delete('/campaigns/{guid}/lore/{loreGuid}', [CampaignController::class, 'deleteCampaignLoreItem']);
    Route::patch('/campaigns/{guid}/lore/{loreGuid}', [CampaignController::class, 'editCampaignLoreItem']);

    // campaigns
    Route::get('/campaigns', [CampaignController::class, 'getCampaigns']);
    Route::post('/campaigns', [CampaignController::class, 'createCampaign']);
    Route::get('/campaigns/{guid}', [CampaignController::class, 'getCampaign']);
    Route::patch('/campaigns/{guid}', [CampaignController::class, 'updateCampaign']);
    Route::post('/campaigns/{guid}/characters', [CampaignController::class, 'addCharacterToCampaign']);
    Route::delete('/campaigns/{campaignGuid}/characters/{charGuid}', [CampaignController::class, 'removeCharacterFromCampaign']);
});

// images - no auth required so the images can be used in <img> tags
Route::get('/campaigns/maps/{guid}/image', [CampaignController::class, 'getMapImage']);
Route::get('/campaigns/maps/{guid}/thumb', [CampaignController::class, 'getMapThumb']);
Route::get('/characters/{guid}/portrait', [CharactersController::class, 'getPortraitImage']);

Route::get('/campaigns/{guid}/lore/{loreGuid}/thumb', [CampaignController::class, 'getCampaignLoreThumb']);
Route::get('/campaigns/{guid}/lore/{loreGuid}', [CampaignController::class, 'getCampaignLoreItem']);


// locations
Route::any('/location/create/{type}', [LocationController::class, 'generateLocation'])
    ->where('type', '(tavern)');
Route::get('/location/{guid}/map', [LocationController::class, 'getMap']);
Route::get('/location/{guid}/map/{floor}', [LocationController::class, 'getMap']);
