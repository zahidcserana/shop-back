<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $guarded = [];
    protected $fillable = ['code', 'mobile', 'name', 'balance'];
}
