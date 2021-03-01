<?php

namespace App\Http\Controllers\API;

use App\Order;
use App\BusinessSetting;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
session_start();

class SslApiController extends Controller
{
    public function ssl(Request $request)
    {
        $order = Order::findOrFail($request->order_id);
        $shipping = json_decode($order->shipping_address);
        if(BusinessSetting::where('type', 'sslcommerz_sandbox')->first()->value == 1){
            $ssl_mode = "sandbox";
        }
        else{
            $ssl_mode = "securepay";
        }

        $ssl_store_id = env('SSLCZ_STORE_ID');
        $ssl_store_pass = env('SSLCZ_STORE_PASSWD');
        $ssl_url =  "https://" . $ssl_mode . ".sslcommerz.com/gwprocess/v3/api.php";;
        $ssl_currency = "BDT";

        $post_data = array();
        $post_data['store_id'] = $ssl_store_id;
        $post_data['store_passwd'] = $ssl_store_pass;
        $post_data['total_amount'] = $order->grand_total;
        $post_data['currency'] = $ssl_currency;
        $post_data['tran_id'] = substr(md5($request->order_id), 0, 10);
        $post_data['success_url'] = url('api/ssl-pay/success');
        $post_data['fail_url'] = url('/api/ssl-pay/fail');
        $post_data['cancel_url'] = url('/api/ssl-pay/faill');
        # $post_data['multi_card_name'] = "mastercard,visacard,amexcard";  # DISABLE TO DISPLAY ALL AVAILABLE

        # EMI INFO
        $post_data['emi_option'] = "0";
        $post_data['emi_max_inst_option'] = "9";
        $post_data['emi_selected_inst'] = "9";

        # CUSTOMER INFORMATION
        $post_data['cus_name'] = $shipping->name;
        $post_data['cus_email'] = $shipping->email;
        $post_data['cus_add1'] = $shipping->address;
        $post_data['cus_add2'] = "Dhaka";
        $post_data['cus_city'] = "Dhaka";
        $post_data['cus_state'] = "Dhaka";
        $post_data['cus_postcode'] = "1000";
        $post_data['cus_country'] = "Bangladesh";
        $post_data['cus_phone'] = $shipping->phone;
        $post_data['cus_fax'] = "01711111111";

        # SHIPMENT INFORMATION
        // $post_data['ship_name'] = "Wallet Recharge";
        // $post_data['ship_add1 '] = "Dhaka";
        // $post_data['ship_add2'] = "Dhaka";
        // $post_data['ship_city'] = "Dhaka";
        // $post_data['ship_state'] = "Dhaka";
        // $post_data['ship_postcode'] = "1000";
        // $post_data['ship_country'] = "Bangladesh";

        # OPTIONAL PARAMETERS
        // $post_data['value_a'] = $user->id;
        // $post_data['value_b '] = $user_type;
        // $post_data['value_c'] = $user_type;
        // $post_data['value_d'] = "ref004";

        # CART PARAMETERS
        $post_data['cart'] = json_encode(array(
            array("product" => "DHK TO BRS AC A1", "amount" => "200.00"),
            array("product" => "DHK TO BRS AC A2", "amount" => "200.00"),
            array("product" => "DHK TO BRS AC A3", "amount" => "200.00"),
            array("product" => "DHK TO BRS AC A4", "amount" => "200.00"),
        ));
        $post_data['product_amount'] = "100";
        $post_data['vat'] = "5";
        $post_data['discount_amount'] = "5";
        $post_data['convenience_fee'] = "3";

        # REQUEST SEND TO SSLCOMMERZ
        $direct_api_url = $ssl_url;

        $handle = curl_init();
        curl_setopt($handle, CURLOPT_URL, $direct_api_url);
        curl_setopt($handle, CURLOPT_TIMEOUT, 30);
        curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($handle, CURLOPT_POST, 1);
        curl_setopt($handle, CURLOPT_POSTFIELDS, $post_data);
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, false); # KEEP IT FALSE IF YOU RUN FROM LOCAL PC

        $content = curl_exec($handle);
        // dd($content);
        $code = curl_getinfo($handle, CURLINFO_HTTP_CODE);

        if ($code == 200 && !(curl_errno($handle))) {
            curl_close($handle);
            $sslcommerzResponse = $content;
        } else {
            curl_close($handle);
            echo "FAILED TO CONNECT WITH SSLCOMMERZ API";
            exit;
        }

        # PARSE THE JSON RESPONSE
        $sslcz = json_decode($sslcommerzResponse, true);

        if (isset($sslcz['GatewayPageURL']) && $sslcz['GatewayPageURL'] != "") {
            # THERE ARE MANY WAYS TO REDIRECT - Javascript, Meta Tag or Php Header Redirect or Other
            echo "<script>window.location.href = '". $sslcz['GatewayPageURL'] ."';</script>";
            #echo "<meta http-equiv='refresh' content='0;url=" . $sslcz['GatewayPageURL'] . "'>";
            # header("Location: ". $sslcz['GatewayPageURL']);
            exit;
        } else {
            echo "JSON Data parsing error!";
        }
    }

    public function success(){
        return 'Payment Successful';
    }

    public function fail(){
        return 'Payment Failed';
    }
}
