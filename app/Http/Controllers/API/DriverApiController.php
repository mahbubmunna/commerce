<?php

namespace App\Http\Controllers\API;

use App\Currency;
use App\GeneralSetting;
use App\Role;
use App\Staff;
use Auth;
use JWTAuth;
use App\User;
use Response;
use Validator;
use JWTFactory;
use App\Product;
use App\Wishlist;
use App\Cart;
use App\BusinessSetting;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Order;
use App\OrderDetail;
use DB;

class DriverApiController extends Controller
{
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email|max:255|unique:users',
            'name' => 'required',
            'password'=> 'required',
        ]);
        if ($validator->fails()) {
            return response()->json($validator->errors());
        }
        $role = Role::where('name','Driver')->first();
        $register = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'user_type' => 'staff',
            'password' => bcrypt($request->password),
        ]);
        if($register){
            $staff = new Staff;
            $staff->user_id = $register->id;
            $staff->role_id = $role->id;
            $staff->save();
        }
        if(BusinessSetting::where('type', 'email_verification')->first()->value != 1){
            $register->email_verified_at = date('Y-m-d H:m:s');
            $register->save();
        }

        $token = JWTAuth::fromUser($register);
        $user = User::findOrFail($register->id);
        $user->token = $token;
        $user->provider_id = $register->id;

        $response = [
            'success' => true,
            'data'    => $user,
            'message' => 'Registration Completed.',
        ];
        return response()->json($response, 201);

//        return $this->sendResponse($user, 'Registration Completed.', 201);
    }

    public function driver_orders(Request $request){
        $orders = DB::table('orders')
            ->join('order_details','order_details.order_id','=','orders.id')
            ->where('order_details.delivery_status','!=','delivered')
            ->where('orders.delivery_man_id', auth()->user()->id)
            ->select('orders.id')
            ->distinct('orders.id')
            ->get();
        $data = [];
        foreach ($orders as $key => $order){
            $order_info = Order::where('id',$order->id)->first();
            $orders_details = OrderDetail::where('order_id',$order->id)->get();
            $data[$key]['order']['id'] = $order_info->id;
            $data[$key]['order']['shipping info']['name'] = json_decode($order_info->shipping_address)->name;
            $data[$key]['order']['shipping info']['email'] = json_decode($order_info->shipping_address)->email;
            $data[$key]['order']['shipping info']['phone'] = json_decode($order_info->shipping_address)->phone;
            $data[$key]['order']['shipping info']['address'] = json_decode($order_info->shipping_address)->address.','.json_decode($order_info->shipping_address)->city.','.json_decode($order_info->shipping_address)->country.','.json_decode($order_info->shipping_address)->postal_code;
            $data[$key]['order']['payment_type'] = $order_info->payment_type;
            $data[$key]['order']['date'] = date("m-d-Y", strtotime($order_info->created_at));
            $quantity=0;
            $delivery_status='';
            foreach ($orders_details as $key1=>$order_detail){
                    $product = Product::where('id',$order_detail->product_id)->first();
                    $data[$key]['order_details'][$key1]['id']= $product['id'];
                    $data[$key]['order_details'][$key1]['product name']= $product['name'];
                    $product_photos = json_decode($product['photos']);
                    if($product_photos){
                        foreach ($product_photos as $key2 => $product_photo)
                        {
                            $data[$key]['order_details'][$key1]['photos'][$key2]= $product_photo;
                        }
                    }else{
                        $data[$key]['order_details'][$key1]['photos'] = null;
                    }
                    $data[$key]['order_details'][$key1]['thumbnail img']= $product['thumbnail_img'];
                    $data[$key]['order_details'][$key1]['featured img']= $product['featured_img'];
                    $data[$key]['order_details'][$key1]['flash deal img']= $product['flash_deal_img'];
                    $data[$key]['order_details'][$key1]['price']= $order_detail->price;
                    $data[$key]['order_details'][$key1]['tax']= $order_detail->tax;
                    $data[$key]['order_details'][$key1]['shipping cost']= $order_detail->shipping_cost;
                    $data[$key]['order_details'][$key1]['quantity']= $order_detail->quantity;
                    $delivery_status = $order_detail->delivery_status;
                    $quantity+=$order_detail->quantity;
            }
            $data[$key]['order']['total quantity']=$quantity;
            $data[$key]['order']['delivery status']=$delivery_status;
        }
        return $this->sendResponse($data, 'Orders retrieve successfully.', 200);
    }
    public function driver_delivered_orders(){
        $orders = DB::table('orders')
            ->join('order_details','order_details.order_id','=','orders.id')
            ->where('order_details.delivery_status','=','delivered')
            ->where('orders.delivery_man_id', auth()->user()->id)
            ->select('orders.id')
            ->distinct('orders.id')
            ->get();
        $data = [];
        foreach ($orders as $key => $order){
            $order_info = Order::where('id',$order->id)->first();
            $data[$key]['order']['id'] = $order_info->id;
            $data[$key]['order']['shipping info']['name'] = json_decode($order_info->shipping_address)->name;
            $data[$key]['order']['shipping info']['email'] = json_decode($order_info->shipping_address)->email;
            $data[$key]['order']['shipping info']['phone'] = json_decode($order_info->shipping_address)->phone;
            $data[$key]['order']['shipping info']['address'] = json_decode($order_info->shipping_address)->address.','.json_decode($order_info->shipping_address)->city.','.json_decode($order_info->shipping_address)->country.','.json_decode($order_info->shipping_address)->postal_code;
            $data[$key]['order']['payment_type'] = $order_info->payment_type;
            $data[$key]['order']['date'] = date("m-d-Y", strtotime($order_info->created_at));
            $orders_details = OrderDetail::where('order_id',$order->id)->get();
            $quantity=0;
            $delivery_status='';
            foreach ($orders_details as $key1=>$order_detail){
                $product = Product::where('id',$order_detail->product_id)->first();
                $data[$key]['order_details'][$key1]['id']= $product['id'];
                $data[$key]['order_details'][$key1]['product name']= $product['name'];
                $product_photos = json_decode($product['photos']);
                if($product_photos){
                    foreach ($product_photos as $key2 => $product_photo)
                    {
                        $data[$key]['order_details'][$key1]['photos'][$key2]= $product_photo;
                    }
                }else{
                    $data[$key]['order_details'][$key1]['photos'] = null;
                }
                $data[$key]['order_details'][$key1]['thumbnail img']= $product['thumbnail_img'];
                $data[$key]['order_details'][$key1]['featured img']= $product['featured_img'];
                $data[$key]['order_details'][$key1]['flash deal img']= $product['flash_deal_img'];
                $data[$key]['order_details'][$key1]['price']= $order_detail->price;
                $data[$key]['order_details'][$key1]['tax']= $order_detail->tax;
                $data[$key]['order_details'][$key1]['shipping cost']= $order_detail->shipping_cost;
                $data[$key]['order_details'][$key1]['quantity']= $order_detail->quantity;
                $delivery_status = $order_detail->delivery_status;
                $quantity+=$order_detail->quantity;
            }
            $data[$key]['order']['total quantity']=$quantity;
            $data[$key]['order']['delivery status']=$delivery_status;
        }
        return $this->sendResponse($data, 'Orders retrieve successfully.', 200);
    }
    public function delivery_status(Request $request){
        if(auth()->user()){
            $order_details = OrderDetail::where('order_id',$request->order_id)->update(['delivery_status'=>$request->status]);
            return $this->sendResponse($order_details, 'Orders updated successfully.', 200);
        }else{
            return $this->sendError('Unauthorized User', 401);
        }
    }
    public function driver_settings(){
        $data = [];
        $site_name = GeneralSetting::select('site_name')->first();
        $stripe_payment = BusinessSetting::where('type','stripe_payment')->select('value')->first();
        $paypal_payment = BusinessSetting::where('type','paypal_payment')->select('value')->first();
        $system_default_currency = BusinessSetting::where('type','system_default_currency')->select('value')->first();
        $currency_symbol = Currency::where('id',$system_default_currency->value)->select('symbol')->first();
        $data['app_name'] = $site_name->site_name;
        $data['enable_stripe'] = $stripe_payment->value;
        $data['default_tax'] = 10;
        $data['default_currency'] = $currency_symbol->symbol;
        $data['enable_paypal'] = $paypal_payment->value;
        $response = [
            'success' => true,
            'data'    => $data,
            'message' => 'Settings retrieved successfully',
        ];
        return response()->json($response, 200);
    }

//    public function login(Request $request)
//    {
//        $validator = Validator::make($request->all(), [
//            'email' => 'required|string|email|max:255',
//            'password'=> 'required'
//        ]);
//        if ($validator->fails()) {
//            return response()->json($validator->errors());
//        }
//        $credentials = $request->only('email', 'password');
//        try {
//            if (! $token = JWTAuth::attempt($credentials)) {
//                return response()->json(['error' => 'invalid_credentials'], 401);
//            }
//        } catch (JWTException $e) {
//            return response()->json(['error' => 'could_not_create_token'], 500);
//        }
//        return $this->sendResponse($token, 'Login and user retrived successfully.');
//    }

    public function user(Request $request)
    {
        $user = auth()->user();
        return $this->sendResponse($user, 'User retrived successfully.');
    }

    //User Profile Update
    public function user_update(Request $request){
        if(auth()->user()){
            $user = User::findOrFail(auth()->user()->id);
            $user->name = $request->name;
            if($request->new_password != null && ($request->new_password == $request->confirm_password)){
                $user->password = Hash::make($request->new_password);
            }
            if($request->hasFile('photo')){
                $user->avatar_original = $request->photo->store('uploads/users');
            }
            $user->save();
            return $this->sendResponse($user, 'Profile Updated successfully.');
        }else{
            return $this->sendError('Unauthorized User', 401);
        }
    }

    //User Shipping Info Update
//    public function shipping_info(Request $request){
//        if(auth()->user()){
//            $user = User::findOrFail(auth()->user()->id);
//            $user->address = $request->address;
//            $user->country = $request->country;
//            $user->city = $request->city;
//            $user->postal_code = $request->postal_code;
//            $user->phone = $request->phone;
//            $user->save();
//            return $this->sendResponse($user, 'Shipping Info Updated successfully.');
//        }else{
//            return $this->sendError('Unauthorized User', 401);
//        }
//    }

//    public function logout()
//    {
//        auth()->logout();
//        return $this->sendResponse(null, 'Logged Out successfully.');
//    }

//    public function wishlist(){
//        if(auth()->user()){
//            $wishlists = Wishlist::where('user_id', auth()->user()->id)->get();
//            foreach($wishlists as $key => $wishlist){
//                $wishlists[$key]['product'] = Product::where('id', $wishlist->product_id)->where('published', 1)->first();
//            }
//            return $this->sendResponse($wishlists, 'Wishlist retrived successfully.');
//        }else{
//            return $this->sendError('Unauthorized User', 401);
//        }
//    }

//    public function add_wishlist(Request $request){
//        if(auth()->user()){
//            $validator = Validator::make($request->all(), [
//                'product_id' => 'required',
//            ]);
//            if ($validator->fails()) {
//                return response()->json($validator->errors());
//            }
//
//            $wishlist = Wishlist::where('user_id', auth()->user()->id)->where('product_id', $request->product_id)->first();
//            if($wishlist == null){
//                $wishlist = new Wishlist;
//                $wishlist->user_id = auth()->user()->id;
//                $wishlist->product_id = $request->product_id;
//                $wishlist->save();
//                return $this->sendResponse($wishlist, 'Product Added to wishlist successfully.', 201);
//            }else{
//                return $this->sendResponse($wishlist, 'Product Already exixt in wishlist.');
//            }
//        }else{
//            return $this->sendError('Unauthorized User', 401);
//        }
//    }

//    public function remove_wishlist(Request $request){
//        if(auth()->user()){
//            $validator = Validator::make($request->all(), [
//                'wishlist_id' => 'required',
//            ]);
//            if ($validator->fails()) {
//                return response()->json($validator->errors());
//            }
//
//            $wishlist = Wishlist::findOrFail($request->wishlist_id);
//            if($wishlist!=null){
//                if(Wishlist::destroy($request->wishlist_id)){
//                    return $this->sendResponse(null, 'Wishlist removed successfully.');
//                }
//            }
//
//        }else{
//            return $this->sendError('Unauthorized User', 401);
//        }
//    }

//    public function cart(){
//        if(auth()->user()){
//            $carts = Cart::where('user_id', auth()->user()->id)->get();
//            foreach($carts as $key => $cart){
//                $carts[$key]['product'] = Product::where('id', $cart->product_id)->where('published', 1)->first();
//            }
//            return $this->sendResponse($carts, 'Cart retrived successfully.');
//        }else{
//            return $this->sendError('Unauthorized User', 401);
//        }
//    }

//    public function add_cart(Request $request){
//        if(auth()->user()){
//            $validator = Validator::make($request->all(), [
//                'product_id' => 'required',
//            ]);
//            if ($validator->fails()) {
//                return response()->json($validator->errors());
//            }
//
//            $cart = Cart::where('user_id', auth()->user()->id)->where('product_id', $request->product_id)->first();
//            if($cart == null){
//                $cart = new Cart;
//                $cart->user_id = auth()->user()->id;
//                $cart->product_id = $request->product_id;
//                $cart->save();
//                return $this->sendResponse($cart, 'Product Added to cart successfully.', 201);
//            }else{
//                return $this->sendResponse($cart, 'Product Already exixt in cart.');
//            }
//        }else{
//            return $this->sendError('Unauthorized User', 401);
//        }
//    }

//    public function remove_cart(Request $request){
//        if(auth()->user()){
//            $validator = Validator::make($request->all(), [
//                'cart_id' => 'required',
//            ]);
//            if ($validator->fails()) {
//                return response()->json($validator->errors());
//            }
//
//            $cart = Cart::findOrFail($request->cart_id);
//            if($cart!=null){
//                if(Cart::destroy($request->cart_id)){
//                    return $this->sendResponse(null, 'Cart removed successfully.');
//                }
//            }
//
//        }else{
//            return $this->sendError('Unauthorized User', 401);
//        }
//    }

    //order lisr methods
//    public function all_orders(){
//        $orders = [];
//        if(auth()->user()){
//            $order = Order::where('user_id', auth()->user()->id)->get();
//            foreach($order as  $key =>  $ord){
//                $orders[$key] = OrderDetail::where('order_id', $ord->id)->first();
//                $orders[$key]['product'] = Product::find($orders[$key]->product_id);
//            }
//            if (!empty($orders)) {
//                return $this->sendResponse($orders, 'All Orders retrived successfully');
//            } else {
//                return $this->sendResponse($order, 'No Orders Found.');
//            }
//
//        }else{
//            return $this->sendError('Unauthorized User', 401);
//        }
//    }

    //paid orders
//    public function paid_orders(){
//        if(auth()->user()){
//            $order = Order::where('user_id', auth()->user()->id)->get();
//            foreach($order as  $key =>  $ord){
//                $orders[$key] = OrderDetail::where('order_id', $ord->id)->where('payment_status', 'paid')->first();
//                $orders[$key]['product'] = Product::find($orders[$key]->product_id);
//            }
//            if ($orders) {
//                return $this->sendResponse($orders, 'Paid Orders retrived successfully');
//            } else {
//                return $this->sendResponse($order, 'No Orders Found.');
//            }
//
//        }else{
//            return $this->sendError('Unauthorized User', 401);
//        }
//    }

    //unpaid orders
//    public function unpaid_orders(){
//        if(auth()->user()){
//            $order = Order::where('user_id', auth()->user()->id)->get();
//            foreach($order as  $key =>  $ord){
//                $orders[$key] = OrderDetail::where('order_id', $ord->id)->where('payment_status', 'unpaid')->first();
//                $orders[$key]['product'] = Product::find($orders[$key]->product_id);
//            }
//            if ($orders) {
//                return $this->sendResponse($orders, 'Unpaid Orders retrived successfully');
//            } else {
//                return $this->sendResponse($order, 'No Orders Found.');
//            }
//
//        }else{
//            return $this->sendError('Unauthorized User', 401);
//        }
//    }

    //shipped orders
//    public function shipped_orders(){
//        if(auth()->user()){
//            $order = Order::where('user_id', auth()->user()->id)->get();
//            foreach($order as  $key =>  $ord){
//                $orders[$key] = OrderDetail::where('order_id', $ord->id)->where('delivery_status', 'delivered')->first();
//                $orders[$key]['product'] = Product::find($orders[$key]->product_id);
//            }
//            if ($orders) {
//                return $this->sendResponse($orders, 'Shipped Orders retrived successfully');
//            } else {
//                return $this->sendResponse($order, 'No Orders Found.');
//            }
//
//        }else{
//            return $this->sendError('Unauthorized User', 401);
//        }
//    }

    //tobeshipped orders
//    public function toBeShipped_orders(){
//        if(auth()->user()){
//            $order = Order::where('user_id', auth()->user()->id)->get();
//            foreach($order as  $key =>  $ord){
//                $orders[$key] = OrderDetail::where('order_id', $ord->id)->where('delivery_status', 'pending')->first();
//                $orders[$key]['product'] = Product::find($orders[$key]->product_id);
//            }
//            if ($orders) {
//                return $this->sendResponse($orders, 'Pending Orders retrived successfully');
//            } else {
//                return $this->sendResponse($order, 'No Orders Found.');
//            }
//
//        }else{
//            return $this->sendError('Unauthorized User', 401);
//        }
//    }


}
