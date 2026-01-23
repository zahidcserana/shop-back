<?php

namespace App\Http\Controllers;

use DateTime;
use Validator;
use Carbon\Carbon;
use App\Models\EmiInstallment;
use App\Models\Cart;
use App\Models\Sale;
use App\Models\Product;
use App\Models\Customer;
use App\Models\Order;
use App\Models\CartItem;
use App\Models\Medicine;
use App\Models\SaleItem;
use Barryvdh\DomPDF\PDF;
use App\Models\OrderItem;
use App\Models\PaymentType;
use Illuminate\Http\Request;
use App\Models\MedicineCompany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\App;

class CustomerController extends Controller
{ 
  public function search(Request $request)
  {
    $data = Customer::where('name', 'like', '%' . $request->q . '%')
        ->orWhere('mobile', 'like', '%' . $request->q . '%')
        ->limit(20)
        ->get(['id', 'name', 'mobile']);

    return response()->json($data);
  }

}
