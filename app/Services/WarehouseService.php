<?php

namespace App\Services;

use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use App\Models\WarehouseStock;
use App\Models\Product;
use App\Models\Medicine;
use Illuminate\Support\Facades\DB;
use Exception;
use Illuminate\Http\Request;

class WarehouseService
{
    /**
     * Create stock
     */
    public function createStock(Request $request) 
    {
        return DB::transaction(function () use ($request) {

            $this->validateItem($request);

            $stock = WarehouseStock::firstOrNew([
                'warehouse_id' => $request->warehouse_id,
                'medicine_id'  => $request->product_id
            ]);
            
            $stock->quantity = max(0, ($stock->quantity ?? 0) + $request->quantity);
            $stock->save();
        });
    }

    /* =====================================================
        VALIDATION
    ===================================================== */

    protected function validateItem(Request $request)
    {
        if (
            !isset($request->product_id) ||
            !isset($request->quantity)
        ) {
            throw new Exception('Invalid stock item structure');
        }

        // if ($request->quantity <= 0) {
        //     throw new Exception('Stock quantity must be greater than zero');
        // }
    }
}
