<?php

namespace App\Http\Controllers;


use App\Http\Resources\DeityResource;
use App\Models\Deity;

class DeityController extends Controller
{
    public function getDeities()
    {
        return DeityResource::collection(Deity::with('Alignment')->get());
    }
}
