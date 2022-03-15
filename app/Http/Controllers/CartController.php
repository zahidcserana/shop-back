<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Medicine;
use App\Models\MedicineCompany;
use Illuminate\Http\Request;
use Validator;
use DB;

class CartController extends Controller
{
    public function addToCart(Request $request)
    {
        $user = $request->auth;
        $data = $request->all();

        $this->validate($request, [
            'medicine_id' => 'required',
            'quantity' => 'required'
        ]);
        $cart = Cart::where('token', $data['token'])->first();
        if ($cart && CartItem::where('medicine_id', $data['medicine_id'])->where('cart_id', $cart->id)->first()) {
            return response()->json(['success' => false, 'error' => 'Already added this item!']);
        }
        $cartModel = new Cart();
        $cart = $cartModel->AddToCart($data, $user);

        return response()->json($cart);
    }

    public function view($cartToken)
    {

        $cart = Cart::where('token', $cartToken)->first();
        if (empty($cart)) {
            return response()->json(['success' => false, 'error' => 'Invalid Cart Token!']);
        }
        $cartModel = new Cart();
        $result = $cartModel->getCartDetails($cart->id);

        return response()->json($result);
    }

    public function tokenCheck($cartToken)
    {

        $cart = Cart::where('token', $cartToken)->first();
        if (empty($cart)) {
            return response()->json(['status' => false]);
        }
        return response()->json(['status' => true]);
    }

    public function quantityUpdate(Request $request)
    {
        $data = $request->all();
        $item = CartItem::find($data['id'])->first();
        $product = DB::table('products')
            ->select(DB::raw('SUM(quantity) as available_quantity'))
            ->where('medicine_id', $item->medicine_id)
            ->first();

        if ($data['increment'] == 1 && ($product->available_quantity < ($item->quantity + 1))) {
            return response()->json(['success' => false, 'error' => 'Only ' . $product->available_quantity . ' Pcs is available']);
        }
        $cartModel = new Cart();
        $cartUpdate = $cartModel->quantityUpdate($data);

        return response()->json($cartUpdate);
    }
    public function priceUpdate(Request $request)
    {
        $data = $request->all();

        $this->validate($request, [
            'item_id' => 'required',
            'item_price' => 'required'
        ]);
        $cartModel = new Cart();
        $cartUpdate = $cartModel->priceUpdate($data);

        return response()->json($cartUpdate);
    }

    public function deleteItem(Request $request)
    {
        $data = $request->all();
        $cartItemModel = new CartItem();
        $result = $cartItemModel->deleteItem($data);

        return response()->json($result);
    }

    public function destroy($token)
    {
        if ($cart = Cart::where('token', $token)->first()) {
            Cart::destroy($cart->id);
            CartItem::where(['cart_id' => $cart->id])->delete();

            return response()->json(['status' => true]);
        }
        return response()->json(['status' => false]);
    }
}
