@extends('master')

@section('content')
    <div class="col-md-10 col-sm-12 mx-auto">

        <h1 class="my-5 text-center">@lang('strings.shop')</h1>

        <!-- prouct list -->
        <ul class="list-group shadow">
            
            @foreach ($products as $product)    
                <!-- product item builder -->    
                <li class="list-group-item">
                    <div class="media row p-3">
                        <div class="col">
                            <h5 class="font-weight-bold mb-2">{{$product->name}}</h5>
                            <p class="small">{{$product->quantity}} @lang('strings.shop.stock')</p>
                            <h6 class="font-weight-bold my-2">{{number_format($product->price, 2)}} @lang("strings.currency")</h6>
                        </div>
                        <div class="col">
                            
                            
                            <a href="{{url('cart/add/'.$product->product_id)}}" class="btn btn-primary float-right" >
                                <i class="fa fa-cart-plus mr-2" aria-hidden="true"></i>
                                <strong>@lang('strings.shop.addcart')</strong>
                            </a>
                            
                        </div>
                    </div> <!-- End -->
                </li>
                <!-- End -->
            @endforeach        

        </ul>
    </div>
@endsection