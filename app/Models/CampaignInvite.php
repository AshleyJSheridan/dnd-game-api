<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class CampaignInvite extends Model
{
    /** @use HasFactory<\Database\Factories\CampaignFactory> */
    use HasFactory;

    protected $table = 'game_players';
    protected $primaryKey = 'id';
    protected $fillable = ['game_id', 'user_id', 'created_at', 'updated_at', 'status'];

    public function Campaign(): HasOne
    {
        return $this->hasOne(Campaign::class, 'id', 'game_id');
    }

    public function User(): HasOne
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }
}
