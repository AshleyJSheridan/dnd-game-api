<?php

namespace App\Http\Controllers;

use App\Http\Resources\DamageTypeResource;
use App\Models\DamageType;

class DamageTypesController extends Controller
{
    public function getDamageTypes()
    {
        return DamageTypeResource::collection(DamageType::where('special', 0)->get()->sortBy('name'));
    }
}
