<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PharmacyBranch extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    public function pharmacy()
    {
        return $this->belongsTo('App\Models\Pharmacy');
    }
}
