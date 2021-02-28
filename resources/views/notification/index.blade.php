@extends('layouts.app')

@section('content')

<div class="row">
    <div class="col-sm-12">
        <a href="{{ route('notification.create')}}" class="btn btn-rounded btn-info pull-right">{{__('Add New Notification')}}</a>
    </div>
</div>

<br>

<!-- Basic Data Tables -->
<!--===================================================-->
<div class="panel">
    <div class="panel-heading">
        <h3 class="panel-title">{{__('Notification')}}</h3>
    </div>
    <div class="panel-body">
        <table class="table table-striped table-bordered demo-dt-basic" cellspacing="0" width="100%">
            <thead>
                <tr>
                    <th width="10%">#</th>
                    <th>{{__('Type')}}</th>
                    <th>{{__('Image')}}</th>
                    <th>{{__('Description')}}</th>
                    <th>{{__('Status')}}</th>
                    <th width="10%">{{__('Options')}}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($notifications as $key => $notification)
                    <tr>
                        <td>{{$key+1}}</td>
                        <td>{{$notification->notify_type}}</td>
                        <td>
                            @if($notification->image)
                                <img src="{{asset($notification->image)}}" style="height: 50px" >
                            @else
                                N/A
                            @endif
                        </td>
                        <td>{{$notification->description}}</td>
                        <td>
                            @if($notification->status=='active')
                                <span class="tag tag-success">Active</span>
                            @else
                                <span class="tag tag-danger">Inactive</span>
                            @endif
                        </td>
                        <td>
                            <div class="btn-group dropdown">
                                <button class="btn btn-primary dropdown-toggle dropdown-toggle-icon" data-toggle="dropdown" type="button">
                                    {{__('Actions')}} <i class="dropdown-caret"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-right">
                                    <li><a href="{{route('notification.edit', encrypt($notification->id))}}">{{__('Edit')}}</a></li>
                                    <li><a onclick="confirm_modal('{{route('notification.destroy', $notification->id)}}');">{{__('Delete')}}</a></li>
                                </ul>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

    </div>
</div>

@endsection
