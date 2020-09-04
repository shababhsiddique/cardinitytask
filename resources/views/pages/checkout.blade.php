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
                                        {{number_format($product->price, 0)}}
                                    </td>
                                    <td>
                                        {{$product->quantity}}
                                    </td>
                                    <td class="text-right">
                                        {{number_format($product->getPriceSum(), 0)}}
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
                                        <label for="holder">@lang('strings.checkout.holder')</label>
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
                                        <label for="pan">@lang('strings.checkout.pan')</label>
                                        <div class="input-group mb-3">     
                                            <input type="text" id="pan" required name="pan" class="form-control" value="{{old('pan')}}" >    
                                            <span id="card_type" class="input-group-text"></span>                            
                                        </div>
                                        @error('pan')
                                            <small class="text-danger">{{ $message }}</small>
                                        @enderror
                                        @error('payment_instrument.pan')
                                            <small class="text-danger">@lang("validation.payment_instrument.pan")</small>
                                        @enderror
                                        @error('payment_instrument.card_brand')
                                            <small class="text-danger">@lang("validation.payment_instrument.card_brand")</small>
                                        @enderror
                                    </div>
                                </div>
                            </div>
        
                            <div class="form-group">
                                <div class="row">
                                    <div class="col-6">
                                        <label for="date">@lang('strings.checkout.exp')</label>
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
                                        @error('payment_instrument.cvc')
                                            <small class="text-danger">@lang("validation.payment_instrument.cvc")</small>
                                        @enderror
                                    </div>      
                                </div>  
                            </div>
                            
                            @error('payment_instrument.fields')
                                <small class="text-danger">@lang("validation.payment_instrument.fields")</small>
                            @enderror                           
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


@push('footer-scripts')
    <script type="text/javascript">

        function creditCardTypeFromNumber(num) {
            // first, sanitize the number by removing all non-digit characters.
            num = num.replace(/[^\d]/g,'');
            // now test the number against some regexes to figure out the card type.
            if (num.match(/^5[1-5]\d{14}$/)) {
                return 'MasterCard';
            } else if (num.match(/^4\d{15}/) || num.match(/^4\d{12}/)) {
                return 'Visa';
            } else if (num.match(/^3[47]\d{13}/)) {
                return 'AmEx';
            } else if (num.match(/^6011\d{12}/)) {
                return 'Discover';
            }
            return 'UNKNOWN';
        }

        //Check 
        $('#pan').keyup(function() {
            $('#card_type').text(creditCardTypeFromNumber($(this).val()));
        });    

    </script>
@endpush