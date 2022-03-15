<?php

namespace App\Exports;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\MedicineCompany;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class PurchaseExport implements FromView
{
    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }
    
    public function view(): View
    {
        $purchase_data = $this->data;

        return view('purchase.purchase_report_to_excel', [
            'purchase_data' => $purchase_data
        ]);
    }
}