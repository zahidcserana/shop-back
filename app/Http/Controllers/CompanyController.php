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
            $data[] = ['id' => $company->id, 'name' => $company->company_name];
        }
        return response()->json($data);
    }

    public function companyList()
    {
        return response()->json($this->getCompanyList());
    }

    public function getCompanyList()
    {
        $companies = MedicineCompany::orderBy('company_name', 'asc')->get();

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
        return $data;
    }

    public function store(Request $request)
    {
        $addMedicineCompany = new MedicineCompany();
        $addMedicineCompany->company_name = $request->name;
        $addMedicineCompany->company_address = $request->address;
        $addMedicineCompany->company_contact_person = $request->contact_person;
        $addMedicineCompany->company_contact_person_mobile = $request->mobile;
        $addMedicineCompany->company_contact_person_email = $request->email;
        $addMedicineCompany->save();

        return response()->json(['success' => true, 'message' => "Supplier saved successfully!", 'data' => $this->getCompanyList()]);
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

        return response()->json(['success' => true, 'message' => "Supplier updated successfully!", 'data' => $this->getCompanyList()]);
    }

    public function destroy($id)
    {
        if (MedicineCompany::destroy($id)) {
            return response()->json(['success' => true, 'message' => "Supplier deleted successfully!", 'data' => $this->getCompanyList()]);
        }

        return response()->json(['success' => false]);
    }
}
