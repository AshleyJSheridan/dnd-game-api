<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DiceRoll extends Model
{
    protected $table = 'dice_rolls';
    protected $primaryKey = 'id';

    protected $fillable = ['guid', 'roll_data'];
}
