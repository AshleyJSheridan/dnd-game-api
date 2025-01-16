<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ItemStarterPack extends Model
{
    protected $table = 'char_starter_packs';
    protected $primaryKey = 'id';

    public function items(): BelongsToMany
    {
        return $this->belongsToMany(GameItem::class, 'char_starter_pack_items', 'starter_pack_id', 'item_id');
    }
}
