<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CampaignMapDrawingEntity extends Model
{
    /** @use HasFactory<\Database\Factories\CampaignMapDrawingEntityFactory> */
    use HasFactory, SoftDeletes;

    protected $table = 'game_map_entities';
    protected $primaryKey = 'id';
    protected $fillable = ['guid', 'linked_id', 'map_id', 'type', 'x', 'y', 'orientation', 'highlight_colour', 'created_at', 'updated_at', 'stats', 'deleted_at'];
}
