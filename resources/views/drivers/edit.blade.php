@extends('layouts.app')

@section('content')

<div class="col-lg-6 col-lg-offset-3">
    <div class="panel">
        <div class="panel-heading">
            <h3 class="panel-title">{{__('Driver Information')}}</h3>
        </div>

        <!--Horizontal Form-->
        <!--===================================================-->
        <form class="form-horizontal" action="{{ route('update.driver', $driver->id) }}" method="POST" enctype="multipart/form-data">
        	@csrf
            <input type="hidden" name="role_id" value="{{$role->id}}">
            <div class="panel-body">
                <div class="form-group">
                    <label class="col-sm-3 control-label" for="name">{{__('Name')}}</label>
                    <div class="col-sm-9">
                        <input type="text" placeholder="{{__('Name')}}" id="name" name="name" value="{{ $driver->user->name }}" class="form-control" required>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-sm-3 control-label" for="email">{{__('Email')}}</label>
                    <div class="col-sm-9">
                        <input type="text" placeholder="{{__('Email')}}" id="email" name="email" value="{{ $driver->user->email }}" class="form-control" required>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-sm-3 control-label" for="mobile">{{__('Phone')}}</label>
                    <div class="col-sm-9">
                        <input type="text" placeholder="{{__('Phone')}}" id="mobile" name="mobile" value="{{ $driver->user->phone }}" class="form-control" required>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-sm-3 control-label" for="password">{{__('Password')}}</label>
                    <div class="col-sm-9">
                        <input type="password" placeholder="{{__('Password')}}" id="password" name="password" class="form-control">
                    </div>
                </div>
{{--                <div class="form-group">--}}
{{--                    <label class="col-sm-3 control-label" for="name">{{__('Role')}}</label>--}}
{{--                    <div class="col-sm-9">--}}
{{--                        <select name="role_id" required class="form-control demo-select2-placeholder">--}}
{{--                            @foreach($roles as $role)--}}
{{--                                <option value="{{$role->id}}" @php if($staff->role_id == $role->id) echo "selected"; @endphp >{{$role->name}}</option>--}}
{{--                            @endforeach--}}
{{--                        </select>--}}
{{--                    </div>--}}
{{--                </div>--}}
            </div>
            <div class="panel-footer text-right">
                <button class="btn btn-purple" type="submit">{{__('Save')}}</button>
            </div>
        </form>
        <!--===================================================-->
        <!--End Horizontal Form-->

    </div>
</div>

@endsection
