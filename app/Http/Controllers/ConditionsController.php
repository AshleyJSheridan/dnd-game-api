<?php

namespace App\Http\Controllers;

use App\Http\Resources\ConditionResource;
use App\Models\Condition;

class ConditionsController extends Controller
{
    public function getConditions()
    {
        return ConditionResource::collection(Condition::all());
    }
}
