@extends('master')

@section('menu')    
     
    <!-- common menu -->
    <div class="d-flex flex-column flex-md-row align-items-center p-3 px-md-4 mb-3 bg-white border-bottom shadow-sm">
        <h5 class="my-0 mr-md-auto font-weight-normal">
          <a href="{{url('/')}}">@lang('strings.shop')</a>
        </h5>
        <nav class="my-2 my-md-0 mr-md-3">
          <a class="p-2 text-dark" href="{{url('/')}}">@lang('strings.home')</a>
        </nav>
        <?php 
    
            $countTag = ($cartCount != 0 ? '<span class="ml-2 badge badge-light">'.$cartCount.'</span>' : '');
            
        ?>        
        <a class="btn btn-primary" href="{{url('/cart')}}">@lang('strings.cart'){!!$countTag!!}</a>
      </div>
      <!-- common menu -->
   
@endsection


