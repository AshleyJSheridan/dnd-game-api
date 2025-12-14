<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ItemStarterPack extends Model
{
    /** @use HasFactory<\Database\Factories\ItemStarterPackFactory> */
    use HasFactory;
    protected $table = 'char_starting_equipment';
    protected $primaryKey = 'id';
    const UPDATED_AT = null;
    const CREATED_AT = null;

    public function items(): BelongsToMany
    {
        return $this->belongsToMany(GameItem::class, 'char_starting_equipment_items', 'starting_equipment_id', 'item_id')
            ->withPivotValue('container_id', 0)
            ->withPivot('quantity')
            ->orderBy('id', 'asc');
    }
}
