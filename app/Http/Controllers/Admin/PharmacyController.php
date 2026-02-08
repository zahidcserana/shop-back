<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Pharmacy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PharmacyController extends Controller
{
    public function index(Request $request)
    {
        $perPage = (int) $request->get('per_page', 10);
        $search  = $request->get('search');
        $sortBy  = $request->get('sort_by', 'id');
        $sortDir = $request->get('sort_dir', 'desc');

        $query = Pharmacy::query();

        // 🔍 Search
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('pharmacy_shop_code', 'like', "%{$search}%")
                ->orWhere('pharmacy_shop_name', 'like', "%{$search}%")
                ->orWhere('pharmacy_shop_owner_name', 'like', "%{$search}%");
            });
        }

        // 🔃 Sorting (whitelist for security)
        $allowedSorts = [
            'id',
            'pharmacy_shop_code',
            'pharmacy_shop_name',
            'pharmacy_shop_owner_name',
        ];

        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortDir === 'asc' ? 'asc' : 'desc');
        }

        $data = $query->paginate($perPage);

        return response()->json([
            'status' => true,
            'data'   => $data->items(),
            'total' => $data->total()
        ]);
    }

    public function store(Request $request)
    {
        try {
            DB::beginTransaction();

            $this->validate($request, [
                'pharmacy_shop_code' => 'required|unique:pharmacies,pharmacy_shop_code',
                'pharmacy_shop_name' => 'required',
            ]);

            $pharmacy = Pharmacy::create([
                'pharmacy_shop_code' => $request->pharmacy_shop_code,
                'pharmacy_shop_name' => $request->pharmacy_shop_name,
                'pharmacy_shop_owner_name' => $request->pharmacy_shop_owner_name,
                'pharmacy_shop_licence_no' => $request->pharmacy_shop_licence_no,
                'pharmacy_shop_branch_owner_nid' => $request->pharmacy_shop_branch_owner_nid,
                'pharmacy_shop_license_exp_date' => $request->pharmacy_shop_license_exp_date,
                'pharmacy_shop_dgda_verification_status' => $request->pharmacy_shop_dgda_verification_status,
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'data' => $pharmacy
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'status' => false,
                'errors' => $e->errors(),
            ], 422);

        } catch (\Throwable $th) {
            DB::rollBack();

            return response()->json([
                'status' => false,
                'message' => 'Client creation failed',
                'error' => $th->getMessage(), // ✅ correct variable
            ], 500);
        }
    }

    public function show($id)
    {
        return response()->json(Pharmacy::findOrFail($id));
    }

    public function update(Request $request, $id)
    {
        try {
            DB::beginTransaction();

            $client = Pharmacy::findOrFail($id);

            $this->validate($request, [
                'pharmacy_shop_code' => 'required|unique:pharmacies,pharmacy_shop_code,' . $client->id,
                'pharmacy_shop_name' => 'required',
                'pharmacy_shop_owner_name' => 'required',
            ]);

            $client->update($request->all());

            DB::commit();

            return response()->json([
                'status' => true,
                'data' => $client
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'status' => false,
                'errors' => $e->errors()
            ], 422);

        } catch (\Throwable $th) {
            DB::rollBack();

            return response()->json([
                'status' => false,
                'message' => 'Client update failed',
                'error' => $th->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        Pharmacy::findOrFail($id)->delete();

        return response()->json([
            'status' => true,
            'message' => 'Pharmacy deleted'
        ]);
    }
}
