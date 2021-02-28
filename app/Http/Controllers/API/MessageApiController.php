<?php

namespace App\Http\Controllers\API;

use App\Message;
use Auth;
use JWTAuth;
use App\User;
use Response;
use Validator;
use JWTFactory;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use DB;

class MessageApiController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
       if(auth()->user()){
           $message = new Message;
           $message->conversation_id = $request->conversation_id;
           $message->user_id = auth()->user()->id;
           $message->message = $request->message;
           $message->save();
           $conversation = $message->conversation;
           if ($conversation->sender_id == auth()->user()->id) {
               $conversation->receiver_viewed ="1";
           }
           elseif($conversation->receiver_id == auth()->user()->id) {
               $conversation->sender_viewed ="1";
           }
           $conversation->save();
           $result['message'] = $message;
           return $this->sendResponse($result, 'Message sent successfully');
       }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
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
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
