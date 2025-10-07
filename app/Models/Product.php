<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use SoftDeletes;

    public function company()
    {
        return $this->belongsTo(MedicineCompany::class, 'company_id');
    }
}
