@extends('master')

@section('content')
    <div>

        <h1 class="my-5">@lang('strings.cart')</h1>

        <!-- shopping cart -->
        <div class="card">   
            <div class="card-header">
                <div class="row py-2">                    
                    <div class="col-8">
                        <strong>@lang('strings.cart.name')</strong>
                    </div>
                    <div class="col-4">
                        <strong>@lang('strings.cart.qty')</strong>
                    </div>
                </div>                
              </div>         
            <div class="card-body pt-0">
                <hr>
               
                @foreach ($products as $product)
                    <div class="row">                    
                        <div class="col-8">
                            <h3>{{$product->name}}</h3>
                        </div>
                        <div class="col-3">
                            <div class="input-group">                              
                                <input type="text" class="form-control">
                                <div class="input-group-append" id="button-addon4">
                                    <button class="btn btn-info" type="button">
                                        <i class="fa fa-check" aria-hidden="true"></i>
                                    </button>
                                </div>
                            </div>
                            <small id="passwordHelpBlock" class="form-text text-muted">
                                @lang('strings.cart.maxhint') 2
                            </small>
                        </div>
                        <div class="col-1">
                            <button class="btn btn-danger" type="button">
                                <i class="fa fa-times" aria-hidden="true"></i>
                            </button>
                        </div>
                    </div>
                    <hr>
                @endforeach  
            </div>

            <div class="card-footer">
                <div class="row">
                    <div class="col-8">
                        <h3>@lang('strings.cart.total') 452.00 @lang('strings.currency')</h3>
                    </div>
                    <div class="col-4 ">
                        <button class="btn btn-primary float-right">@lang('strings.cart.checkout') <i class="fa fa-arrow-right pl-3" aria-hidden="true"></i></button>
                    </div>
                </div>
            </div>
        </div>        
    </div>
@endsection