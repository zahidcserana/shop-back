<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmiInstallment extends Model
{
    
    protected $fillable = [
        'sale_id',
        'customer_id',
        'installment_no',
        'due_date',
        'amount',
        'paid_amount',
        'paid_date',
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
