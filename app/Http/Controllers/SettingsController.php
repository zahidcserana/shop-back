<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\MedicineType;
use Illuminate\Http\Request;


class SettingsController extends Controller
{
    public function typeSave(Request $request)
    {
        if ($request->type_id) {
            $UpdateMedicineType = MedicineType::find($request->type_id);;
            $UpdateMedicineType->name = $request->type;
            $UpdateMedicineType->save();

            return response()->json(['status' => true, 'message' => "Product Type Updated Successfully!"], 201);
        } else {
            $exist = MedicineType::where('name', 'like', $request->type)->first();

            if (!$exist) {
                $medicineType = new MedicineType();
                $medicineType->name = $request->type;
                $medicineType->save();

                return response()->json(['status' => true, 'message' => "Product Type Added Successfully!"], 201);
            }

            return response()->json(['status' => false, 'message' => "Already exists!"]);
        }

        return response()->json(['status' => false, 'message' => "Please Check All the details!"], 302);
    }

    public function types(Request $request)
    {
        $typeList = MedicineType::select('id', 'name')->orderBy('name', 'asc')->get();
        return response()->json($typeList);
    }

    public function destroyType($id)
    {
        MedicineType::destroy($id);
        return response()->json(['status' => true, 'message' => "Product Type deleted Successfully!"], 201);
    }

    public function brands(Request $request)
    {
        $list = Brand::select('id', 'name')->orderBy('name', 'asc')->get();
        return response()->json($list);
    }

    public function brandSave(Request $request)
    {
        if ($request->brand_id) {
            $brand = Brand::find($request->brand_id);;
            $brand->name = $request->name;
            $brand->save();

            return response()->json(['status' => true, 'message' => "Product Brand Updated Successfully!"], 201);
        } else {
            $exist = Brand::where('name', 'like', $request->name)->first();

            if (!$exist) {
                $brand = new Brand();
                $brand->name = $request->name;
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
