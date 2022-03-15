<?php

namespace App\Models;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StockBalance extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    public function stockBalanceItems()
    {
        return $this->hasMany(StockBalanceItem::class);
    }

    public function openStockItems($user, $date)
    {
        $this->pharmacy_branch_id = $user->pharmacy_branch_id;
        $this->date_open = $date;
        $this->save();

        $products = new Product();
        $inventories = $products
            ->select(DB::raw('SUM(quantity) as quantity, medicine_id as product_id'))
            ->groupBy('medicine_id')
            ->get();

        foreach ($inventories as $inventory) {
            $stockBalanceItem = new StockBalanceItem();
            $stockBalanceItem->stock_balance_id = $this->id;
            $stockBalanceItem->product_id = $inventory->product_id;
            $stockBalanceItem->quantity_open = $inventory->quantity;
            $stockBalanceItem->save();
        }
    }

    public function closeStockItems()
    {
        $productsPurchaseQty = [];
        $productsSaleQty = [];
        $stockProductIds = $this->stockBalanceItems->pluck('product_id');

        foreach ($this->stockBalanceItems->pluck('product_id') as $i => $product) {
            $productsPurchaseQty[$product] = 0;
            $productsSaleQty[$product] = 0;
        }

        $productsPurchaseQty = $this->getPurchaseQty($productsPurchaseQty, $stockProductIds);
        $productsSaleQty = $this->getSaleQty($productsSaleQty, $stockProductIds);

        foreach ($this->stockBalanceItems as $stockBalanceItem) {
            $stockQty = Product::where('medicine_id', $stockBalanceItem->product_id)->sum('quantity');

            $stockBalanceItem->quantity_close = $stockQty;
            $stockBalanceItem->quantity_in = $productsPurchaseQty[$stockBalanceItem->product_id];
            $stockBalanceItem->quantity_out = $productsSaleQty[$stockBalanceItem->product_id];

            $stockBalanceItem->update();
        }
    }

    private function getSaleQty($productsSaleQty, $stockProductIds)
    {
        $where = array();
        $where = array_merge(array(['sales.pharmacy_branch_id', $this->pharmacy_branch_id]), $where);
        $where = array_merge(array(['sales.status', 'COMPLETE']), $where);

        $items = Sale::where($where)
            ->whereBetween('sales.sale_date', [$this->date_open, $this->date_close])
            ->whereIn('sale_items.medicine_id', $stockProductIds)
            ->join('sale_items', 'sales.id', '=', 'sale_items.sale_id')
            ->get();

        foreach ($items as $item) {
            $productsSaleQty[$item->medicine_id] += $item->quantity;
        }

        return $productsSaleQty;
    }

    private function getPurchaseQty($productsPurchaseQty, $stockProductIds)
    {
        $where = array();
        $where = array_merge(array(['orders.pharmacy_branch_id', $this->pharmacy_branch_id]), $where);
        $where = array_merge(array(['orders.status', 'ACCEPTED']), $where);

        $items = Order::where($where)
            ->whereBetween('purchase_date', [$this->date_open, $this->date_close])
            ->whereIn('order_items.medicine_id', $stockProductIds)
            ->join('order_items', 'orders.id', '=', 'order_items.order_id')
            ->get();

        foreach ($items as $item) {
            $productsPurchaseQty[$item->medicine_id] += $item->quantity;
        }

        return $productsPurchaseQty;
    }
}
