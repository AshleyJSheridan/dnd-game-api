<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Campaign extends Model
{
    protected $table = 'games';
    protected $primaryKey = 'id';
    protected $fillable = ['guid', 'name', 'user_id', 'created_at', 'description', 'state'];

    public function Maps(): HasMany
    {
        return $this->hasMany(CampaignMap::class, 'game_id', 'id');
    }
}
