<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends Controller
{ 
  public function search(Request $request)
  {
    $data = Customer::where('pharmacy_branch_id', $request->auth->pharmacy_branch_id)
        ->where(function ($query) use ($request) {
            $query->where('name', 'like', '%' . $request->q . '%')
                  ->orWhere('mobile', 'like', '%' . $request->q . '%');
        })
        ->limit(20)
        ->get(['id', 'name', 'mobile']);

    return response()->json($data);
  }
  
}
