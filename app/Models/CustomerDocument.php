<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerDocument extends Model
{
    protected $fillable = [
        'customer_id',
        'pharmacy_branch_id',
        'type',
        'file_name',
        'file_path',
        'file_size',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
