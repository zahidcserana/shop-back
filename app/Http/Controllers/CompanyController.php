<?php

namespace App\Http\Controllers;

use App\Models\MedicineCompany;
use Illuminate\Http\Request;
use DB;

class CompanyController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->auth;
        $client_id = $user->pharmacy_id;
        $shop_id = $user->pharmacy_branch_id;

        $companies = MedicineCompany::where('pharmacy_id', $client_id)->get();

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
            $data[] = ['id' => $company->id, 'name' => $company->company_name];
        }
        return response()->json($data);
    }

    public function companyList(Request $request)
    {
        $user = $request->auth;
        $client_id = $user->pharmacy_id;
        $shop_id = $user->pharmacy_branch_id;

        $companies = MedicineCompany::where('pharmacy_id', $client_id)->orderBy('company_name', 'asc')->get();

        $data = array();
        foreach ($companies as $company) {
            $data[] = array(
                'id' => $company->id,
                'name' => $company->company_name,
                'address' => $company->company_address,
                'contact_person' => $company->company_contact_person,
                'mobile' => $company->company_contact_person_mobile,
                'email' => $company->company_contact_person_email,
                'status' => $company->company_active_status
            );
        }
        return response()->json($data);
    }

    public function store(Request $request)
    {
        $user = $request->auth;
        $client_id = $user->pharmacy_id;
        $shop_id = $user->pharmacy_branch_id;

        $addMedicineCompany = new MedicineCompany();
        $addMedicineCompany->company_name = $request->name;
        $addMedicineCompany->company_address = $request->address;
        $addMedicineCompany->company_contact_person = $request->contact_person;
        $addMedicineCompany->company_contact_person_mobile = $request->mobile;
        $addMedicineCompany->company_contact_person_email = $request->email;
        $addMedicineCompany->pharmacy_id = $client_id;
        $addMedicineCompany->pharmacy_branch_id = $shop_id;
        $addMedicineCompany->save();

        return response()->json(['success' => true, 'message' => "Supplier saved successfully!"]);
    }

    public function update(Request $request, $id)
    {
        $medicineCompany = MedicineCompany::find($id);
        $medicineCompany->company_name = $request->name;
        $medicineCompany->company_address = $request->address;
        $medicineCompany->company_contact_person = $request->contact_person;
        $medicineCompany->company_contact_person_mobile = $request->mobile;
        $medicineCompany->company_contact_person_email = $request->email;
        $medicineCompany->company_active_status = $request->status;
        $medicineCompany->save();

        return response()->json(['success' => true, 'message' => "Supplier updated successfully!"]);
    }

    public function destroy($id)
    {
        if (MedicineCompany::destroy($id)) {
            return response()->json(['success' => true, 'message' => "Supplier deleted successfully!"]);
        }

        return response()->json(['success' => false]);
    }
}
