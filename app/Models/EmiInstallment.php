<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmiInstallment extends Model
{
    
    protected $fillable = [
        'emi_plan_id',
        'installment_no',
        'due_date',
        'amount',
        'paid_amount',
        'paid_date',
        'status'
    ];    

    public function emiPlan()
    {
        return $this->belongsTo(EmiPlan::class);
    }
}
