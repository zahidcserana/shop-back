<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\WarehouseStock;
use App\Models\Medicine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Services\WarehouseService;

class WarehouseController extends Controller
{
    public function stocks(string $id, Request $request)
    {
        $perPage = (int) $request->get('per_page', 10);
        $search  = $request->get('search');
        $sortBy  = $request->get('sort_by', 'id');
        $sortDir = $request->get('sort_dir', 'desc');

        $query = WarehouseStock::with('warehouse', 'medicine')
            ->where('warehouse_id', $id);

        // Filter 1: Already Expired
        if ($request->boolean('has_stock')) {
            $query->where("quantity", '>', 0);
        }

        // 🔍 Search
        if ($search) {
            $query->whereHas('medicine', function ($q) use ($request) {
                $q->where('brand_name', 'like', '%' . $str . '%')
                ->orWhere('barcode', 'like', '%' . $str . '%');
            });
        }

        // 🔃 Sorting (whitelist for security)
        $allowedSorts = [
            'id',
            'warehouse_id',
            'brand_name',
            'quantity',
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

    public function searchProduct(Request $request)
    {
        try {
            $str = $request->input('search');
            $warehouseId = $request->warehouse_id;
            $pharmacyId = $request->auth->pharmacy_id;
    
            $medicines = Medicine::query()
                ->select(
                    'medicines.id',
                    'medicines.barcode',
                    'medicines.brand_name',
                    'brands.name as brand_name_rel',
                    'medicine_types.name as type_name',
                    DB::raw('COALESCE(warehouse_stocks.quantity, 0) as stock')
                )
                ->leftJoin('brands', 'brands.id', '=', 'medicines.brand_id')
                ->leftJoin('medicine_types', 'medicine_types.id', '=', 'medicines.medicine_type_id')
                ->leftJoin('warehouse_stocks', function ($join) use ($warehouseId) {
                    $join->on('warehouse_stocks.medicine_id', '=', 'medicines.id')
                         ->where('warehouse_stocks.warehouse_id', '=', $warehouseId);
                })
                ->where(function ($q) use ($str) {
                    $q->where('medicines.brand_name', 'like', "%{$str}%")
                      ->orWhere('medicines.barcode', 'like', "%{$str}%");
                })
                ->whereHas('products', function ($q) use ($pharmacyId) {
                    $q->where('products.pharmacy_id', $pharmacyId);
                })
                ->orderBy('medicines.brand_name', 'asc')
                ->limit(100)
                ->get();
    
            return response()->json(
                $medicines->map(function ($medicine) {
                    return [
                        'id'      => $medicine->id,
                        'barcode' => $medicine->barcode,
                        'name'    => $medicine->brand_name,
                        'brand'   => $medicine->brand_name_rel ?? '',
                        'type'    => $medicine->type_name ?? '',
                        'stock'   => (int) $medicine->stock,
                    ];
                })
            );
    
        } catch (\Throwable $e) {
            \Log::error('Medicine search failed: ' . $e->getMessage());
    
            return response()->json([
                'success' => false,
                'message' => 'Search failed.',
            ], 500);
        }
    }
    
    public function storeStock(Request $request)
    {
        try {

            $user = $request->auth;
            $service = new WarehouseService();

            $warehouse = $service->createStock($request);

            return response()->json([
                'message' => 'Stock updated successfully',
                'data'    => []
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'data'   => 'Stock add unsuccessful!',
                'status' => false,
                'error'  => $th->getMessage(),
            ], 500);
        }
    }
}
