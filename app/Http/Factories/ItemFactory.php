<?php

namespace App\Http\Factories;

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
        }
    }
}
