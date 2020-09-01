@extends('master')

@section('content')
    <div>

        <h1 class="my-5">@lang('strings.shop')</h1>

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
                            <h4>&nbsp;</h4>
                            
                            <button type="button" class="btn btn-primary float-right" 
                                @if($product->quantity <= 0)
                                    disabled 
                                @endif>
                                <i class="fa fa-cart-plus mr-2" aria-hidden="true"></i>
                                <strong>@lang('strings.shop.addcart')</strong>
                            </button>
                            
                        </div>
                    </div> <!-- End -->
                </li>
                <!-- End -->
            @endforeach        

        </ul>
    </div>
@endsection