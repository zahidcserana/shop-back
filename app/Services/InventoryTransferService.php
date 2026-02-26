<?php

namespace App\Services;

use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use App\Models\WarehouseStock;
use App\Models\Product;
use App\Models\Medicine;
use Illuminate\Support\Facades\DB;
use Exception;

class InventoryTransferService
{
    /**
     * Create and send warehouse -> branch transfer
     */
    public function createTransfer(
        int $pharmacyId,
        int $warehouseId,
        int $branchId,
        array $items,
        int $userId,
        string $remarks = null
    ) {
        if (empty($items)) {
            throw new Exception('Transfer items cannot be empty');
        }

        return DB::transaction(function () use (
            $pharmacyId,
            $warehouseId,
            $branchId,
            $items,
            $userId,
            $remarks
        ) {

            // Create transfer header
            $transfer = StockTransfer::create([
                'pharmacy_id'        => $pharmacyId,
                'warehouse_id'       => $warehouseId,
                'pharmacy_branch_id' => $branchId,
                'reference_no'       => $this->generateReference(),
                'status'             => StockTransfer::STATUS_SENT,
                'created_by'         => $userId,
                'remarks'            => $remarks,
            ]);

            foreach ($items as $item) {

                $this->validateItem($item);

                $this->deductWarehouseStock(
                    $warehouseId,
                    $item['product_id'],
                    $item['quantity']
                );

                StockTransferItem::create([
                    'stock_transfer_id' => $transfer->id,
                    'medicine_id'       => $item['product_id'],
                    'quantity'          => $item['quantity'],
                ]);
            }

            return $transfer;
        });
    }

    public function approveTransfer(
        StockTransfer $transfer,
        array $items,
        string $remarks = null
    ) {
        if (empty($items)) {
            throw new Exception('Transfer items cannot be empty');
        }

        return DB::transaction(function () use (
            $transfer,
            $items,
            $remarks
        ) {

            foreach ($items as $item) {

                $this->validateReceiveItem($item);

                $stockTransferItem = StockTransferItem::findOrFail($item['stock_transfer_item_id']);

                if ($stockTransferItem) {
                    $this->addBranchStock(
                        $transfer->pharmacy_branch_id,
                        $stockTransferItem->medicine_id,
                        $item['batch_no'] ?? Medicine::DEFAULT_BATCH,
                        $item['quantity']
                    );
                }
            }

            $transfer->status = StockTransfer::STATUS_RECEIVED;
            $transfer->update();

            return $transfer;
        });
    }

    /* =====================================================
        VALIDATION
    ===================================================== */

    protected function validateItem(array $item)
    {
        if (
            !isset($item['product_id']) ||
            !isset($item['quantity'])
        ) {
            throw new Exception('Invalid transfer item structure');
        }

        if ($item['quantity'] <= 0) {
            throw new Exception('Transfer quantity must be greater than zero');
        }
    }

    protected function validateReceiveItem(array $item)
    {
        if (
            !isset($item['stock_transfer_item_id']) ||
            !isset($item['quantity'])
        ) {
            throw new Exception('Invalid transfer item structure');
        }

        if ($item['quantity'] <= 0) {
            throw new Exception('Transfer quantity must be greater than zero');
        }
    }

    /* =====================================================
        WAREHOUSE STOCK
    ===================================================== */

    protected function deductWarehouseStock(
        int $warehouseId,
        int $medicineId,
        float $qty
    ) {
        $stock = WarehouseStock::where('warehouse_id', $warehouseId)
            ->where('medicine_id', $medicineId)
            ->lockForUpdate()
            ->first();

        if (!$stock) {
            throw new Exception("Medicine ID {$medicineId} not found in warehouse");
        }

        if ($stock->quantity < $qty) {
            throw new Exception("Insufficient warehouse stock for medicine ID {$medicineId}");
        }

        $stock->quantity -= $qty;
        $stock->save();
    }

    /* =====================================================
        BRANCH STOCK (products table)
    ===================================================== */

    protected function addBranchStock(
        int $branchId,
        int $medicineId,
        string $batchNo,
        float $qty
    ) {
        $product = Product::where('pharmacy_branch_id', $branchId)
            ->where('medicine_id', $medicineId)
            ->where('batch_no', $batchNo)
            ->lockForUpdate()
            ->first();

        if ($product) {
            $product->quantity += $qty;
            $product->save();
        } else {
            Product::create([
                'pharmacy_branch_id' => $branchId,
                'medicine_id'        => $medicineId,
                'quantity'           => $qty,
            ]);
        }
    }

    /* =====================================================
        UTILITIES
    ===================================================== */

    protected function generateReference(): string
    {
        return 'TR-' . date('YmdHis') . '-' . random_int(100, 999);
    }
}
