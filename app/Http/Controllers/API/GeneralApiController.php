<?php

namespace App\Http\Controllers\API;

use App\Brand;
use App\Color;
use App\Policy;
use App\Slider;
use App\Country;
use App\Product;
use App\Category;
use App\Currency;
use App\FlashDeal;
use App\SubCategory;
use App\GeneralSetting;
use App\SubSubCategory;
use App\BusinessSetting;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Controllers\SearchController;

class GeneralApiController extends Controller
{
    public function settings() {
        $settings = collect([]);
        $settings->put('currency_name', Currency::find(BusinessSetting::where('type', 'home_default_currency')->first()->value)->name);
        $settings->put('currency_symbol', Currency::find(BusinessSetting::where('type', 'home_default_currency')->first()->value)->symbol);
        $settings->put('currency_code', Currency::find(BusinessSetting::where('type', 'home_default_currency')->first()->value)->code);
        $settings->put('cash_payment', BusinessSetting::where('type', 'cash_payment')->first()->value);
        $settings->put('stripe_payment', BusinessSetting::where('type', 'stripe_payment')->first()->value);
        $settings->put('best_selling', BusinessSetting::where('type', 'best_selling')->first()->value);
        $settings->put('facebook_login', BusinessSetting::where('type', 'facebook_login')->first()->value);
        $settings->put('google_login', BusinessSetting::where('type', 'google_login')->first()->value);
        $settings->put('twitter_login', BusinessSetting::where('type', 'twitter_login')->first()->value);
        $settings->put('email_verification', BusinessSetting::where('type', 'email_verification')->first()->value);
        $settings->put('wallet_system', BusinessSetting::where('type', 'wallet_system')->first()->value);
        $settings->put('coupon_system', BusinessSetting::where('type', 'coupon_system')->first()->value);
        $settings->put('pickup_point', BusinessSetting::where('type', 'pickup_point')->first()->value);
        $settings->put('conversation_system', BusinessSetting::where('type', 'conversation_system')->first()->value);
        $settings->put('guest_checkout_active', BusinessSetting::where('type', 'guest_checkout_active')->first()->value);
        $settings->put('color', Color::find(GeneralSetting::first()->frontend_color)->code);
        $settings->put('general_setting', GeneralSetting::first());

        return $this->sendResponse($settings, 'Site Settings Retrived');
    }

    public function banner(){
        $sliders = Slider::where('published', 1)->get();

        return $this->sendResponse($sliders->toArray(), 'Sliders retrieved successfully.');
    }

    public function flash_sales(){
        try {
            $products = [];
            $flash_sales = FlashDeal::where('status', 1)->first();
            $flash_sales_products = $flash_sales->flash_deal_products()->get();
            foreach($flash_sales_products as $key => $flash){
                $products[$key] = Product::find($flash->product_id)->only('id', 'name', 'rating', 'num_of_sale', 'current_stock', 'thumbnail_img', 'purchase_price', 'created_at');
                $products[$key]['discount'] = $flash->discount;
                $products[$key]['discount_type'] = $flash->discount_type;
            }

            return $this->sendResponse($products, 'Flash Sales retrieved successfully.');
        } catch (\Throwable $th) {
            return $this->sendError('Flash Sales Not Found.');
        }

    }

    public function brands(){
        $brands = Brand::all();

        foreach($brands as $key => $brand){
            $brands[$key]['products'] = Product::where('brand_id', $brand->id)->where('published', 1)->get();
        }

        if(count($brands) > 0){
            return $this->sendResponse($brands, 'Brand retrieved successfully.');
        }else{
            return $this->sendError('Brand Not Found.');
        }
    }

    public function categories(){
        $categories = Category::all();

        foreach($categories as $key => $category){
            $categories[$key]['products'] = Product::where('category_id', $category->id)->where('published', 1)->get();
            $categories[$key]['sub_categories'] = SubCategory::where('category_id', $category->id)->get();
            foreach($categories[$key]['sub_categories'] as $skey => $sub){
                $categories[$key]['sub_categories'][$skey]['products'] = Product::where('subcategory_id', $sub->id)->where('published', 1)->get();
            }
        }

        if(count($categories) > 0){
            return $this->sendResponse($categories, 'Categories retrieved successfully.');
        }else{
            return $this->sendError('Categories Not Found.');
        }
    }

    public function sub_categories($category_id){
        $sub_categories = SubCategory::where('category_id', $category_id)->get();

        foreach($sub_categories as $key => $sub_category){
            $sub_categories[$key]['products'] = Product::where('subcategory_id', $sub_category->id)->where('published', 1)->get();
        }

        if(count($sub_categories) > 0){
            return $this->sendResponse($sub_categories, 'Sub Categories retrieved successfully.');
        }else{
            return $this->sendError('Sub Categories Not Found.');
        }
    }

    public function category_product($category_id){
        $products = Product::where('category_id', $category_id)->where('published', 1)->get();

        if(count($products) > 0){
            return $this->sendResponse($products, 'Products by Category retrieved successfully.');
        }else{
            return $this->sendError('Products by Category Not Found.');
        }
    }

    public function sub_category_product($sub_category_id){
        $products = Product::where('subcategory_id', $sub_category_id)->where('published', 1)->get();

        if(count($products) > 0){
            return $this->sendResponse($products, 'Products by Sub Category retrieved successfully.');
        }else{
            return $this->sendError('Products by Sub Category Not Found.');
        }
    }

    public function brand_product($brand_id){
        $products = Product::where('brand_id', $brand_id)->where('published', 1)->get();

        if(count($products) > 0){
            return $this->sendResponse($products, 'Products by Brand retrieved successfully.');
        }else{
            return $this->sendError('Products by Brand Not Found.');
        }
    }

    public function countries(){
        $countries = Country::all();

        if(count($countries) > 0){
            return $this->sendResponse($countries, 'Countries retrieved successfully.');
        }else{
            return $this->sendError('Country Not Found.');
        }
    }

    public function related_products($id){

        $product = Product::findOrFail($id);

        $related = filter_products(\App\Product::where('subcategory_id', $product->subcategory_id)->where('id', '!=', $product->id))->select('id', 'name', 'thumbnail_img', 'unit_price', 'purchase_price', 'rating')->limit(10)->get();

        if(count($related) > 0){
            return $this->sendResponse($related, 'Related Products retrieved successfully.');
        }else{
            return $this->sendError('Related Products Not Found.');
        }
    }

    public function best_selling(){

        $best_selling = filter_products(\App\Product::where('published', 1)->select('id', 'category_id', 'photos', 'featured_img', 'flash_deal_img', 'num_of_sale', 'name', 'thumbnail_img', 'unit_price', 'purchase_price', 'rating')->orderBy('num_of_sale', 'desc'))->limit(20)->get();

        if(count($best_selling) > 0){
            return $this->sendResponse($best_selling, 'Best Selling Products retrieved successfully.');
        }else{
            return $this->sendError('Products Not Found.');
        }
    }

    public function search(Request $request)
    {
        $query = $request->q;
        $brand_id = (Brand::where('slug', $request->brand)->first() != null) ? Brand::where('slug', $request->brand)->first()->id : null;
        $sort_by = $request->sort_by;
        $category_id = (Category::where('slug', $request->category)->first() != null) ? Category::where('slug', $request->category)->first()->id : null;
        $subcategory_id = (SubCategory::where('slug', $request->subcategory)->first() != null) ? SubCategory::where('slug', $request->subcategory)->first()->id : null;
        $subsubcategory_id = (SubSubCategory::where('slug', $request->subsubcategory)->first() != null) ? SubSubCategory::where('slug', $request->subsubcategory)->first()->id : null;
        $min_price = $request->min_price;
        $max_price = $request->max_price;
        $seller_id = $request->seller_id;

        $conditions = ['published' => 1];

        if($brand_id != null){
            $conditions = array_merge($conditions, ['brand_id' => $brand_id]);
        }
        if($category_id != null){
            $conditions = array_merge($conditions, ['category_id' => $category_id]);
        }
        if($subcategory_id != null){
            $conditions = array_merge($conditions, ['subcategory_id' => $subcategory_id]);
        }
        if($subsubcategory_id != null){
            $conditions = array_merge($conditions, ['subsubcategory_id' => $subsubcategory_id]);
        }
        if($seller_id != null){
            $conditions = array_merge($conditions, ['user_id' => Seller::findOrFail($seller_id)->user->id]);
        }

        $products = Product::where($conditions);

        if($min_price != null && $max_price != null){
            $products = $products->where('unit_price', '>=', $min_price)->where('unit_price', '<=', $max_price);
        }

        if($query != null){
            $searchController = new SearchController;
            $searchController->store($request);
            $products = $products->where('name', 'like', '%'.$query.'%')->orWhere('tags', 'like', '%'.$query.'%');
        }

        if($sort_by != null){
            switch ($sort_by) {
                case '1':
                    $products->orderBy('created_at', 'desc');
                    break;
                case '2':
                    $products->orderBy('created_at', 'asc');
                    break;
                case '3':
                    $products->orderBy('unit_price', 'asc');
                    break;
                case '4':
                    $products->orderBy('unit_price', 'desc');
                    break;
                default:
                    // code...
                    break;
            }
        }

        $products = filter_products($products)->paginate(12)->appends(request()->query());

        return $this->sendResponse($products, 'Products Reteived by Search Keyword');
    }

    public function return_policy() {
        $policy = Policy::where('name', 'return_policy')->first();
        return $this->sendResponse($policy, 'Policies content Retrived');
    }

    public function support_policy() {
        $policy = Policy::where('name', 'support_policy')->first();
        return $this->sendResponse($policy, 'Policies content Retrived');
    }
}
