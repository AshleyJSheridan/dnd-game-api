<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class CampaignMap extends Model
{
    protected $table = 'game_maps';
    protected $primaryKey = 'id';
    protected $fillable = ['guid', 'name', 'description', 'game_id', 'created_at', 'image', 'width', 'height'];

    public function Campaign(): HasOne
    {
        return $this->hasOne(Campaign::class, 'id', 'campaign_id');
    }
}
