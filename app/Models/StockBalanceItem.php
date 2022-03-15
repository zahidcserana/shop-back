<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\SoftDeletes;

class StockBalanceItem extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    public function medicine()
    {
        return $this->belongsTo('App\Models\Medicine', 'product_id');
    }
}
