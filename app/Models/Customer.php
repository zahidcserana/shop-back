<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $guarded = [];
    protected $fillable = [
        'pharmacy_id',
        'pharmacy_branch_id',
        'code',
        'mobile',
        'name',
        'balance'
    ];
}
