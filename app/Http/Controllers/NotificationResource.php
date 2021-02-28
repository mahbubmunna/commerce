<?php

namespace App\Http\Controllers;

use App\Notifications;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Http\Controllers\Controller;
use Carbon\Carbon;

class NotificationResource extends Controller
{

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $notifications = Notifications::orderBy('created_at' , 'desc')->get();
        return view('notification.index', compact('notifications'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('notification.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {

        $this->validate($request, [
            'notify_type' => 'required',
            'image' => 'required|mimes:jpeg,jpg,png|max:5242880',
        ]);

        try{

            $Notifications = new Notifications;
            $Notifications->notify_type = $request->notify_type;

            if($request->hasFile('image')){
                $Notifications->image = $request->file('image')->store('uploads/notifications/images');
            }

            $Notifications->description = $request->description;
            $Notifications->expiry_date = date('Y-m-d H:i:s');
            $Notifications->status = $request->status;
            $Notifications->save();

            flash('Notification Saved Successfully')->success();
            return back();

        }

        catch (ModelNotFoundException $e) {
            flash('Something went wrong')->error();
            return back();
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Reason  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        try {
            return Notifications::findOrFail($id);
        } catch (ModelNotFoundException $e) {
            return $e;
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Reason  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        try {
            $notification = Notifications::findOrFail(decrypt($id));
            return view('notification.edit',compact('notification'));
        } catch (ModelNotFoundException $e) {
            return $e;
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Reason  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $this->validate($request, [
            'notify_type' => 'required',
            'image' => 'mimes:jpeg,jpg,png|max:5242880',
        ]);

        try {

            $Notifications = Notifications::findOrFail($id);

            $Notifications->notify_type = $request->notify_type;

            if($request->hasFile('image')){
                $Notifications->image = $request->file('image')->store('uploads/notifications/images');
            }

            $Notifications->description = $request->description;
            $Notifications->expiry_date = date('Y-m-d H:i:s');
            $Notifications->status = $request->status;
            $Notifications->save();

            flash('Notification Updated Successfully')->success();
            return redirect()->route('notification.index');
        }

        catch (ModelNotFoundException $e) {
            flash('Something went wrong')->error();
            return back();
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Reason  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        try {
            Notifications::find($id)->delete();
            flash('Notification Updated Successfully')->success();
            return back();
        }
        catch (ModelNotFoundException $e) {
            flash('Something went wrong')->error();
            return back();
        }
    }

    /**
       get notifications for respcted types
    */
    public function getnotify($type)
    {
        if($type=='user'){
            $search_type='provider';
        }
        else{
            $search_type='user';
        }

        try {

            $notification = Notifications::where('notify_type', '!=', $search_type)->where('status', 'active')->orderBy('created_at' , 'desc')->get();
            return response()->json($notification);
        }
        catch (Exception $e) {
            return response()->json(['error' => 'Something went wrong'], 500);
        }
    }
}
