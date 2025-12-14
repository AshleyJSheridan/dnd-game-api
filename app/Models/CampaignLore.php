<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CampaignLore extends Model
{
    use SoftDeletes;

    /** @use HasFactory<\Database\Factories\CampaignFactory> */
    use HasFactory;

    protected $table = 'game_lore';
    protected $primaryKey = 'id';
    protected $fillable = ['guid', 'game_id', 'name', 'type', 'created_at', 'updated_at', 'raw_content', 'parsed_content',
        'url', 'file', 'is_image', 'player_visible', 'lore_group'];

    public function LoreGroup()
    {
        return $this->belongsTo(CampaignLoreGroup::class, 'lore_group', 'id');
    }
}
