<?php

namespace App\Http\Controllers;

use App\Http\Resources\AlignmentResource;
use App\Models\Alignment;

class CreatureAlignmentController extends Controller
{
    public function getCreatureAlignments()
    {
        return AlignmentResource::collection(Alignment::all());
    }
}
