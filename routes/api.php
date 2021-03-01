<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

//Login And Resister with JSON Web Token(JWT)
Route::get('countries', 'API\UserApiController@country_list');
Route::post('register', 'API\UserApiController@register');
Route::post('login', 'API\UserApiController@login');
Route::post('logout', 'API\UserApiController@logout');
Route::get('email/resend', 'API\VerificationController@resend')->name('verification.resend');
Route::post('email/verify/{id}/{hash}', 'API\VerificationController@verify')->name('verification.verify');
// Route::post('password/email', 'API\ForgotPasswordController@sendResetLinkEmail');
// Route::post('password/reset', 'API\ResetPasswordController@reset');

Route::get('settings', 'API\GeneralApiController@settings');
Route::get('search', 'API\GeneralApiController@search');
Route::get('return-policy', 'API\GeneralApiController@return_policy');
Route::get('support-policy', 'API\GeneralApiController@support_policy');

//User
Route::group(['middleware' => ['jwt.auth']], function(){
    Route::get('user', 'API\UserApiController@user');
    Route::post('user', 'API\UserApiController@user_update');
    Route::post('shipping_info', 'API\UserApiController@shipping_info');
    Route::get('wishlist', 'API\UserApiController@wishlist');
    Route::post('wishlist', 'API\UserApiController@add_wishlist');
    Route::delete('wishlist', 'API\UserApiController@remove_wishlist');
    Route::get('cart', 'API\UserApiController@cart');
    Route::post('cart', 'API\UserApiController@add_cart');
    Route::delete('cart', 'API\UserApiController@remove_cart');

    //order list
    Route::get('/orders/all', 'API\UserApiController@all_orders');
    Route::get('/orders/paid', 'API\UserApiController@paid_orders');
    Route::get('/orders/unpaid', 'API\UserApiController@unpaid_orders');
    Route::get('/orders/shipped', 'API\UserApiController@shipped_orders');
    Route::get('/orders/pending', 'API\UserApiController@toBeShipped_orders');

    //Route for checkout
    Route::post('store_shipping_info', 'API\UserApiController@store_shipping');

    //driver orders
    Route::get('driver/orders', 'API\DriverApiController@driver_orders');
    Route::get('orders/history', 'API\DriverApiController@driver_delivered_orders');
    Route::post('order/delivery_status','API\DriverApiController@delivery_status');
});

Route::get('countries', 'API\GeneralApiController@countries');
Route::get('hubs', 'API\GeneralApiController@hubs');

Route::get('banner', 'API\GeneralApiController@banner');
Route::get('flash_sales', 'API\GeneralApiController@flash_sales');
Route::get('brands', 'API\GeneralApiController@brands');
Route::get('categories', 'API\GeneralApiController@categories');
Route::get('sub_categories/{category_id}', 'API\GeneralApiController@sub_categories');
Route::get('category/products/{category_id}', 'API\GeneralApiController@category_product');
Route::get('sub_category/products/{sub_category_id}', 'API\GeneralApiController@sub_category_product');


//Login And Resister with JSON Web Token(JWT) For Driver
Route::post('driver/register', 'API\DriverApiController@register');
Route::get('driver/settings', 'API\DriverApiController@driver_settings');

Route::get('notification/{user_type}', 'API\UserApiController@getnotify');
Route::get('related_products/{product_id}', 'API\GeneralApiController@related_products');
Route::get('product/best-selling', 'API\GeneralApiController@best_selling');

Route::get('ssl-pay', 'API\SslApiController@ssl');
Route::any('ssl-pay/success', 'API\SslApiController@success');
Route::any('ssl-pay/fail', 'API\SslApiController@fail');
