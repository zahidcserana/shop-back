<?php

namespace App\Traits;

use Illuminate\Http\Request;
use App\Models\PaymentType;

trait PaymentTypeTrait
{
    public function setPaymentType(Request $request) 
    {
        $methods = (array) $request->payment_methods;
        $branchId = $this->id;

        $this->paymentTypes()->update(['status' => 'INACTIVE']);

        foreach ($methods as $methodName) {
            $accountNo = ($methodName === 'Mobile Banking') ? $request->mobile_banking_account : null;

            PaymentType::updateOrCreate(
                [
                    'pharmacy_branch_id' => $branchId,
                    'name'               => $methodName
                ],
                [
                    'account_no' => $accountNo,
                    'status'     => 'ACTIVE'
                ]
            );
        }
    }
}