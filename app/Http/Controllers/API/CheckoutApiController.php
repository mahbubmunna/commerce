<?php

namespace App\Http\Controllers\API;

use App\CouponUsage;
use App\FlashDeal;
use App\Mail\InvoiceEmailManager;
use App\Order;
use App\OrderDetail;
use Illuminate\Http\Request;
use App\Product;
use App\SubSubCategory;
use App\Category;
use Illuminate\Support\Facades\Mail;
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
use PDF;
class CheckoutApiController extends Controller
{
    public function checkout(Request $request){
        $order = new Order;
        $order->user_id = auth()->user()->id;
        $order->shipping_address = json_encode($request->shipping_info);

        $order->payment_type = $request->payment_option;
        $order->delivery_viewed = '0';
        $order->payment_status_viewed = '0';
        $order->code = date('Ymd-His').rand(10,99);
        $order->date = strtotime('now');

        if($order->save()){
            $subtotal = 0;
            $tax = 0;
            $shipping = 0;
            foreach ($request->cart as $key => $cartItem){
                $product = Product::find($cartItem['id']);

//                if ($cartItem['shipping_type'] == 'home_delivery') {
//                    $subtotal += $cartItem['price']*$cartItem['quantity'];
//                    $tax += $cartItem['tax']*$cartItem['quantity'];
//                    $shipping += \App\Product::find($cartItem['id'])->shipping_cost*$cartItem['quantity'];
//                }
                $subtotal += $cartItem['price']*$cartItem['quantity'];
                $tax += 0;
                $shipping += \App\Product::find($cartItem['id'])->shipping_cost*$cartItem['quantity'];

                $product_variation = null;
                if(isset($cartItem['color'])){
                    $product_variation .= Color::where('code', $cartItem['color'])->first()->name;
                    foreach (json_decode($product->choice_options) as $choice){
                        $str = $choice->name; // example $str =  choice_0
                        if ($product_variation != null) {
                            $product_variation .= '-'.str_replace(' ', '', $cartItem[$str]);
                        }
                        else {
                            $product_variation .= str_replace(' ', '', $cartItem[$str]);
                        }
                    }
                }
                if($product_variation != null){
                    $variations = json_decode($product->variations);
                    $variations->$product_variation->qty -= $cartItem['quantity'];
                    $product->variations = json_encode($variations);
                    $product->save();
                }
                else {
                    $product->current_stock -= $cartItem['quantity'];
                    $product->save();
                }

                $order_detail = new OrderDetail;
                $order_detail->order_id  =$order->id;
                $order_detail->seller_id = $product->user_id;
                $order_detail->product_id = $product->id;
                $order_detail->variation = $product_variation;
                $order_detail->price = $cartItem['price'] * $cartItem['quantity'];
                $order_detail->tax = 0;
                $order_detail->shipping_type = 'home_delivery';


//                if ($cartItem['shipping_type'] == 'home_delivery') {
//                    $order_detail->shipping_cost = \App\Product::find($cartItem['id'])->shipping_cost*$cartItem['quantity'];
//                }
//                else{
//                    $order_detail->shipping_cost = 0;
//                    $order_detail->pickup_point_id = $cartItem['pickup_point'];
//                }
//                $order_detail->shipping_cost = \App\Product::find($cartItem['id'])->shipping_cost*$cartItem['quantity'];
                $order_detail->shipping_cost = \App\Product::find($cartItem['id'])->shipping_cost*$cartItem['quantity'];

                $order_detail->quantity = $cartItem['quantity'];
                $order_detail->save();

                $product->num_of_sale++;
                $product->save();
            }

            $order->grand_total = $subtotal + $tax + $shipping;


//            if(Session::has('coupon_discount')){
//                $order->grand_total -= Session::get('coupon_discount');
//                $order->coupon_discount = Session::get('coupon_discount');
//
//                $coupon_usage = new CouponUsage;
//                $coupon_usage->user_id = auth()->user()->id;
//                $coupon_usage->coupon_id = Session::get('coupon_id');
//                $coupon_usage->save();
//            }

            $order->save();


            //stores the pdf for invoice
            $pdf = PDF::setOptions([
                'isHtml5ParserEnabled' => true, 'isRemoteEnabled' => true,
                'logOutputFile' => storage_path('logs/log.htm'),
                'tempDir' => storage_path('logs/')
            ])->loadView('invoices.customer_invoice', compact('order'));
            $output = $pdf->output();
            file_put_contents(public_path('/invoices/').'Order#'.$order->code.'.pdf', $output);
            $array['view'] = 'emails.invoice';
            $array['subject'] = 'Order Placed - '.$order->code;
            $array['from'] = env('MAIL_USERNAME');
            $array['content'] = 'Hi. Your order has been placed';
            $array['file'] = public_path('/invoices/').'Order#'.$order->code.'.pdf';
            $array['file_name'] = 'Order#'.$order->code.'.pdf';

            //sends email to customer with the invoice pdf attached
            if(env('MAIL_USERNAME') != null){
                try {
                   Mail::to($request->shipping_info['email'])->queue(new InvoiceEmailManager($array));
                } catch (\Exception $e) {

                }

            }
            unlink($array['file']);
        }
        $result['order_id'] = $order->id;
        return $this->sendResponse($result, 'Order Posted successfully.');
    }

    public function update_payment_status(Request $request){
        $order_update = Order::where('id',$request->order_id)->update(['payment_status'=>'Paid']);
        $order_details = OrderDetail::where('order_id',$request->order_id)->update(['payment_status'=>'Paid']);
        if($order_update && $order_details){
            return $this->sendResponse($order_update, 'Order Payment Update Successfully.');
        }else{
            return $this->sendError('Something went wrong');
        }

    }
}
