<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\PharmacyBranch;
use App\Models\StockTransfer;
use App\Models\PaymentType;
use App\Models\Pharmacy;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\InventoryTransferService;

class StockTransferController extends Controller
{
    public function index(Request $request)
    {
        $perPage = (int) $request->get('per_page', 10);
        $search  = $request->get('search');
        $sortBy  = $request->get('sort_by', 'id');
        $sortDir = $request->get('sort_dir', 'desc');
        $pharmacyId = $request->auth->pharmacy_id;

        $query = StockTransfer::with('warehouse', 'branch', 'items')
            ->where('stock_transfers.pharmacy_id', $pharmacyId);

        if ($request->status) {
            $query->where('stock_transfers.status', $request->status);
        }

        // 🔃 Sorting (whitelist for security)
        $allowedSorts = [
            'id',
            'warehouse_id',
            'pharmacy_branch_id',
            'status',
        ];

        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortDir === 'asc' ? 'asc' : 'desc');
        }

        $data = $query->select('stock_transfers.*')->paginate($perPage);

        return response()->json([
            'status' => true,
            'data'   => $data->items(),
            'total' => $data->total()
        ]);
    }

    public function store(Request $request)
    {
        try {

            $user = $request->auth;
            $service = new InventoryTransferService();

            $transfer = $service->createTransfer(
                $user->pharmacy_id,
                $request->warehouse_id,
                $request->pharmacy_branch_id,
                $request->items,
                $user->id,
                $request->remarks ?? ''
            );

            return response()->json([
                'message' => 'Stock transferred successfully',
                'data'    => $transfer->load('items.medicine')
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'data'   => 'Product add unsuccessful!',
                'status' => false,
                'error'  => $th->getMessage(),
            ], 500);
        }
    }

    public function approve(string $id, Request $request)
    {
        try {

            $user = $request->auth;
            $stockTransfer = StockTransfer::findOrFail($id);

            if (!$stockTransfer || $stockTransfer->status == StockTransfer::STATUS_RECEIVED) {
                return response()->json([
                    'data'   => 'Transfer already processed',
                    'status' => false,
                    'error'  => 'Transfer already processed',
                ], 500);
            }
            
            $service = new InventoryTransferService();

            $transfer = $service->approveTransfer(
                $stockTransfer,
                $request->items,
                $request->remarks ?? ''
            );

            return response()->json([
                'message' => 'Stock received successfully',
                'data'    => $transfer->load('items.medicine')
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'data'   => 'Received unsuccessful!',
                'status' => false,
                'error'  => $th->getMessage(),
            ], 500);
        }
    }

    public function view(string $id, Request $request)
    {
        try {

            $user = $request->auth;
            $stockTransfer = StockTransfer::findOrFail($id);

            if (!$stockTransfer) {
                return response()->json([
                    'data'   => 'Not approved!',
                    'status' => false,
                    'error'  => 'Not approved!',
                ], 500);
            }

            $data = $stockTransfer->load('warehouse', 'branch', 'items', 'items.medicine');

            foreach ($data->items as $item) {
                $item->batch = Product::where('medicine_id', $item->medicine_id)->where('pharmacy_id', $user->pharmacy_id)->pluck('batch_no');
                $item->batch_no = $item->batch[0] ?? ''; 
            }

            return response()->json($data);
        } catch (\Throwable $th) {
            return response()->json([
                'data'   => 'Product add unsuccessful!',
                'status' => false,
                'error'  => $th->getMessage(),
            ], 500);
        }
    }

}
