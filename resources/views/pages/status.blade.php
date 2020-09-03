@extends('master')

@section('content')
    <div class=" col-md-5 col-sm-10 mx-auto" >

        <h1 class="my-5 text-center">@lang('strings.checkout.response')</h1>

        <div class="card shadow">               
            <div class="card-header">
                <strong>@lang('strings.status')</strong>
            </div>         
            <div class="card-body">                
                {{$output}}            
            </div>
            <div class="card-footer">
                <a href="{{url('/')}}" class="btn btn-primary float-right">@lang('strings.checkout.back') <i class="fa fa-cart pl-3"></i></a>
            </div>            
        </div>
    </div>
@endsection
