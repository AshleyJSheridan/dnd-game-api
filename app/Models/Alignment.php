<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Alignment extends Model
{
    /** @use HasFactory<\Database\Factories\AlignmentFactory> */
    use HasFactory;

    protected $table = 'game_alignments';
    protected $primaryKey = 'id';
    const CREATED_AT = null;
    const UPDATED_AT = null;
}
