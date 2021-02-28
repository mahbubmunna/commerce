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
Route::post('register', 'API\UserApiController@register');
Route::post('login', 'API\UserApiController@login');
Route::post('social-login', 'API\UserApiController@socialLogin');
Route::post('logout', 'API\UserApiController@logout');
Route::get('email/resend', 'API\VerificationController@resend')->name('verification.resend');
Route::post('email/verify/{id}/{hash}', 'API\VerificationController@verify')->name('verification.verify');
Route::post('password/email', 'API\ForgotPasswordController@sendResetLinkEmail');
// Route::post('password/reset', 'API\ResetPasswordController@reset');

//User
Route::group(['middleware' => ['jwt.auth', 'mail.verified']], function(){
    Route::get('user', 'API\UserApiController@user');
    Route::post('user', 'API\UserApiController@user_update');
    Route::post('user/profile-pic', 'API\UserApiController@user_profile_pic');
    Route::post('shipping_info', 'API\UserApiController@shipping_info');
    Route::get('wishlist', 'API\UserApiController@wishlist');
    Route::post('wishlist', 'API\UserApiController@add_wishlist');
    Route::delete('wishlist', 'API\UserApiController@remove_wishlist');
    Route::get('cart', 'API\UserApiController@cart');
    Route::post('cart', 'API\UserApiController@add_cart');
    Route::delete('cart', 'API\UserApiController@remove_cart');

    //cart
    Route::get('carts', 'API\CartApiController@index');
    Route::post('add-to-cart', 'API\CartApiController@addToCart');
    Route::post('remove-from-cart', 'API\CartApiController@removeFromCart');
    Route::post('update-cart-quantity', 'API\CartApiController@updateQuantity');

    //store shipping address
    Route::post('shipping-address', 'API\CartApiController@shipping_address');

    //order list
    Route::get('/orders/all', 'API\UserApiController@all_orders');
    Route::get('/orders/paid', 'API\UserApiController@paid_orders');
    Route::get('/orders/unpaid', 'API\UserApiController@unpaid_orders');
    Route::get('/orders/shipped', 'API\UserApiController@shipped_orders');
    Route::get('/orders/pending', 'API\UserApiController@toBeShipped_orders');

    //Route for checkout
    Route::post('checkout', 'API\CheckoutApiController@checkout');
    Route::post('store_shipping_info', 'API\UserApiController@store_shipping');

    //Route update payment status
    Route::post('payment-status-update','API\CheckoutApiController@update_payment_status');

    //user setting profile
    Route::get('user/profile','API\UserApiController@user_profile');

    //conversation between seller and customer
    Route::get('conversations','API\ConversationApiController@index');
    Route::get('conversation/{id}','API\ConversationApiController@show');
    Route::post('start-conversation','API\ConversationApiController@store');
    Route::post('send-message','API\MessageApiController@store');

});

Route::get('countries', 'API\TestApiController@countries');
Route::get('banner', 'API\TestApiController@banner');
Route::get('flash_sales', 'API\TestApiController@flash_sales');
Route::get('brands', 'API\TestApiController@brands');
Route::get('categories', 'API\TestApiController@categories');
Route::get('sub_categories/{category_id}', 'API\TestApiController@sub_categories');
Route::get('category/products/{category_id}', 'API\TestApiController@category_product');
Route::get('sub_category/products/{sub_category_id}', 'API\TestApiController@sub_category_product');
Route::get('brand/products/{brand_id}', 'API\TestApiController@brand_product');
Route::get('review/{product_id}', 'API\TestApiController@product_review');
