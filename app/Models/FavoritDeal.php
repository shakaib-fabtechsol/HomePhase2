<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FavoritDeal extends Model
{
    protected $fillable = [
        'user_id',
        'deal_id',
    ];
}
