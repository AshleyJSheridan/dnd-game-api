<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CharInventory extends Model
{
    protected $table = 'char_inventory';
    protected $primaryKey = 'id';
    protected $fillable = ['guid', 'char_id', 'base_item_id', 'quantity', 'parent_id', 'created_at', 'updated_at'];
}
