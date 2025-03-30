<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CampaignMap extends Model
{
    protected $table = 'game_maps';
    protected $primaryKey = 'id';
    protected $fillable = ['guid', 'name', 'description', 'game_id', 'created_at', 'image'];
}
