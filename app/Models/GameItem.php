<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class GameItem extends Model
{
    /** @use HasFactory<\Database\Factories\GameItemFactory> */
    use HasFactory;

    protected $table = 'game_items';
    protected $primaryKey = 'id';
    protected $fillable = ['name', 'description', 'cost', 'cost_unit', 'type', 'rarity', 'special', 'generated', 'weight'];

    public function isContainer(): bool
    {
        return $this->container === 1;
    }

    public function isStarterPack(): bool
    {
        return $this->type === 'pack';
    }

    public function starterItems(): BelongsToMany
    {
        return $this->belongsToMany(GameItem::class, 'char_starting_equipment_items', 'container_id', 'item_id')
            ->withPivot('quantity');
    }

    public function Proficiency(): HasOne
    {
        return $this->hasOne(CharProficiency::class, 'id', 'proficiency_id');
    }
}
