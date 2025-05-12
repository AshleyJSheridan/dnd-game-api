<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class CampaignMap extends Model
{
    protected $table = 'game_maps';
    protected $primaryKey = 'id';
    protected $fillable = ['guid', 'name', 'description', 'game_id', 'created_at', 'image', 'width', 'height',
        'show_grid', 'grid_size', 'grid_colour'];

    public function Campaign(): HasOne
    {
        return $this->hasOne(Campaign::class, 'id', 'game_id');
    }

    public function Players(): HasMany
    {
        return $this->hasMany(CampaignMapCharacterEntity::class, 'map_id', 'id')
            ->where('type', 'character');
    }

    public function Creatures(): HasMany
    {
        return $this->hasMany(CampaignMapCreatureEntity::class, 'map_id', 'id')
            ->where('type', 'creature');
    }

    public function Drawings(): HasMany
    {
        return $this->hasMany(CampaignMapDrawingEntity::class, 'map_id', 'id')
            ->where('type', 'drawing');
    }

    public function RawEntities(): HasMany
    {
        return $this->hasMany(CampaignMapDrawingEntity::class, 'map_id', 'id');
    }
}
