<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PaymentType extends Model
{
    use SoftDeletes;
    public static $TYPE_CASH = 'Cash';

    protected $fillable = [
        'pharmacy_branch_id',
        'name',
        'account_no',
        'status'
    ];

}
