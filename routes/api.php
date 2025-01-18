<?php

use App\Http\Controllers\CharBackgroundController;
use App\Http\Controllers\CharRaceController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CharClassController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


Route::get('/characters/classes', [CharClassController::class, 'getCharacterClasses']);
Route::get('/characters/backgrounds', [CharBackgroundController::class, 'getCharacterBackgrounds']);
Route::get('/characters/races', [CharRaceController::class, 'getCharacterRaces']);
