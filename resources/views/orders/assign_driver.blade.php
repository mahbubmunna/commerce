@extends('layouts.app')

@section('content')

    <div class="col-lg-6 col-lg-offset-3">
        <div class="panel">
            <div class="panel-heading">
                <h3 class="panel-title">Order Delivery Information</h3>
            </div>
            <div class="panel-body">
                <div class="form-group">
                    <div class="col-sm-12">
                        <h4 class="panel-title" id="message" style="display: none;"></h4>
                    </div>
                </div>
                <div class="form-group">
                    <div class="col-sm-9">
                        <div><b>Code</b><p>{{$order->code}}</p></div>
                        <input type="hidden" id="order_id" value="{{$order->id}}">
                    </div>
                </div>
                <div class="form-group">
                    <div class="col-sm-9">
                        <div><b>Payment Status</b><p>{{$order->payment_status}}</p></div>
                    </div>
                </div>
                <div class="form-group">
                    <div class="col-sm-9">
                        <div><b>Payment Details</b><p>{{$order->payment_details}}</p></div>
                    </div>
                </div>

                <div class="form-group">
                    <div class="col-sm-6">
                        <div>
                            <b>Shipping Address</b><br>
                            <small>Name: <b>{{ json_decode($order->shipping_address)->name }}</b></small><br>
                            <small>Address: {!! json_decode($order->shipping_address)->address !!}</small><br>
                            <small>City: {{ json_decode($order->shipping_address)->city  }}</small><br>
                            <small>Coutry: {{ json_decode($order->shipping_address)->country  }}</small><br>
                            <small>Postal code: {{ json_decode($order->shipping_address)->postal_code }}</small><br>
                            <small>Phone Number: {{ json_decode($order->shipping_address)->phone }}</small><br>
                        </div>
                    </div>
{{--                    @php--}}
{{--                        $order_details =\App\OrderDetail::where('order_id',$order->id)->first();--}}
{{--                        $driver_details = \App\Driver::where('id', $order_details->driver_id)->first();--}}
{{--                    @endphp--}}
{{--                    @if(!empty($driver_details))--}}
{{--                        <div class="col-sm-6">--}}
{{--                            <div>--}}
{{--                                <b>Current Driver Details</b><br>--}}
{{--                                <small>Name: <b>{{ $driver_details['name'] }}</b></small><br>--}}
{{--                                <small>Phone: {{$driver_details['phone']}}</small><br>--}}
{{--                                <small>email: {{$driver_details['email']}}</small><br>--}}
{{--                                <small>City: {{ $driver_details['city']  }}</small><br>--}}
{{--                                <small>Area: {{ $driver_details['area']  }}</small><br>--}}
{{--                                <small>Plate No: {{ $driver_details['plate_no'] }}</small><br>--}}
{{--                            </div>--}}
{{--                        </div>--}}
{{--                    @endif--}}
                </div>
                <div class="form-group">
                    <label class="col-sm-9 control-label" for="name"><b>Drivers</b></label>
                    <div class="col-sm-12">
                        <select name="driver_id" id="driver_id"  required class="form-control demo-select2-placeholder">
                            <option value="0">Select a driver</option>
                            @foreach($drivers as $driver)
                                @php
                                    $driver_details = \App\User::where('id', $driver->user_id)->first();
                                @endphp
                                <option value="{{$driver_details->id}}">{{$driver_details->name}}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <br>
                <form class="" action="{{route('driver.insert')}}" method="POST" enctype="multipart/form-data">
                <form class="" action="" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="form-group">
                        <div class="col-sm-12">
                            <div id="driver_details">

                            </div>
                            <div class="panel-footer text-right" id="save_button" style="display: none">
                                <input class="btn btn-purple" type="submit" id="store_driver" value="{{__('Save')}}">
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>


@endsection

@section('script')
    <script type="text/javascript">
        $('#driver_id').on('change',function () {
            var driverId = $('#driver_id').val();
            $('#driver_details').html("");
            document.getElementById('save_button').style="display:none;";

            $.post("{{route('driver.details')}}",{ _token: '{{csrf_token()}}', driver_id :driverId})
                .done(function(data){
                    console.log(data);
                    var orderId = $('#order_id').val();
                    $('#driver_details').html(
                        "<br><b> "+"Selected Driver Details"+" </b><br>"+
                        "<p> Name: <b>"+data.name+"</b></p>"+
                        "<p> Phone: <b>"+data.phone+"</b></p>"+
                        "<p> Email: <b>"+data.email+"</b></p>"+
                        '<input type=\"hidden\" name=\"driver_id\" value=\"'+data.id+'\" />'+
                        '<input type=\"hidden\" name=\"order_id\" value=\"'+orderId+'\" />'
                    );
                    document.getElementById('save_button').style="display:block;";
                })
                .fail(function(xhr, status, error) {
                    // $('#mobile_verfication').html("<p class='helper'> "+xhr.responseJSON.message+" </p>");
                });
        });
    </script>
@endsection

