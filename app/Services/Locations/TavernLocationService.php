<?php

namespace App\Services\Locations;

use Illuminate\Http\Request;

class TavernLocationService
{
    public function makeLocation(Request $request)
    {
        var_dump($request->get('size'));
    }
}
