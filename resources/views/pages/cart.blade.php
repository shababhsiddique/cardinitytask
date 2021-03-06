@extends('master')

@section('content')
    <div class="col-md-10 col-sm-12 mx-auto">

        <h1 class="my-5 text-center">@lang('strings.cart')</h1>

        <!-- shopping cart -->
        <div class="card shadow">   
            <form method="POST" action="{{url('/cart/update')}}">
                <div class="card-body p-0">  
                    @csrf
                    <table class="table">
                        <thead class="thead-light">
                            <tr>
                                <th style="width: 50%"> @lang('strings.cart.name')</th>
                                <th style="width: 7%"> @lang('strings.cart.price') </th>
                                <th style="width: 20%"> @lang('strings.cart.qty') </th>                            
                                <th style="width: 7%"> @lang('strings.cart.subtotal') </th>
                                <th style="width: 6%">  </th>
                            </tr>
                        </thead>
                            @foreach ($cartProducts as $product)                
                                <tr>
                                    <td>
                                        <strong>{{$product->name}}</strong>{{$product->id}}
                                    </td>
                                    <td>
                                        {{number_format($product->price, 0)}}
                                    </td>
                                    <td>
                                        <input type="hidden" name="rowId[]" value="{{$product->id}}" />
                                        <input type="text" name="quantity[]" class="form-control" value="{{$product->quantity}}">
                                    
                                        <small id="passwordHelpBlock" class="form-text text-muted">
                                            @lang('strings.cart.maxhint') 2
                                        </small>
                                    </td>
                                    <td>
                                    <strong>{{number_format($product->getPriceSum(), 0)}}</strong>
                                    </td>
                                    <td>
                                        <a href="{{url('cart/remove/'.$product->id)}}" class="btn btn-link" type="button">
                                            <i class="fa fa-trash-o fa-lg" aria-hidden="true"></i>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach  
                            @error('rowId')
                            <tr>
                                <td colspan="5">
                                    <strong class="text-danger">@lang('strings.cart.empty_error')</strong>
                                </td>
                            </tr>                                
                            @enderror      
                    </table>                                  
                </div>

                <div class="card-footer">
                    <div class="row">
                        <div class="col-6">
                            <strong>@lang('strings.cart.total') {{$cartTotal}} @lang('strings.currency')</strong>
                        </div>
                        <div class="col-6">
                            <div class="btn-group float-right" role="group" aria-label="Basic example">
                                <button type="submit" name="action" value="update" class="btn btn-primary ">@lang('strings.cart.update') <i class="fa fa-floppy-o pl-3" aria-hidden="true"></i></button>
                                <button type="submit" name="action" value="checkout" class="btn btn-success">@lang('strings.cart.checkout') <i class="fa fa-arrow-right pl-3" aria-hidden="true"></i></button>
                            </div>                            
                        </div>
                    </div>
                </div>
            </form>
        </div>        
    </div>
@endsection