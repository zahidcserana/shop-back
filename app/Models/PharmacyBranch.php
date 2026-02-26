<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\Request;
use App\Traits\ImageUploadTrait;
use App\Traits\PaymentTypeTrait;

class PharmacyBranch extends Model
{
    use SoftDeletes, ImageUploadTrait, PaymentTypeTrait;

    public const STATUS_ACTIVE = 'YES';

    protected $fillable = [
        'pharmacy_id',
        'branch_name',
        'branch_city',
        'branch_area',
        'branch_full_address',
        'branch_lat',
        'branch_long',
        'branch_mobile',
        'branch_alt_mobile',
        'branch_image',
        'branch_contact_person_name',
        'branch_contact_person_mobile',
        'branch_contact_person_alt_mobile',
        'branch_model_pharmacy_status',
        'subscription_period',
        'subscription_count',
        'branch_config',
    ];

    protected $casts = [
        'branch_config' => 'array',
    ];

    public function pharmacy()
    {
        return $this->belongsTo(Pharmacy::class);
    }

    public function paymentTypes()
    {
        return $this->hasMany(PaymentType::class, 'pharmacy_branch_id');
    }

    public function admin()
    {
        return $this->hasOne(User::class, 'pharmacy_branch_id');
    }

    public function transfers()
    {
        return $this->hasMany(StockTransfer::class, 'pharmacy_branch_id');
    }

    public function products()
    {
        return $this->hasMany(Product::class, 'pharmacy_branch_id');
    }

}
