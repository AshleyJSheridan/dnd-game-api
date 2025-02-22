<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CharClassPath extends Model
{
    protected $table = 'char_class_paths';
    protected $primaryKey = 'id';

    public function Features(): HasMany
    {
        return $this->hasMany(CharClassPathFeature::class, 'class_path_id', 'id');
    }
}
