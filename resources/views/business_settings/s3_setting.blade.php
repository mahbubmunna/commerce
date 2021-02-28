@extends('layouts.app')

@section('content')

<div class="row">
    <div class="col-md-3"></div>
    <div class="col-md-6">
        <div class="panel">
            <div class="panel-heading">
                <h3 class="panel-title text-center">{{__('S3 BUCKET CREDENTIALS')}}</h3>
            </div>
            <div class="panel-body">
                <form class="form-horizontal" action="{{ route('env_key_update.update') }}" method="POST">
                    @csrf
                    <div class="form-group">
                        <input type="hidden" name="types[]" value="S3_ACCESS_KEY_ID">
                        <div class="col-lg-3">
                            <label class="control-label">{{__('S3 ACCESS KEY ID ')}}</label>
                        </div>
                        <div class="col-lg-6">
                            <input type="text" class="form-control" name="S3_ACCESS_KEY_ID" value="{{  env('S3_ACCESS_KEY_ID') }}" placeholder="S3 ACCESS KEY ID ">
                        </div>
                    </div>
                    <div class="form-group">
                        <input type="hidden" name="types[]" value="S3_SECRET_ACCESS_KEY">
                        <div class="col-lg-3">
                            <label class="control-label">{{__('S3 SECRET ACCESS KEY')}}</label>
                        </div>
                        <div class="col-lg-6">
                            <input type="text" class="form-control" name="S3_SECRET_ACCESS_KEY" value="{{  env('S3_SECRET_ACCESS_KEY') }}" placeholder="S3 SECRET ACCESS KEY">
                        </div>
                    </div>
                    <div class="form-group">
                        <input type="hidden" name="types[]" value="S3_DEFAULT_REGION">
                        <div class="col-lg-3">
                            <label class="control-label">{{__('S3 DEFAULT REGION')}}</label>
                        </div>
                        <div class="col-lg-6">
                            <input type="text" class="form-control" name="S3_DEFAULT_REGION" value="{{  env('S3_DEFAULT_REGION') }}" placeholder="S3 DEFAULT REGION" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <input type="hidden" name="types[]" value="S3_BUCKET">
                        <div class="col-lg-3">
                            <label class="control-label">{{__('S3 BUCKET NAME')}}</label>
                        </div>
                        <div class="col-lg-6">
                            <input type="text" class="form-control" name="S3_BUCKET" value="{{  env('S3_BUCKET') }}" placeholder="S3 BUCKET NAME" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="col-lg-12 text-right">
                            <button class="btn btn-purple" type="submit">{{__('Save')}}</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection
