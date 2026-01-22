<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmiPlan extends Model
{
    protected $fillable = [
        'sale_id',
        'customer_id',
        'total_amount',
        'down_payment',
        'emi_amount',
        'total_installments',
        'start_date',
        'interest_rate',
        'status'
    ];

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
