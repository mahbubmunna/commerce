<?php

namespace App\Http\Controllers\API;

use App\Review;
use App\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Slider;
use App\FlashDeal;
use App\Product;
use App\Brand;
use App\Category;
use App\SubCategory;
use App\Country;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;

class TestApiController extends Controller
{
    public function banner(){
        $sliders = Slider::where('published', 1)->get();

        return $this->sendResponse($sliders->toArray(), 'Sliders retrieved successfully.');
    }

    public function flash_sales(Request $request){
        try {
            $products = [];
            $flash_sales = FlashDeal::where('status', 1)->first();
            $flash_sales_products = $flash_sales->flash_deal_products()->get();
            foreach($flash_sales_products as $key => $flash){
                $products[$key] = Product::find($flash->product_id)->only('id', 'name', 'rating', 'num_of_sale', 'current_stock', 'thumbnail_img', 'purchase_price', 'created_at');
                $products[$key]['discount'] = $flash->discount;
                $products[$key]['discount_type'] = $flash->discount_type;
            }

            $paginate = 10;
            $page = $request->get('page', 1);

            $offSet = ($page * $paginate) - $paginate;
            $itemsForCurrentPage = array_slice($products, $offSet, $paginate, true);
            $result = new \Illuminate\Pagination\LengthAwarePaginator($itemsForCurrentPage, count($products), $paginate, $page, ['path'  => url()->current()]);

            $result = $result->toArray();
            return $this->sendResponse($result, 'Flash Sales retrieved successfully.');
        } catch (\Throwable $th) {
            return $this->sendError('Flash Sales Not Found.');
        }

    }

    public function brands(){
        $brands = Brand::paginate(10);

        if(count($brands) > 0){
            return $this->sendResponse($brands, 'Brand retrieved successfully.');
        }else{
            return $this->sendError('Brand Not Found.');
        }
    }

    public function categories(){
        $categories = Category::paginate(10);

        if(count($categories) > 0){
            return $this->sendResponse($categories, 'Categories retrieved successfully.');
        }else{
            return $this->sendError('Categories Not Found.');
        }
    }

    public function sub_categories($category_id){
        $sub_categories = SubCategory::where('category_id', $category_id)->paginate(10);

        if(count($sub_categories) > 0){
            return $this->sendResponse($sub_categories, 'Sub Categories retrieved successfully.');
        }else{
            return $this->sendError('Sub Categories Not Found.');
        }
    }

    public function category_product($category_id){
        $products = Product::where('category_id', $category_id)->where('published', 1)->paginate(10);

        if(count($products) > 0){
            return $this->sendResponse($products, 'Products by Category retrieved successfully.');
        }else{
            return $this->sendError('Products by Category Not Found.');
        }
    }

    public function sub_category_product($sub_category_id){
        $products = Product::where('subcategory_id', $sub_category_id)->where('published', 1)->paginate(10);

        if(count($products) > 0){
            return $this->sendResponse($products, 'Products by Sub Category retrieved successfully.');
        }else{
            return $this->sendError('Products by Sub Category Not Found.');
        }
    }

    public function brand_product($brand_id){
        $products = Product::where('brand_id', $brand_id)->where('published', 1)->paginate(10);

        if(count($products) > 0){
            return $this->sendResponse($products, 'Products by Brand retrieved successfully.');
        }else{
            return $this->sendError('Products by Brand Not Found.');
        }
    }

    public function product_review($product_id,Request $request){
        $data = [];
        $reviews = Review::where('product_id',$product_id)->select('id','user_id','rating','comment','created_at')->get();
        foreach ($reviews as $key => $review){
            $data[$key]['id'] = $review->id;
            $data[$key]['name'] = $review->user->name;
            $data[$key]['profile_pic'] = $review->user->avatar_original;
            $data[$key]['rating'] = $review->rating;
            $data[$key]['comment'] = $review->comment;
            $data[$key]['created_at'] = date("m-d-Y", strtotime($review->created_at));;
        }

        $paginate = 10;
        $page = $request->get('page', 1);
        $offSet = ($page * $paginate) - $paginate;
        $itemsForCurrentPage = array_slice($data, $offSet, $paginate, true);
        $result = new LengthAwarePaginator($itemsForCurrentPage, count($data), $paginate, $page, ['path'  => url()->current()]);

        $result = $result->toArray();
        return $this->sendResponse($result,'Product review retrieved successfully');
    }

    public function countries(){
        $countries = Country::all();

        if(count($countries) > 0){
            return $this->sendResponse($countries, 'Countries retrieved successfully.');
        }else{
            return $this->sendError('Country Not Found.');
        }
    }
}
