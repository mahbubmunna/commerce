<?php

namespace App\Http\Controllers\API;

use App\FlashDeal;
use Illuminate\Http\Request;
use App\Product;
use App\SubSubCategory;
use App\Category;
use Session;
use App\Color;
use Auth;
use JWTAuth;
use App\User;
use Response;
use Validator;
use JWTFactory;
use App\Http\Controllers\Controller;
use DB;

class CartApiController extends Controller
{
    public function index(Request $request){
        $result['cart'] = $request->session()->get('cart');
        return $this->sendResponse($result, 'Cart retrived successfully.');
    }

    public function addToCart(Request $request)
    {
        $product = Product::find($request->id);

        $data = array();
        $data['id'] = $product->id;
        $data['name'] = $product->name;
        $data['photos'] = json_decode($product->photos);
        $data['flash_deal_discount'] = 0;
        $data['product_discount'] = 0;
        $data['shipping_type'] = 'home_delivery';
        $str = '';
        $tax = 0;

        //check the color enabled or disabled for the product
        if($request->has('size')){
            $data['size'] = $request['size'];
        }

        //check the color enabled or disabled for the product
        if($request->has('color')){
            $data['color'] = $request['color'];
            $str = Color::where('code', $request['color'])->first()->name;
        }

        //Gets all the choice values of customer choice option and generate a string like Black-S-Cotton
        foreach (json_decode(Product::find($request->id)->choice_options) as $key => $choice) {
            $data[$choice->name] = $request[$choice->name];
            if($str != null){
//                $str .= '-'.str_replace(' ', '', $request[$choice->name]);
                $str .= '-'.str_replace(' ', '', $request[$choice->name]);
            }
            else{
                $str .= str_replace(' ', '', $request[$choice->name]);
            }
        }

        //Check the string and decreases quantity for the stock
        if($str != null){
            $variations = json_decode($product->variations);
            $price = $variations->$str->price;
            if($variations->$str->qty >= $request['quantity']){
                 $variations->$str->qty -= $request['quantity'];
                 $product->variations = json_encode($variations);
                 $product->save();
            }
            else{
//                return view('frontend.partials.outOfStockCart');
                return $this->sendError('Product Out Of Stock.');
            }
        }
        else{
            $price = $product->unit_price;
        }
        //discount calculation based on flash deal and regular discount
        //calculation of taxes
        $flash_deals = FlashDeal::where('status', 1)->get();

        foreach ($flash_deals as $flash_deal) {
            if ($flash_deal != null && $flash_deal->status == 1  && strtotime(date('d-m-Y')) >= $flash_deal->start_date && strtotime(date('d-m-Y')) <= $flash_deal->end_date && \App\FlashDealProduct::where('flash_deal_id', $flash_deal->id)->where('product_id', $product->id)->first() != null) {
                $flash_deal_product = \App\FlashDealProduct::where('flash_deal_id', $flash_deal->id)->where('product_id', $product->id)->first();
                if($flash_deal_product->discount_type == 'percent'){
                    $price -= ($price*$flash_deal_product->discount)/100;
                    $data['flash_deal_discount'] = ($price*$flash_deal_product->discount)/100;
                }
                elseif($flash_deal_product->discount_type == 'amount'){
                    $price -= $flash_deal_product->discount;
                    $data['flash_deal_discount'] = $flash_deal_product->discount;
                }
                break;
            }
            else{
                if($product->discount_type == 'percent'){
                    $price -= ($price*$product->discount)/100;
                    $data['product_discount'] = ($price*$product->discount)/100;
                }
                elseif($product->discount_type == 'amount'){
                    $price -= $product->discount;
                    $data['product_discount'] = $product->discount;
                }
            }
        }

        if($product->tax_type == 'percent'){
            $tax = ($price*$product->tax)/100;
            $price += $tax;
        }
        elseif($product->tax_type == 'amount'){
            $tax = $product->tax;
            $price += $tax;
        }
        $shipping_cost = $product->shipping_cost*$request['quantity'];
        $data['shipping_cost'] = $shipping_cost;
        $data['quantity'] = $request['quantity'];
        $data['price'] = $price;
        $data['total_price'] = $price * $request['quantity']+$shipping_cost;
        $data['tax'] = $tax;

        //$data['shipping_type'] = $product->shipping_type;

        // if($product->shipping_type == 'free'){
        //
        // }
        // else{
        //     $data['shipping'] = $product->shipping_cost;
        // }

        if($request->session()->has('cart')){
            $cart = $request->session()->get('cart', collect([]));
            $cart->push($data);
        }
        else{
            $cart = collect([$data]);
            $request->session()->put('cart', $cart);
        }
        $result['cart'] = $request->session()->get('cart');
        return $this->sendResponse($result, 'Product added to cart successfully.');
    }

    //removes from Cart
    public function removeFromCart(Request $request)
    {
        if($request->session()->has('cart')){
            $cart = $request->session()->get('cart', collect([]));
            $value = null;
            // &-sign for php variable by reference
            $cart = $cart->map(function ($object, $key) use ($request,&$value) {
                if($object['id']== $request->id){
                    $value=$key;
                    return $object;
                }
                return $object;
            });
            $cart->forget($value);
            $request->session()->put('cart', $cart);
        }
        $result['cart'] = $request->session()->get('cart');
        return $this->sendResponse($result, 'Product removed from cart successfully.');
    }

    //updated the quantity for a cart item
    public function updateQuantity(Request $request)
    {
        $cart = $request->session()->get('cart', collect([]));
        $cart = $cart->map(function ($object, $key) use ($request) {
            if($object['id']== $request->id){
                $object['quantity'] = $request->quantity;
                $object['total_price'] = $object['quantity'] * $object ['unit_price'];
            }
            return $object;
        });
        $request->session()->put('cart', $cart);
        $result['cart'] = $request->session()->get('cart');
        return $this->sendResponse($result, 'Cart updated successfully.');
    }

    //store shipping address
    public function shipping_address(Request $request){
        $data['name'] = $request->name;
        $data['email'] = $request->email;
        $data['address'] = $request->address;
        $data['country'] = $request->country;
        $data['city'] = $request->city;
        $data['postal_code'] = $request->postal_code;
        $data['phone'] = $request->phone;
        $request->session()->put('shipping_info', $data);
        $result['shipping_info'] = $request->session()->get('shipping_info');
        return $this->sendResponse($result, 'Shipping address stored successfully.');
    }
}
