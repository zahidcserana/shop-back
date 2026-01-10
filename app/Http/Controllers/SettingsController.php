<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\MedicineType;
use Illuminate\Http\Request;


class SettingsController extends Controller
{
    public function typeSave(Request $request)
    {
        try {
            $user = $request->auth;
            $client_id = $user->pharmacy_id;
            $shop_id = $user->pharmacy_branch_id;

            if ($request->type_id) {
                $UpdateMedicineType = MedicineType::find($request->type_id);;
                $UpdateMedicineType->name = $request->type;
                $UpdateMedicineType->save();

                return response()->json(['status' => true, 'message' => "Product Type Updated Successfully!"], 201);
            } else {
                $exist = MedicineType::where('name', 'like', $request->type)->where('pharmacy_branch_id', $shop_id)->first();

                if (!$exist) {
                    $medicineType = new MedicineType();
                    $medicineType->name = $request->type;
                    $medicineType->pharmacy_id = $client_id;
                    $medicineType->pharmacy_branch_id = $shop_id;
                    $medicineType->save();

                    return response()->json(['status' => true, 'message' => "Product Type Added Successfully!"], 201);
                }

                return response()->json(['status' => false, 'message' => "Already exists!"]);
            }

            return response()->json(['status' => false, 'message' => "Please Check All the details!"], 302);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong!',
                'error'   => $th->getMessage(),
            ], 500);
        }
    }

    public function types(Request $request)
    {
        $user = $request->auth;
        $client_id = $user->pharmacy_id;
        $shop_id = $user->pharmacy_branch_id;

        $typeList = MedicineType::select('id', 'name')->where('pharmacy_id', $client_id)->orderBy('name', 'asc')->get();
        return response()->json($typeList);
    }

    public function destroyType($id)
    {
        MedicineType::destroy($id);
        return response()->json(['status' => true, 'message' => "Product Type deleted Successfully!"], 201);
    }

    public function brands(Request $request)
    {
        $user = $request->auth;
        $client_id = $user->pharmacy_id;
        $shop_id = $user->pharmacy_branch_id;

        $list = Brand::select('id', 'name')->where('pharmacy_id', $client_id)->orderBy('name', 'asc')->get();
        return response()->json($list);
    }

    public function brandSave(Request $request)
    {
        $user = $request->auth;
        $client_id = $user->pharmacy_id;
        $shop_id = $user->pharmacy_branch_id;

        if ($request->brand_id) {
            $brand = Brand::find($request->brand_id);;
            $brand->name = $request->name;
            $brand->save();

            return response()->json(['status' => true, 'message' => "Product Brand Updated Successfully!"], 201);
        } else {
            $exist = Brand::where('name', $request->name)
                ->where('pharmacy_branch_id', $shop_id)
                ->first();

            if (!$exist) {
                $brand = new Brand();
                $brand->name = $request->name;
                $brand->pharmacy_id = $client_id;
                $brand->pharmacy_branch_id = $shop_id;
                $brand->save();

                return response()->json(['status' => true, 'message' => "Product Brand Added Successfully!"], 201);
            }

            return response()->json(['status' => false, 'message' => "Already exists!"]);
        }

        return response()->json(['status' => false, 'message' => "Please Check All the details!"], 302);
    }

    public function destroyBrand($id)
    {
        Brand::destroy($id);
        return response()->json(['status' => true, 'message' => "Product Brand deleted Successfully!"], 201);
    }
}
