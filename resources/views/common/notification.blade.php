@extends('master')

@section('notification')
    
    <?php        
    $notification = Session::get('notification');        
    if (isset($notification) && !empty($notification)) {
        ?>

        <div class="col-md-10 col-sm-12 mx-auto">
            <!-- Simple notifications style -->
            <div class="alert alert-{{$notification["type"]}} alert-dismissible fade show" role="alert">
                <strong>{{ $notification['title'] }}</strong>
                <small>{{ $notification['body']}}</small>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <!-- Notifications style -->         
        </div>

        <?php
        Session::forget('notification');
    }
    ?>
   
@endsection


