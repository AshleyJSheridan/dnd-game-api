<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Campaign extends Model
{
    /** @use HasFactory<\Database\Factories\CampaignFactory> */
    use HasFactory;

    protected $table = 'games';
    protected $primaryKey = 'id';
    protected $fillable = ['guid', 'name', 'user_id', 'created_at', 'description', 'state'];

    public function Maps(): HasMany
    {
        return $this->hasMany(CampaignMap::class, 'game_id', 'id');
    }

    public function Characters(): BelongsToMany
    {
        return $this->belongsToMany(Character::class, 'game_character', 'game_id', 'char_id');
    }
}
