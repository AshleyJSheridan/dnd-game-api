<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class CampaignMapCreatureEntity extends Model
{
    /** @use HasFactory<\Database\Factories\CampaignMapCreatureEntityFactory> */
    use HasFactory, SoftDeletes;

    protected $table = 'game_map_entities';
    protected $primaryKey = 'id';
    protected $fillable = ['guid', 'linked_id', 'map_id', 'type', 'x', 'y', 'orientation', 'highlight_colour', 'created_at', 'updated_at', 'stats', 'entity_name'];

    public function Creature(): HasOne
    {
        return $this->hasOne(GameCreature::class, 'id', 'linked_id');
    }
}
