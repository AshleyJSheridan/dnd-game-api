<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CampaignLoreGroup extends Model
{
    /** @use HasFactory<\Database\Factories\CampaignFactory> */
    use HasFactory;

    protected $table = 'game_lore_groups';
    protected $primaryKey = 'id';
    protected $fillable = ['guid', 'user_id', 'name', 'created_at'];


}
