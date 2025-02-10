<?php

namespace App\Http\Controllers;

use App\Models\CharLanguage;

class LanguageController extends Controller
{
    public function getLanguages()
    {
        return CharLanguage::where('limited_access', 0)->get();
    }
}
