<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CampaignMapDrawingEntity extends Model
{
    protected $table = 'game_map_entities';
    protected $primaryKey = 'id';
    protected $fillable = ['guid', 'linked_id', 'map_id', 'type', 'x', 'y', 'orientation', 'highlight_colour', 'created_at', 'updated_at'];
}
