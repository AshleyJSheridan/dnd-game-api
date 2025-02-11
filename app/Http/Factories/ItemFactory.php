<?php

namespace App\Http\Factories;

use App\Services\Items\WeaponItemService;
use Illuminate\Support\Facades\App;
use App\Services\Items\BookItemService;

class ItemFactory
{
    public static function create(string $itemType)
    {
        switch ($itemType)
        {
            case 'book':
                return App::make(BookItemService::class);
            case 'weapon':
                return App::make(WeaponItemService::class);
        }
    }
}
