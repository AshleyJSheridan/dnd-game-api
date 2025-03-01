<?php

namespace App\Http\Controllers;

use App\Http\Factories\LocationFactory;
use App\Models\Location;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    public function generateLocation(string $type, Request $request)
    {
        return LocationFactory::create($type)->makeLocation($request);
    }

    public function getMap(string $guid, int $floor = 0)
    {
        $location = Location::where('guid', $guid)->first();
        $floor = $location->Floors->where('floor', $floor)->first();
        $dimensions = json_decode($location->dimensions);

        return response()
            ->view("maps.tavern", [
                'dimensions' => $dimensions,
                'flooring_tile' => $floor->flooring_type,
                'objects' => $floor->Objects,
            ])
            ->header('Content-Type', 'image/svg+xml');
    }
}
