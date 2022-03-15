<?php

namespace App\Http\Controllers;

use App\Models\User;
use \Illuminate\Http\Request;
use App\Models\MedicineCompany;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Facades\DB;

class MrController extends Controller
{
    public function index()
    {
        $mrs = DB::table('mrs')->get();
        foreach ($mrs as $mr) {
            $mr->company = DB::table('medicine_companies')->where('id', $mr->company_id)->pluck('company_name');
        }

        return response()->json($mrs);
    }
    public function smartMrOrderList(Request $request)
    {
        $pageNo = $request->query('page_no') ?? 1;
        $limit = $request->query('limit') ?? 100;
        $offset = (($pageNo - 1) * $limit);
        $where = array();
        $user = $request->auth;
        $where = array_merge(array(['mr_id', $user->id]), $where);
        $orderModel = new Order();
        $orders = $orderModel->getAllItem($where, $offset, $limit);
        return response()->json($orders);
    }

    public function addMR(Request $request)
    {
        $data = $request->all();
        $companyData = MedicineCompany::where('company_name', 'like', $data['mr_company'])->first();
        if (!$companyData) {
            return response()->json(['success' => false, 'error' => 'Invalid Company Name']);
        }
        /* create MR */
        $input = array(
            'mr_address' => $data['mr_address'] ?? '',
            'company_id' => $companyData->id,
            'mr_full_name' => $data['mr_full_name'] ?? '',
            'mr_mobile' => $data['mr_mobile'],
            'active_status' => 'ACTIVE',
            'created_at' => date('Y-m-d H:i:s'),
        );
        DB::table('mrs')->insertGetId($input);

        $mrs = DB::table('mrs')->get();
        foreach ($mrs as $mr) {
            $mr->company = DB::table('medicine_companies')->where('id', $mr->company_id)->pluck('company_name');
        }
        return response()->json(['success' => true, 'data' => $mrs]);
    }

    public function add(Request $request)
    {
        $data = $request->all();
        $this->validate($request, [
            'name' => 'required',
            'user_mobile' => 'required',
            'mr_company' => 'required',
            'firebase_id' => 'required',
        ]);
        /* create user */

        $data = $request->all();
        $data['user_type'] = 'MR';
        $data['created_at'] = date('Y-m-d H:i:s');
        $userModel = new User();
        $user = $userModel->create($data);
        $userid = $user->id . substr($user['user_mobile'], -4);

        $user->update(['userid' => $userid]);

        /* create MR */
        $input = array(
            'mr_profile_pic' => $data['mr_profile_pic'] ?? '',
            'mr_lat' => $data['mr_lat'] ?? '',
            'mr_city' => $data['mr_city'] ?? '',
            'firebase_id' => $data['firebase_id'] ?? '',
            'mr_area' => $data['mr_area'] ?? '',
            'mr_long' => $data['mr_long'] ?? '',
            'mr_full_name' => $data['name'] ?? '',
            'mr_mobile' => $data['user_mobile'],
            'mr_address' => $data['mr_address'] ?? '',
            'company_id' => $data['mr_company'],
            'active_status' => 'ACTIVE',
            'created_at' => date('Y-m-d H:i:s'),
            'userid' => $userid,
        );
        $mrId = DB::table('mrs')->insertGetId($input);

        return response()->json(['success' => true, 'data' => DB::table('mrs')->where('id', $mrId)->first()]);
    }

    public function update(Request $request, $mrId)
    {
        $data = $request->all();
        $companyData = MedicineCompany::where('company_name', 'like', $data['mr_company'])->first();
        if (!$companyData) {
            return response()->json(['success' => false, 'error' => 'Invalid Company Name']);
        }
        $input = array(
            'mr_full_name' => $data['mr_full_name'],
            'mr_mobile' => $data['mr_mobile'],
            'mr_address' => $data['mr_address'],
            'company_id' => $companyData->id,
            'active_status' => 'ACTIVE',
            'updated_at' => date('Y-m-d H:i:s'),
        );
        DB::table('mrs')->where('id', $mrId)->update($input);

        $mrs = DB::table('mrs')->get();
        foreach ($mrs as $mr) {
            $mr->company = DB::table('medicine_companies')->where('id', $mr->company_id)->pluck('company_name');
        }

        return response()->json(['success' => true, 'data' => $mrs]);
    }
}
