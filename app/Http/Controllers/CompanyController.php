<?php

namespace App\Http\Controllers;

use App\Models\MedicineCompany;
use Illuminate\Http\Request;
use DB;
class CompanyController extends Controller
{
    public function index()
    {
        $companies = MedicineCompany::all();

        $data = array();
        foreach ($companies as $company) {
            $data[] = $company->company_name;
        }
        return response()->json($data);
    }

    public function getCompaniesByInventory(Request $request)
    {
      $user = $request->auth;
      $companyIds = DB::table('products')
                        ->where('pharmacy_branch_id', $user->pharmacy_branch_id)
                        ->select('company_id')->distinct()
                        ->pluck('company_id');

      $companies = MedicineCompany::whereIn('id', $companyIds)->get();
      $data = array();
      foreach ($companies as $company) {
          $data[] = ['id'=>$company->id, 'name' => $company->company_name];
      }
      return response()->json($data);
    }

    public function companyList()
    {
        $companies = MedicineCompany::orderBy('company_name', 'asc')->get();

        $data = array();
        foreach ($companies as $company) {
            $data[] = array('id' => $company->id, 'name' => $company->company_name, );
        }
        return response()->json($data);
    }
}
