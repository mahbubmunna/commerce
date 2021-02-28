<?php

namespace App\Http\Controllers\API;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Conversation;
use App\BusinessSetting;
use App\Message;
use Auth;
use App\Product;
use Response;
use App\Http\Controllers\Controller;
use Illuminate\Pagination\LengthAwarePaginator;

class ConversationApiController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        try{
            if(auth()->user()){
                $data = [];
                $conversations = Conversation::where('sender_id',auth()->user()->id)->orWhere('receiver_id', auth()->user()->id)->orderBy('created_at', 'desc')->get();
                foreach ($conversations as $key => $conversation){
                    $data[$key]['id'] = $conversation->id;
                    $data[$key]['title'] = $conversation->title;
                    $data[$key]['sender_id'] = $conversation->sender->name;
                    $data[$key]['sender_img'] = $conversation->sender->avatar_original;
                    $data[$key]['receiver_id'] = $conversation->receiver->name;
                    $data[$key]['receiver_img'] = $conversation->receiver->avatar;
                    $last_message = Message::where('conversation_id',$conversation->id)->orderBy('created_at', 'desc')->first();
                    $data[$key]['last_message'] = $last_message->message;
                    $data[$key]['created_at'] = Carbon::parse($last_message->created_at)->diffForHumans();
                }
                $paginate = 10;
                $page = $request->get('page', 1);
                $offSet = ($page * $paginate) - $paginate;
                $itemsForCurrentPage = array_slice($data, $offSet, $paginate, true);
                $result = new LengthAwarePaginator($itemsForCurrentPage, count($data), $paginate, $page, ['path'  => url()->current()]);
                $result = $result->toArray();
                return $this->sendResponse($result, 'Conversations retrieved successfully.');
            }else{
                return $this->sendError('Unauthorized User', 401);
            }
        }catch (\Throwable $th) {
                return $this->sendError('Conversation Not Found.');
        }

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
            $conversation = new Conversation;
            $conversation->sender_id = auth()->user()->id;
            $conversation->receiver_id = Product::findOrFail($request->product_id)->user->id;
            $conversation->title = $request->title;
            $conversation->save();

            $message = new Message;
            $message->conversation_id = $conversation->id;
            $message->user_id = auth()->user()->id;
            $message->message = $request->message;
            $message->save();
            $result['conversation_id'] = $conversation->id;
            return $this->sendResponse($result, 'Conversation created successfully');
        }

    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id,Request $request)
    {
       try{
           if(auth()->user()){
               $data = [];
               $conversation = Conversation::findOrFail($id);
               $messages = Message::where('conversation_id',$conversation->id)->get();
               foreach ($messages as $key => $message){
                   $data[$key]['message_id'] = $message->id;
                   $data[$key]['name'] = $message->user->name;
                   $data[$key]['image'] = $message->user->avatar;
                   $data[$key]['message'] = $message->message;
                   $data[$key]['created_at'] = Carbon::parse($message->created_at)->diffForHumans();
               }
               if ($conversation->sender_id == auth()->user()->id) {
                   $conversation->sender_viewed = 1;
               }
               elseif($conversation->receiver_id == auth()->user()->id) {
                   $conversation->receiver_viewed = 1;
               }
               $conversation->save();

               $paginate = 10;
               $page = $request->get('page', 1);
               $offSet = ($page * $paginate) - $paginate;
               $itemsForCurrentPage = array_slice($data, $offSet, $paginate, true);
               $result = new LengthAwarePaginator($itemsForCurrentPage, count($data), $paginate, $page, ['path'  => url()->current()]);
               $result = $result->toArray();
               return $this->sendResponse($result, 'Conversations retrieved successfully.');

           }else{
               return $this->sendError('Unauthorized User', 401);
           }
       }catch (\Throwable $th) {
           return $this->sendError('Conversation Not Found.');
       }
    }


    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function refresh(Request $request)
    {
        $conversation = Conversation::findOrFail(decrypt($request->id));
        if($conversation->sender_id == Auth::user()->id){
            $conversation->sender_viewed = 1;
            $conversation->save();
        }
        else{
            $conversation->receiver_viewed = 1;
            $conversation->save();
        }
        return view('frontend.partials.messages', compact('conversation'));
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function admin_show($id)
    {
        $conversation = Conversation::findOrFail(decrypt($id));
        if ($conversation->sender_id == Auth::user()->id) {
            $conversation->sender_viewed = 1;
        }
        elseif($conversation->receiver_id == Auth::user()->id) {
            $conversation->receiver_viewed = 1;
        }
        $conversation->save();
        return view('conversations.show', compact('conversation'));
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
        $conversation = Conversation::findOrFail(decrypt($id));
        foreach ($conversation->messages as $key => $message) {
            $message->delete();
        }
        if(Conversation::destroy(decrypt($id))){
            flash(__('Conversation has been deleted successfully'))->success();
            return back();
        }
    }
}
