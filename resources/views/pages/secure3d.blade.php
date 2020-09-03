@extends('master')

@section('content')
    <div class=" col-md-5 col-sm-10 mx-auto" >

        <h1 class="my-5 text-center">@lang('strings.checkout.response')</h1>

        <div class="card shadow">               
            <div class="card-header">
                <strong>@lang('strings.status')</strong>
            </div>         
            <div class="card-body text-center">   
                    <i class="{{$icon}} fa-4x"></i>
                    <h5>{{$output}}</h5>          
                    @if($secure3dObj)
                    <h4>@lang('strings.3ds.redirect')</h4>
                    <?php 
                    echo "Payment ID .$secure3dObj[identifier]";
                    ?>
                        <form name="ThreeDForm" method="POST" action="{{$secure3dObj['Url3dSForm']}}">
                            @lang('strings.3ds.redirect_fail') <button class="btn btn-link" type=submit>@lang('strings.click_here')</button>
                            <input type="hidden" name="PaReq" value="{{$secure3dObj['PaReq']}}" />
                            <input type="hidden" name="TermUrl" value="{{$secure3dObj['TermUrl']}}" />
                            <input type="hidden" name="MD" value="{{$secure3dObj['identifier']}}" />
                        </form>
                    @endif
            </div>
            <div class="card-footer">
                @if(!$secure3dObj)
                    <a href="{{url('/')}}" class="btn btn-primary float-right">@lang('strings.checkout.back') <i class="fa fa-cart pl-3"></i></a>
                @endif
            </div>            
        </div>
    </div>
@endsection

@push('footer-scripts')
    @if($secure3dObj)
        <script type="text/javascript">

            // Make the form post after 2 min
            $(document).ready(function() {    
                window.setTimeout(function() {
                    document.ThreeDForm.submit();
                }, 2000);
            });     

        </script>        
    @endif
@endpush