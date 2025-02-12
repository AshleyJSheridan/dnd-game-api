<?php

namespace App\Http\Factories;

use Illuminate\Support\Facades\App;

class ItemFactory
{
    public static function create(string $itemType)
    {
        $classPath = '\\App\\Services\\Items\\' . ucfirst(str_replace(' ', '', $itemType)) . 'ItemService';

        if (class_exists($classPath))
            return App::make($classPath);
    }
}
