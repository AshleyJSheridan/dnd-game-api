<?php

namespace App\Http\Factories;

use Illuminate\Support\Facades\App;

class LocationFactory
{
    public static function create(string $type)
    {
        $classPath = '\\App\\Services\\Locations\\' . ucfirst(str_replace(' ', '', $type)) . 'LocationService';

        if (class_exists($classPath))
            return App::make($classPath);
    }
}
