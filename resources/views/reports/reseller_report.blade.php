@extends('layouts.app')

@section('content')
    <div class="col-md-offset-2 col-md-8">
        <div class="panel">
            <!--Panel heading-->
            <div class="panel-heading">
                <h3 class="panel-title">{{ __('Pickup Point') }} {{ __('Reports') }}</h3>
            </div>

            <!--Panel body-->
            <div class="panel-body">
                <div class="table-responsive">
                    <table class="table table-striped mar-no demo-dt-basic">
                        <thead>
                            <tr>
                                <th>{{ __('Pick-up Point') }}</th>
                                {{-- <th>{{ __('Num of Sale') }}</th> --}}
                                <th>{{ __('Num. of Products') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($resellers as $key => $reseller)
                                <tr>
                                    <td>{{ $reseller->name }}</td>
                                    {{-- <td>{{ $reseller->orders->count() }}</td> --}}
                                    <td>{{ $reseller->orderDetails->sum('quantity') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

@endsection
