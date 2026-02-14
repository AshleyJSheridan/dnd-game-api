<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class CampaignMap extends Model
{
    /** @use HasFactory<\Database\Factories\CampaignMapFactory> */
    use HasFactory, SoftDeletes;

    protected $table = 'game_maps';
    protected $primaryKey = 'id';
    protected $fillable = ['guid', 'name', 'description', 'game_id', 'created_at', 'image', 'width', 'height',
        'show_grid', 'grid_size', 'grid_colour', 'hidden', 'active'];

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
