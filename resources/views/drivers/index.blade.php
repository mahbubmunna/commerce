@extends('layouts.app')

@section('content')

<div class="row">
    <div class="col-sm-12">
        <a href="{{ route('add.driver')}}" class="btn btn-rounded btn-info pull-right">{{__('Add New Driver')}}</a>
    </div>
</div>

<br>

<!-- Basic Data Tables -->
<!--===================================================-->
<div class="panel">
    <div class="panel-heading">
        <h3 class="panel-title">{{__('drivers')}}</h3>
    </div>
    <div class="panel-body">
        <table class="table table-striped table-bordered demo-dt-basic" cellspacing="0" width="100%">
            <thead>
                <tr>
                    <th width="10%">#</th>
                    <th>{{__('Name')}}</th>
                    <th>{{__('Email')}}</th>
                    <th>{{__('Phone')}}</th>
                    <th>{{__('Role')}}</th>
                    <th width="10%">{{__('Options')}}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($drivers as $key => $driver)
                    <tr>
                        <td>{{$key+1}}</td>
                        <td>{{$driver->user->name}}</td>
                        <td>{{$driver->user->email}}</td>
                        <td>{{$driver->user->phone}}</td>
                        <td>{{$driver->role->name}}</td>
                        <td>
                            <div class="btn-group dropdown">
                                <button class="btn btn-primary dropdown-toggle dropdown-toggle-icon" data-toggle="dropdown" type="button">
                                    {{__('Actions')}} <i class="dropdown-caret"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-right">
                                    <li><a href="{{route('edit.driver', encrypt($driver->id))}}">{{__('Edit')}}</a></li>
                                    <li><a onclick="confirm_modal('{{route('destroy.driver', $driver->id)}}');">{{__('Delete')}}</a></li>
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
