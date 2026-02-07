<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\CustomerDocument;

class Customer extends Model
{
    protected $guarded = [];
    protected $fillable = [
        'pharmacy_branch_id',
        'code',
        'mobile',
        'name',
        'email',
        'balance',
        'pharmacy_id'
    ];

    public function documents()
    {
        return $this->hasMany(CustomerDocument::class);
    }
}
