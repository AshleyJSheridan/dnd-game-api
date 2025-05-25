<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CharLanguage extends Model
{
    /** @use HasFactory<\Database\Factories\CharLanguageFactory> */
    use HasFactory;

    protected $table = 'char_languages';
    protected $primaryKey = 'id';
    const CREATED_AT = null;
    const UPDATED_AT = null;
}
