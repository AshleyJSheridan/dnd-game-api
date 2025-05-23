<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class CampaignMapCharacterEntity extends Model
{
    /** @use HasFactory<\Database\Factories\CampaignMapCharacterEntityFactory> */
    use HasFactory, SoftDeletes;

    protected $table = 'game_map_entities';
    protected $primaryKey = 'id';
    protected $fillable = ['guid', 'linked_id', 'map_id', 'type', 'x', 'y', 'orientation', 'highlight_colour', 'created_at', 'updated_at'];

    public function Player(): HasOne
    {
        return $this->hasOne(Character::class, 'id', 'linked_id');
    }
}
