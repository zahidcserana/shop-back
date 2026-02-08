<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\CustomerDocument;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use SoftDeletes;

    public const STATUS_ACTIVE = 'ACTIVE';
    public const STATUS_INACTIVE = 'INACTIVE';

    protected $fillable = [
        'pharmacy_branch_id',
        'code',
        'mobile',
        'name',
        'email',
        'address',
        'balance',
        'status'
    ];

    public function documents()
    {
        return $this->hasMany(CustomerDocument::class);
    }

    public function updateBalance($sale, $payAmount): void
    {
        if ($payAmount <= 0) {
            return;
        }
    
        $customer = Customer::where('pharmacy_branch_id', $sale->pharmacy_branch_id)
            ->where(function ($q) use ($sale) {
                $q->where('code', $sale->customer_mobile)
                  ->orWhere('mobile', $sale->customer_mobile);
            })
            ->lockForUpdate() // 🔐 important inside transaction
            ->first();
    
        if (!$customer || $customer->balance <= 0) {
            return;
        }
    
        $amount = min($customer->balance, $payAmount);
        $customer->decrement('balance', $amount);
    }
    

}
