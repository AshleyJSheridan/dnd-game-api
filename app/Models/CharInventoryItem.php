<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class CharInventoryItem extends Model
{
    use SoftDeletes;

    protected $table = 'char_inventory';
    protected $primaryKey = 'id';
    protected $fillable = ['guid', 'char_id', 'base_item_id', 'quantity', 'parent_id', 'created_at', 'updated_at'];

    public function Item(): HasOne
    {
        return $this->hasOne(GameItem::class, 'id', 'base_item_id');
    }

    public function Character(): HasOne
    {
        return $this->hasOne(Character::class, 'id', 'char_id');
    }

    public function Items(): HasMany
    {
        return $this->hasMany(CharInventoryItem::class, 'parent_id', 'id');
    }
}
