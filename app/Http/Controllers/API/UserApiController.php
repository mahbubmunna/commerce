<?php

namespace App\Http\Controllers\API;

use App\Customer;
use Auth;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Hash;
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
use App\Notifications;
use DB;
use Socialite;
class UserApiController extends Controller
{
    public function register(Request $request)
    {
        // return $request->email;
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email|max:255|unique:users',
            'name' => 'required',
            'password'=> 'required',
            'user_type' => 'required',
            'device_id'=>'required',
            'device_type' => 'required|in:android,ios',
            'device_token' => 'required'
        ]);
        if ($validator->fails()) {
            return response()->json($validator->errors());
        }

        $register = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'user_type' => $request->user_type,
            'password' => bcrypt($request->password),
            'device_id'=> $request->device_id,
            'device_type' => $request->device_type,
            'device_token' => $request->device_token
        ]);

        if(BusinessSetting::where('type', 'email_verification')->first()->value != 1){
            $register->email_verified_at = date('Y-m-d H:m:s');
            $register->save();
        }

        $token = JWTAuth::fromUser($register);
        $user = User::findOrFail($register->id);
        $user->token = $token;
        return $this->sendResponse($user, 'Registration Completed.', 201);
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email|max:255',
            'password'=> 'required',
            'device_id'=>'required',
            'device_type' => 'required|in:android,ios',
            'device_token' => 'required'
        ]);
        if ($validator->fails()) {
            return response()->json($validator->errors());
        }
        $credentials = $request->only('email', 'password');
        try {
            if (! $token = JWTAuth::attempt($credentials)) {
                return response()->json(['error' => 'invalid_credentials'], 401);
            }
        } catch (JWTException $e) {
            return response()->json(['error' => 'could_not_create_token'], 500);
        }

        $user = auth()->user();
        $user ->device_id= $request->device_id;
        $user ->device_type = $request->device_type;
        $user ->device_token = $request->device_token;
        $user->save();
        return $this->sendResponse($token, 'Login and user retrived successfully.');
    }
    public function socialLogin(Request $request){
        try {
            $validator = Validator::make($request->all(), [
                'provider' => 'required|in:google,facebook,twitter',
                'accessToken'=> 'required',
                'device_id'=>'required',
                'device_type' => 'required|in:android,ios',
                'device_token' => 'required'
            ]);
            if ($validator->fails()) {
                return response()->json($validator->errors());
            }
            if($request->provider == 'twitter'){
                $user = Socialite::driver('twitter');
            }
            else{
                $user = Socialite::driver($request->provider)->stateless()->userFromToken($request->accessToken);
            }
            // check if they're an existing user
            $existingUser = User::where('provider_id', $user->id)->first();
            if($existingUser){
                $request['email'] = $existingUser->email;
                $request['password'] = $user->id;
                $credentials = $request->only('email', 'password');
                try {
                    if (! $token = JWTAuth::attempt($credentials)) {
                        return response()->json(['error' => 'invalid_credentials'], 401);
                    }else{
                        $existingUser->device_id= $request->device_id;
                        $existingUser->device_type = $request->device_type;
                        $existingUser->device_token = $request->device_token;
                        $existingUser->save();
                    }
                } catch (JWTException $e) {
                    return response()->json(['error' => 'could_not_create_token'], 500);
                }
                return $this->sendResponse($token, 'Login and user retrived successfully.');
            }else{
                $newUser                  = new User;
                $newUser->name            = $user->name;
                $newUser->email           = $user->email;
                $newUser->email_verified_at = date('Y-m-d H:m:s');
                $newUser->provider_id     = $user->id;
                $newUser->password = bcrypt($user->id);
                $extension = pathinfo($user->avatar_original, PATHINFO_EXTENSION);
                if($extension != ""){
                    $filename = 'uploads/users/'.str_random(5).'-'.$user->id.'.'.$extension;
                }else{
                    $filename = 'uploads/users/'.str_random(5).'-'.$user->id.'.jpg';
                }

                $fullpath = public_path($filename);
                $file = file_get_contents($user->avatar_original);
                file_put_contents($fullpath, $file);

                $newUser->avatar_original = $filename;
                $newUser->device_id= $request->device_id;
                $newUser->device_type = $request->device_type;
                $newUser->device_token = $request->device_token;
                $newUser->save();

                $customer = new Customer;
                $customer->user_id = $newUser->id;
                $customer->save();
                $request['email'] = $user->email;
                $request['password'] = $user->id;
                $credentials = $request->only('email', 'password');
                if (! $token = JWTAuth::attempt($credentials)) {
                    return response()->json(['error' => 'invalid_credentials'], 401);
                }else{
                    return $this->sendResponse($token, 'Login and user retrived successfully.');
                }
            }
        } catch (\Exception $e) {
            return $this->sendError('Something went wrong with social login ', 500);
        }

    }

    public function user(Request $request)
    {
        $user = auth()->user();
        return $this->sendResponse($user, 'User retrived successfully.');
    }
    //user Profile
    public function user_profile(){
        if(auth()->user()){
            $user = User::findOrFail(auth()->user()->id);
            $data['name'] = $user->name;
            $data['email'] = $user->email;
            $data['profile_pic'] = $user->avatar_original;
            $data['gender'] = "male";
            $data['dob'] = date("Y-m-d", strtotime($user->created_at));
            return $this->sendResponse($data, 'Profile retrived successfully.');
        }
    }

    //Get Country List
    public function country_list(){
        $country = Country::get();
        return $this->sendResponse($country, 'Country Retrived successfully.');
    }

    //User Profile Update
    public function user_update(Request $request){
        if(auth()->user()){
            $user = User::findOrFail(auth()->user()->id);
            if ($request->has('name')) {
                $user->name = $request->name;
            }

            if ($request->has('address')) {
                $user->address = $request->address;
            }

            if ($request->has('country')) {
                $user->country = $request->country;
            }

            if ($request->has('city')) {
                $user->city = $request->city;
            }

            if ($request->has('postal_code')) {
                $user->postal_code = $request->postal_code;
            }

            if ($request->has('phone')) {
                $user->phone = $request->phone;
            }

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

    //User Profile Update
    public function user_profile_pic(Request $request){
        if(auth()->user()){
            $user = User::findOrFail(auth()->user()->id);
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
    public function shipping_info(Request $request){
        if(auth()->user()){
            $user = User::findOrFail(auth()->user()->id);
            $user->address = $request->address;
            $user->country = $request->country;
            $user->city = $request->city;
            $user->postal_code = $request->postal_code;
            $user->phone = $request->phone;
            $user->save();
            return $this->sendResponse($user, 'Shipping Info Updated successfully.');
        }else{
            return $this->sendError('Unauthorized User', 401);
        }
    }

    public function logout()
    {
        auth()->logout();
        return $this->sendResponse(null, 'Logged Out successfully.');
    }

    public function wishlist(){
        if(auth()->user()){
            $wishlists = Wishlist::where('user_id', auth()->user()->id)->get();
            foreach($wishlists as $key => $wishlist){
                $wishlists[$key]['product'] = Product::where('id', $wishlist->product_id)->where('published', 1)->first();
            }
            return $this->sendResponse($wishlists, 'Wishlist retrived successfully.');
        }else{
            return $this->sendError('Unauthorized User', 401);
        }
    }

    public function add_wishlist(Request $request){
        if(auth()->user()){
            $validator = Validator::make($request->all(), [
                'product_id' => 'required',
            ]);
            if ($validator->fails()) {
                return response()->json($validator->errors());
            }

            $wishlist = Wishlist::where('user_id', auth()->user()->id)->where('product_id', $request->product_id)->first();
            if($wishlist == null){
                $wishlist = new Wishlist;
                $wishlist->user_id = auth()->user()->id;
                $wishlist->product_id = $request->product_id;
                $wishlist->save();
                return $this->sendResponse($wishlist, 'Product Added to wishlist successfully.', 201);
            }else{
                return $this->sendResponse($wishlist, 'Product Already exixt in wishlist.');
            }
        }else{
            return $this->sendError('Unauthorized User', 401);
        }
    }

    public function remove_wishlist(Request $request){
        if(auth()->user()){
            $validator = Validator::make($request->all(), [
                'wishlist_id' => 'required',
            ]);
            if ($validator->fails()) {
                return response()->json($validator->errors());
            }

            $wishlist = Wishlist::findOrFail($request->wishlist_id);
            if($wishlist!=null){
                if(Wishlist::destroy($request->wishlist_id)){
                    return $this->sendResponse(null, 'Wishlist removed successfully.');
                }
            }

        }else{
            return $this->sendError('Unauthorized User', 401);
        }
    }

    public function cart(){
        if(auth()->user()){
            $carts = Cart::where('user_id', auth()->user()->id)->get();
            foreach($carts as $key => $cart){
                $carts[$key]['product'] = Product::where('id', $cart->product_id)->where('published', 1)->first();
            }
            return $this->sendResponse($carts, 'Cart retrived successfully.');
        }else{
            return $this->sendError('Unauthorized User', 401);
        }
    }

    public function add_cart(Request $request){
        if(auth()->user()){
            $validator = Validator::make($request->all(), [
                'product_id' => 'required',
            ]);
            if ($validator->fails()) {
                return response()->json($validator->errors());
            }

            $cart = Cart::where('user_id', auth()->user()->id)->where('product_id', $request->product_id)->first();
            if($cart == null){
                $cart = new Cart;
                $cart->user_id = auth()->user()->id;
                $cart->product_id = $request->product_id;
                $cart->save();
                return $this->sendResponse($cart, 'Product Added to cart successfully.', 201);
            }else{
                return $this->sendResponse($cart, 'Product Already exixt in cart.');
            }
        }else{
            return $this->sendError('Unauthorized User', 401);
        }
    }

    public function remove_cart(Request $request){
        if(auth()->user()){
            $validator = Validator::make($request->all(), [
                'cart_id' => 'required',
            ]);
            if ($validator->fails()) {
                return response()->json($validator->errors());
            }

            $cart = Cart::findOrFail($request->cart_id);
            if($cart!=null){
                if(Cart::destroy($request->cart_id)){
                    return $this->sendResponse(null, 'Cart removed successfully.');
                }
            }

        }else{
            return $this->sendError('Unauthorized User', 401);
        }
    }

    //order lisr methods
    public function all_orders(Request $request){
        $data = [];
        if(auth()->user()){
            $orders = Order::where('user_id', auth()->user()->id)->get();
            foreach($orders as  $key =>  $order){
                $data[$key]['id'] = $order->id;
                $data[$key]['payment_type'] = $order->payment_type;
                $data[$key]['payment_status'] = $order->payment_status;
                $data[$key]['created_at'] = date("m-d-Y", strtotime($order->created_at));;
                $order_details = OrderDetail::where('order_id',$order->id)->get();
                foreach ($order_details as $key1 => $order_detail){
                    $data[$key]['order_details'][$key1]['id'] = $order_detail->product->id;
                    $data[$key]['order_details'][$key1]['name'] = $order_detail->product->name;
                    $data[$key]['order_details'][$key1]['category_name'] = $order_detail->product->category->name;
                    $data[$key]['order_details'][$key1]['sub_category_name'] = $order_detail->product->subcategory->name;
                    $data[$key]['order_details'][$key1]['sub_sub_category_name'] = $order_detail->product->subsubcategory->name;
                    $data[$key]['order_details'][$key1]['brand'] = $order_detail->product->brand->name;
                    $data[$key]['order_details'][$key1]['image'] = json_decode($order_detail->product->photos);
                    $data[$key]['order_details'][$key1]['thumbnail_img'] = $order_detail->product->thumbnail_img;
                    $data[$key]['order_details'][$key1]['featured_img'] = $order_detail->product->featured_img;
                    $data[$key]['order_details'][$key1]['colors'] = $order_detail->product->colors;
                    $data[$key]['order_details'][$key1]['price'] = $order_detail->product->unit_price;
                    $data[$key]['order_details'][$key1]['quantity'] = $order_detail->quantity;
                }
            }
            if (!empty($data)) {
                $paginate = 10;
                $page = $request->get('page', 1);
                $offSet = ($page * $paginate) - $paginate;
                $itemsForCurrentPage = array_slice($data, $offSet, $paginate, true);
                $result = new LengthAwarePaginator($itemsForCurrentPage, count($data), $paginate, $page, ['path'  => url()->current()]);
                $result = $result->toArray();
               return $this->sendResponse($result, 'All Orders retrived successfully');
            } else {
               return $this->sendResponse($data, 'No Orders Found.');
            }

        }else{
            return $this->sendError('Unauthorized User', 401);
        }
    }

    //paid orders
    public function paid_orders(Request $request){
        $data = [];
        if(auth()->user()){
            $orders = Order::where('user_id', auth()->user()->id)->where('payment_status', 'paid')->get();
            foreach($orders as  $key =>  $order){
                $data[$key]['id'] = $order->id;
                $data[$key]['payment_type'] = $order->payment_type;
                $data[$key]['payment_status'] = $order->payment_status;
                $data[$key]['created_at'] = date("m-d-Y", strtotime($order->created_at));;
                $order_details = OrderDetail::where('order_id',$order->id)->get();
                foreach ($order_details as $key1 => $order_detail){
                    $data[$key]['order_details'][$key1]['id'] = $order_detail->product->id;
                    $data[$key]['order_details'][$key1]['name'] = $order_detail->product->name;
                    $data[$key]['order_details'][$key1]['category_name'] = $order_detail->product->category->name;
                    $data[$key]['order_details'][$key1]['sub_category_name'] = $order_detail->product->subcategory->name;
                    $data[$key]['order_details'][$key1]['sub_sub_category_name'] = $order_detail->product->subsubcategory->name;
                    $data[$key]['order_details'][$key1]['brand'] = $order_detail->product->brand->name;
                    $data[$key]['order_details'][$key1]['image'] = json_decode($order_detail->product->photos);
                    $data[$key]['order_details'][$key1]['thumbnail_img'] = $order_detail->product->thumbnail_img;
                    $data[$key]['order_details'][$key1]['featured_img'] = $order_detail->product->featured_img;
                    $data[$key]['order_details'][$key1]['colors'] = $order_detail->product->colors;
                    $data[$key]['order_details'][$key1]['price'] = $order_detail->product->unit_price;
                    $data[$key]['order_details'][$key1]['quantity'] = $order_detail->quantity;
                }
            }
            if (!empty($data)) {
                $paginate = 10;
                $page = $request->get('page', 1);
                $offSet = ($page * $paginate) - $paginate;
                $itemsForCurrentPage = array_slice($data, $offSet, $paginate, true);
                $result = new LengthAwarePaginator($itemsForCurrentPage, count($data), $paginate, $page, ['path'  => url()->current()]);
                $result = $result->toArray();
                return $this->sendResponse($result, 'Paid Orders retrived successfully');
            } else {
                return $this->sendResponse($data, 'No Orders Found.');
            }

        }else{
            return $this->sendError('Unauthorized User', 401);
        }
    }

    //unpaid orders
    public function unpaid_orders(Request $request){
        $data = [];
        if(auth()->user()){
            $orders = Order::where('user_id', auth()->user()->id)->where('payment_status', 'unpaid')->get();
            foreach($orders as  $key =>  $order){
                $data[$key]['id'] = $order->id;
                $data[$key]['payment_type'] = $order->payment_type;
                $data[$key]['payment_status'] = $order->payment_status;
                $data[$key]['created_at'] = date("m-d-Y", strtotime($order->created_at));;
                $order_details = OrderDetail::where('order_id',$order->id)->get();
                foreach ($order_details as $key1 => $order_detail){
                    $data[$key]['order_details'][$key1]['id'] = $order_detail->product->id;
                    $data[$key]['order_details'][$key1]['name'] = $order_detail->product->name;
                    $data[$key]['order_details'][$key1]['category_name'] = $order_detail->product->category->name;
                    $data[$key]['order_details'][$key1]['sub_category_name'] = $order_detail->product->subcategory->name;
                    $data[$key]['order_details'][$key1]['sub_sub_category_name'] = $order_detail->product->subsubcategory->name;
                    $data[$key]['order_details'][$key1]['brand'] = $order_detail->product->brand->name;
                    $data[$key]['order_details'][$key1]['image'] = json_decode($order_detail->product->photos);
                    $data[$key]['order_details'][$key1]['thumbnail_img'] = $order_detail->product->thumbnail_img;
                    $data[$key]['order_details'][$key1]['featured_img'] = $order_detail->product->featured_img;
                    $data[$key]['order_details'][$key1]['colors'] = $order_detail->product->colors;
                    $data[$key]['order_details'][$key1]['price'] = $order_detail->product->unit_price;
                    $data[$key]['order_details'][$key1]['quantity'] = $order_detail->quantity;
                }
            }
            if (!empty($data)) {
                $paginate = 10;
                $page = $request->get('page', 1);
                $offSet = ($page * $paginate) - $paginate;
                $itemsForCurrentPage = array_slice($data, $offSet, $paginate, true);
                $result = new LengthAwarePaginator($itemsForCurrentPage, count($data), $paginate, $page, ['path'  => url()->current()]);
                $result = $result->toArray();
                return $this->sendResponse($result, 'Paid Orders retrived successfully');
            } else {
                return $this->sendResponse($data, 'No Orders Found.');
            }

        }else{
            return $this->sendError('Unauthorized User', 401);
        }
    }

    //shipped orders
    public function shipped_orders(Request $request){
        $data = [];
        if(auth()->user()){
            $orders = DB::table('orders')
                ->join('order_details','order_details.order_id','=','orders.id')
                ->where('order_details.delivery_status','=','delivered')
                ->where('orders.user_id', auth()->user()->id)
                ->select('orders.id','orders.payment_type','orders.payment_status','orders.created_at')
                ->distinct('orders.id')
                ->get();
            foreach($orders as  $key =>  $order){
                $data[$key]['id'] = $order->id;
                $data[$key]['payment_type'] = $order->payment_type;
                $data[$key]['payment_status'] = $order->payment_status;
                $data[$key]['created_at'] = date("m-d-Y", strtotime($order->created_at));;
                $order_details = OrderDetail::where('order_id',$order->id)->get();
                foreach ($order_details as $key1 => $order_detail){
                    $data[$key]['order_details'][$key1]['id'] = $order_detail->product->id;
                    $data[$key]['order_details'][$key1]['name'] = $order_detail->product->name;
                    $data[$key]['order_details'][$key1]['category_name'] = $order_detail->product->category->name;
                    $data[$key]['order_details'][$key1]['sub_category_name'] = $order_detail->product->subcategory->name;
                    $data[$key]['order_details'][$key1]['sub_sub_category_name'] = $order_detail->product->subsubcategory->name;
                    $data[$key]['order_details'][$key1]['brand'] = $order_detail->product->brand->name;
                    $data[$key]['order_details'][$key1]['image'] = json_decode($order_detail->product->photos);
                    $data[$key]['order_details'][$key1]['thumbnail_img'] = $order_detail->product->thumbnail_img;
                    $data[$key]['order_details'][$key1]['featured_img'] = $order_detail->product->featured_img;
                    $data[$key]['order_details'][$key1]['colors'] = $order_detail->product->colors;
                    $data[$key]['order_details'][$key1]['price'] = $order_detail->product->unit_price;
                    $data[$key]['order_details'][$key1]['quantity'] = $order_detail->quantity;
                }
            }
            if (!empty($data)) {
                $paginate = 10;
                $page = $request->get('page', 1);
                $offSet = ($page * $paginate) - $paginate;
                $itemsForCurrentPage = array_slice($data, $offSet, $paginate, true);
                $result = new LengthAwarePaginator($itemsForCurrentPage, count($data), $paginate, $page, ['path'  => url()->current()]);
                $result = $result->toArray();
                return $this->sendResponse($result, 'Shipped Orders retrived successfully');
            } else {
                return $this->sendResponse($data, 'No Orders Found.');
            }

        }else{
            return $this->sendError('Unauthorized User', 401);
        }
    }

    //tobeshipped orders
    public function toBeShipped_orders(Request $request){
        $data = [];
        if(auth()->user()){
            $orders = DB::table('orders')
                ->join('order_details','order_details.order_id','=','orders.id')
                ->where('order_details.delivery_status','=','pending')
                ->where('orders.user_id', auth()->user()->id)
                ->select('orders.id','orders.payment_type','orders.payment_status','orders.created_at')
                ->distinct('orders.id')
                ->get();
            foreach($orders as  $key =>  $order){
                $data[$key]['id'] = $order->id;
                $data[$key]['payment_type'] = $order->payment_type;
                $data[$key]['payment_status'] = $order->payment_status;
                $data[$key]['created_at'] = date("m-d-Y", strtotime($order->created_at));;
                $order_details = OrderDetail::where('order_id',$order->id)->get();
                foreach ($order_details as $key1 => $order_detail){
                    $data[$key]['order_details'][$key1]['id'] = $order_detail->product->id;
                    $data[$key]['order_details'][$key1]['name'] = $order_detail->product->name;
                    $data[$key]['order_details'][$key1]['category_name'] = $order_detail->product->category->name;
                    $data[$key]['order_details'][$key1]['sub_category_name'] = $order_detail->product->subcategory->name;
                    $data[$key]['order_details'][$key1]['sub_sub_category_name'] = $order_detail->product->subsubcategory->name;
                    $data[$key]['order_details'][$key1]['brand'] = $order_detail->product->brand->name;
                    $data[$key]['order_details'][$key1]['image'] = json_decode($order_detail->product->photos);
                    $data[$key]['order_details'][$key1]['thumbnail_img'] = $order_detail->product->thumbnail_img;
                    $data[$key]['order_details'][$key1]['featured_img'] = $order_detail->product->featured_img;
                    $data[$key]['order_details'][$key1]['colors'] = $order_detail->product->colors;
                    $data[$key]['order_details'][$key1]['price'] = $order_detail->product->unit_price;
                    $data[$key]['order_details'][$key1]['quantity'] = $order_detail->quantity;
                }
            }
            if (!empty($data)) {
                $paginate = 10;
                $page = $request->get('page', 1);
                $offSet = ($page * $paginate) - $paginate;
                $itemsForCurrentPage = array_slice($data, $offSet, $paginate, true);
                $result = new LengthAwarePaginator($itemsForCurrentPage, count($data), $paginate, $page, ['path'  => url()->current()]);
                $result = $result->toArray();
                return $this->sendResponse($result, 'Shipped Orders retrived successfully');
            } else {
                return $this->sendResponse($data, 'No Orders Found.');
            }

        }else{
            return $this->sendError('Unauthorized User', 401);
        }
    }

    //Checkout Process
    public function store_shipping(Request $request){
        $data['name'] = $request->name;
        $data['email'] = $request->email;
        $data['address'] = $request->address;
        $data['country'] = $request->country;
        $data['city'] = $request->city;
        $data['postal_code'] = $request->postal_code;
        $data['phone'] = $request->phone;
        $data['checkout_type'] = $request->checkout_type;

        $shipping_info = $data;
        $request->session()->put('shipping_info', $shipping_info);

        $subtotal = 0;
        $tax = 0;
        $shipping = 0;
        foreach (Session::get('cart') as $key => $cartItem){
            $subtotal += $cartItem['price']*$cartItem['quantity'];
            $tax += $cartItem['tax']*$cartItem['quantity'];
            $shipping += $cartItem['shipping']*$cartItem['quantity'];
        }

        $total = $subtotal + $tax + $shipping;

        if(Session::has('coupon_discount')){
                $total -= Session::get('coupon_discount');
        }

        return view('frontend.delivery_info');
    }


    public function store_delivery_info(Request $request)
    {
        if(Session::has('cart') && count(Session::get('cart')) > 0){
            $cart = $request->session()->get('cart', collect([]));
            $cart = $cart->map(function ($object, $key) use ($request) {
                if(\App\Product::find($object['id'])->added_by == 'admin'){
                    if($request['shipping_type_admin'] == 'home_delivery'){
                        $object['shipping_type'] = 'home_delivery';
                        $object['shipping'] = \App\Product::find($object['id'])->shipping_cost;
                    }
                    else{
                        $object['shipping_type'] = 'pickup_point';
                        $object['pickup_point'] = $request->pickup_point_id_admin;
                        $object['shipping'] = 0;
                    }
                }
                else{
                    if($request['shipping_type_'.\App\Product::find($object['id'])->user_id] == 'home_delivery'){
                        $object['shipping_type'] = 'home_delivery';
                        $object['shipping'] = \App\Product::find($object['id'])->shipping_cost;
                    }
                    else{
                        $object['shipping_type'] = 'pickup_point';
                        $object['pickup_point'] = $request['pickup_point_id_'.\App\Product::find($object['id'])->user_id];
                        $object['shipping'] = 0;
                    }
                }
                return $object;
            });

            $request->session()->put('cart', $cart);

            $subtotal = 0;
            $tax = 0;
            $shipping = 0;
            foreach (Session::get('cart') as $key => $cartItem){
                $subtotal += $cartItem['price']*$cartItem['quantity'];
                $tax += $cartItem['tax']*$cartItem['quantity'];
                $shipping += $cartItem['shipping']*$cartItem['quantity'];
            }

            $total = $subtotal + $tax + $shipping;

            if(Session::has('coupon_discount')){
                    $total -= Session::get('coupon_discount');
            }

            //dd($total);

            return view('frontend.payment_select', compact('total'));
        }
        else {
            flash('Your Cart was empty')->warning();
            return redirect()->route('home');
        }
    }


    public function checkout(Request $request)
    {
        $orderController = new OrderController;
        $orderController->store($request);

        $request->session()->put('payment_type', 'cart_payment');

        if($request->session()->get('order_id') != null){
            if($request->payment_option == 'paypal'){
                $paypal = new PaypalController;
                return $paypal->getCheckout();
            }
            elseif ($request->payment_option == 'stripe') {
                $stripe = new StripePaymentController;
                return $stripe->stripe();
            }
            elseif ($request->payment_option == 'sslcommerz') {
                $sslcommerz = new PublicSslCommerzPaymentController;
                return $sslcommerz->index($request);
            }
            elseif ($request->payment_option == 'instamojo') {
                $instamojo = new InstamojoController;
                return $instamojo->pay($request);
            }
            elseif ($request->payment_option == 'razorpay') {
                $razorpay = new RazorpayController;
                return $razorpay->payWithRazorpay($request);
            }
            elseif ($request->payment_option == 'paystack') {
                $paystack = new PaystackController;
                return $paystack->redirectToGateway($request);
            }
            elseif ($request->payment_option == 'voguepay') {
                $voguePay = new VoguePayController;
                return $voguePay->customer_showForm();
            }
            elseif ($request->payment_option == 'cash_on_delivery') {
                $order = Order::findOrFail($request->session()->get('order_id'));
                if (BusinessSetting::where('type', 'category_wise_commission')->first()->value != 1) {
                    $commission_percentage = BusinessSetting::where('type', 'vendor_commission')->first()->value;
                    foreach ($order->orderDetails as $key => $orderDetail) {
                        $orderDetail->payment_status = 'unpaid';
                        $orderDetail->save();
                        if($orderDetail->product->user->user_type == 'seller'){
                            $seller = $orderDetail->product->user->seller;
                            $seller->admin_to_pay = $seller->admin_to_pay + ($orderDetail->price*(100-$commission_percentage))/100;
                            $seller->save();
                        }
                    }
                }
                else{
                    foreach ($order->orderDetails as $key => $orderDetail) {
                        $orderDetail->payment_status = 'unpaid';
                        $orderDetail->save();
                        if($orderDetail->product->user->user_type == 'seller'){
                            $commission_percentage = $orderDetail->product->category->commision_rate;
                            $seller = $orderDetail->product->user->seller;
                            $seller->admin_to_pay = $seller->admin_to_pay + ($orderDetail->price*(100-$commission_percentage))/100;
                            $seller->save();
                        }
                    }
                }

                $request->session()->put('cart', collect([]));
                $request->session()->forget('order_id');
                $request->session()->forget('delivery_info');
                $request->session()->forget('coupon_id');
                $request->session()->forget('coupon_discount');

                flash("Your order has been placed successfully")->success();
            	return redirect()->route('home');
            }
            elseif ($request->payment_option == 'wallet') {
                $user = Auth::user();
                $user->balance -= Order::findOrFail($request->session()->get('order_id'))->grand_total;
                $user->save();
                return $this->checkout_done($request->session()->get('order_id'), null);
            }
        }
    }

    //redirects to this method after a successfull checkout
    public function checkout_done($order_id, $payment)
    {
        $order = Order::findOrFail($order_id);
        $order->payment_status = 'paid';
        $order->payment_details = $payment;
        $order->save();

        if (BusinessSetting::where('type', 'category_wise_commission')->first()->value != 1) {
            $commission_percentage = BusinessSetting::where('type', 'vendor_commission')->first()->value;
            foreach ($order->orderDetails as $key => $orderDetail) {
                $orderDetail->payment_status = 'paid';
                $orderDetail->save();
                if($orderDetail->product->user->user_type == 'seller'){
                    $seller = $orderDetail->product->user->seller;
                    $seller->admin_to_pay = $seller->admin_to_pay + ($orderDetail->price*(100-$commission_percentage))/100;
                    $seller->save();
                }
            }
        }
        else{
            foreach ($order->orderDetails as $key => $orderDetail) {
                $orderDetail->payment_status = 'paid';
                $orderDetail->save();
                if($orderDetail->product->user->user_type == 'seller'){
                    $commission_percentage = $orderDetail->product->category->commision_rate;
                    $seller = $orderDetail->product->user->seller;
                    $seller->admin_to_pay = $seller->admin_to_pay + ($orderDetail->price*(100-$commission_percentage))/100;
                    $seller->save();
                }
            }
        }

        Session::put('cart', collect([]));
        Session::forget('order_id');
        Session::forget('payment_type');
        Session::forget('delivery_info');
        Session::forget('coupon_id');
        Session::forget('coupon_discount');

        flash(__('Payment completed'))->success();
        return redirect()->route('home');
    }

    public function getnotify($type)
    {
        if($type=='user'){
            $search_type='provider';
        }
        else{
            $search_type='user';
        }

        try {

            $notification = Notifications::where('notify_type', '!=', $search_type)->where('status', 'active')->orderBy('created_at' , 'desc')->paginate(30);
            if ($notification) {
                return $this->sendResponse($notification, 'Notification retrived successfully');
             } else {
                return $this->sendResponse($notification, 'No Notification Found.');
             }
        }
        catch (Exception $e) {
            return $this->sendError('Something Went Wrong', 500);
        }
    }
}
