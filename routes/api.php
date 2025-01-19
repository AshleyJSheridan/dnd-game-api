<?php

use App\Http\Controllers\CharBackgroundController;
use App\Http\Controllers\CharRaceController;
use App\Http\Controllers\DiceController;
use App\Http\Controllers\ItemController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CharClassController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


Route::get('/characters/classes', [CharClassController::class, 'getCharacterClasses']);
Route::get('/characters/backgrounds', [CharBackgroundController::class, 'getCharacterBackgrounds']);
Route::get('/characters/races', [CharRaceController::class, 'getCharacterRaces']);

Route::get('/game/items', [ItemController::class, 'getItems']);
Route::get('/game/items/{itemType}', [ItemController::class, 'getItems'])
    ->where('itemType', '(armor|book|clothing|food|other|pack|potion|projectile|weapon)');

Route::post('/game/dice', [DiceController::class, 'rollDice']);
