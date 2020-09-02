@extends('master')

@section('content')
    <div >

        <h1 class="my-5 text-center">@lang('strings.checkout')</h1>

        
        <div class="row">
            <div class="col">
                <div class="card shadow">
                    <table class="table">
                        <thead class="thead-light">
                            <tr>
                                <th class="col-6"> @lang('strings.cart.name')</th>
                                <th class="col-1"> @lang('strings.cart.price') </th>
                                <th class="col-1"> @lang('strings.cart.qty') </th>                            
                                <th class="col-1"> @lang('strings.cart.subtotal') </th>
                            </tr>
                       </thead>
                       <tbody>
                            @foreach ($cartProducts as $product)                
                                <tr>
                                    <td>
                                        {{$product->name}}
                                    </td>
                                    <td>
                                        {{$product->price}}
                                    </td>
                                    <td>
                                        {{$product->quantity}}
                                    </td>
                                    <td class="text-right">
                                        {{$product->getPriceSum()}}
                                    </td>
                                </tr>
                            @endforeach  
                            <tr>
                                <td colspan="2"><strong>@lang('strings.cart.total')</strong></td>
                                <td class="text-right" colspan="2"><strong>{{$cartTotal}}</strong></td>
                            </tr>
                       </tbody>
                    </table>
                </div>
            </div>

            <div class="col">
                <form method="POST" action="{{url('cart/pay/submit')}}">                    
                    @csrf
                    <div class="card shadow">               
                        <div class="card-header">
                            <strong>@lang('strings.checkout.details')</strong>
                        </div>         
                        <div class="card-body">   
                            
                            <div class="form-group">
                                <div class="row">
                                    <div class="col-12">
                                        <label for="holder">Card Holder</label>
                                        <input type="text" id="holder" required name="holder" class="form-control" value="{{old('holder')}}" >                                
                                        @error('holder')
                                            <small class="text-danger">{{ $message }}</small>
                                        @enderror
                                    </div>
                                </div>
                            </div>
        
                            <div class="form-group">
                                <div class="row">
                                    <div class="col-12">
                                        <label for="pan">PAN Number</label>
                                        <div class="input-group mb-3">     
                                            <input type="text" id="pan" required name="pan" class="form-control" value="{{old('pan')}}" >    
                                            <span class="input-group-text">Visa</span>                            
                                        </div>
                                        @error('pan')
                                            <small class="text-danger">{{ $message }}</small>
                                        @enderror
                                        @error('incorrect_pan')
                                            <small class="text-danger">@lang("validation.incorrect_pan")</small>
                                        @enderror
                                    </div>
                                </div>
                            </div>
        
                            <div class="form-group">
                                <div class="row">
                                    <div class="col-6">
                                        <label for="date">Expiry Date</label>
                                        <div class="input-group mb-3">                                            
                                            <input type="text" id="exp_month" placeholder="mm" required name="exp_month" class="form-control" value="{{old('exp_month')}}">                                            
                                            <span class="input-group-text">/</span>
                                            <input type="text" id="exp_year" placeholder="yyyy" required name="exp_year" class="form-control" value="{{old('exp_year')}}">                                        
                                        </div>   
                                        @error('exp_month')
                                                <small class="text-danger">{{ $message }}</small>
                                        @enderror    
                                        @error('exp_year')
                                            <small class="text-danger">{{ $message }}</small>
                                        @enderror                                 
                                    </div>           
                                    <div class="col-6">
                                        <label for="CVV">CVV</label>
                                        <input type="text" name="cvv" required id="CVV" class="form-control" value="{{old('cvv')}}">
                                        @error('cvv')
                                            <small class="text-danger">{{ $message }}</small>
                                        @enderror
                                    </div>      
                                </div>  
                            </div> 
                        </div>
                        <div class="card-footer">
                            <button type="submit" class="btn btn-primary float-right">@lang('strings.checkout.pay') <i class="fa fa-arrow-right pl-3" aria-hidden="true"></i></button>
                        </div>            
                    </div>        
                </form>
            </div>
        </div>
    </div>
@endsection