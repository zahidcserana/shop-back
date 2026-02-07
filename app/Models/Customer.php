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

    public function totalDue($payment) {
        $this->balance -= max(0, $payment);
        $this->save();
    }
}
