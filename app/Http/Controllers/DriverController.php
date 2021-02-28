<?php

namespace App\Http\Controllers;

use App\Order;
use App\OrderDetail;
use Illuminate\Http\Request;
use App\Staff;
use App\Role;
use App\User;
use Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DriverController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $role = Role::where('name','Driver')->first();
        if($role){
            $drivers = Staff::where('role_id', $role->id)->orderBy('updated_at','desc')->get();
        }else{
            $drivers = [];
        }
        return view('drivers.index', compact('drivers'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $role = Role::where('name',"Driver")->first();
        return view('drivers.create', compact('role'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $user = new User;
        $user->name = $request->name;
        $user->email = $request->email;
        $user->phone = $request->mobile;
        $user->user_type = "staff";
        $user->password = Hash::make($request->password);
        if($user->save()){
            $staff = new Staff;
            $staff->user_id = $user->id;
            $staff->role_id = $request->role_id;
            if($staff->save()){
                flash(__('Driver has been inserted successfully'))->success();
                return redirect()->route('index.driver');
            }
        }

        flash(__('Something went wrong'))->error();
        return back();
    }


    public function driver_orders(Request $request){
        $payment_status = null;
        $delivery_status = null;
        $sort_search = null;
        $admin_user_id = Auth::user()->id;
        $orders = DB::table('orders')
            ->orderBy('code', 'desc')
            ->join('order_details', 'orders.id', '=', 'order_details.order_id')
            ->where('orders.delivery_man_id', $admin_user_id)
            ->select('orders.id')
            ->distinct();

        if ($request->payment_type != null){
            $orders = $orders->where('order_details.payment_status', $request->payment_type);
            $payment_status = $request->payment_type;
        }
        if ($request->delivery_status != null) {
            $orders = $orders->where('order_details.delivery_status', $request->delivery_status);
            $delivery_status = $request->delivery_status;
        }
        if ($request->has('search')){
            $sort_search = $request->search;
            $orders = $orders->where('code', 'like', '%'.$sort_search.'%');
        }
        $orders = $orders->paginate(15);
        return view('orders.order_driver', compact('orders','payment_status','delivery_status', 'sort_search', 'admin_user_id'));
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request)
    {
        return User::where('id',$request->driver_id)->select(['id','name','phone','email'])->first();
    }
    public function insert(Request $request){
        $order_update = Order::where('id',$request->order_id)->update(['delivery_man_id'=>$request->driver_id]);
        $order_details = OrderDetail::where('order_id',$request->order_id)->update(['delivery_status'=>'driver assigned']);
        if($order_update){
            flash(__('Driver has been assigned successfully'))->success();
            return redirect()->back();
        }else{
            flash(__('Something went wrong'))->error();
            return redirect()->back();
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $driver = Staff::findOrFail(decrypt($id));
        $role = Role::where('name','Driver')->first();
        return view('drivers.edit', compact('driver', 'role'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $staff = Staff::findOrFail($id);
        $user = $staff->user;
        $user->name = $request->name;
        $user->email = $request->email;
        $user->phone = $request->mobile;
        if(strlen($request->password) > 0){
            $user->password = Hash::make($request->password);
        }
        if($user->save()){
            $staff->role_id = $request->role_id;
            if($staff->save()){
                flash(__('Driver has been updated successfully'))->success();
                return redirect()->route('index.driver');
            }
        }

        flash(__('Something went wrong'))->error();
        return back();
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        User::destroy(Staff::findOrFail($id)->user->id);
        if(Staff::destroy($id)){
            flash(__('Driver has been deleted successfully'))->success();
            return redirect()->route('index.driver');
        }

        flash(__('Something went wrong'))->error();
        return back();
    }
}
