<?php

namespace App\Http\Controllers;

use App\Models\OrderProducts;
use App\Models\Product;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Cart;
use Cardinity\Client;
use Cardinity\Method\Payment;
use Validator;
//use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Cache;

class CartController extends Controller
{
    //Layout holder
    private $layout;    


    //build common layout components
    public function __construct()
    {       

        $this->layout['notification'] = view('common.notification');
        $this->layout['menu'] = view("common.menu")->with('cartCount', 0);
    }


    /**
     * Show shopping cart
     */
    public function index()
    {
 
        $items = Cart::getContent();

        //menu item counter
        $count =  Cart::getTotalQuantity();
        $this->layout['menu']->with('cartCount',$count);

        //Load Component
        $this->layout['content'] = view('pages.cart')
            ->with('cartProducts', $items)
            ->with('cartTotal', Cart::getTotal());


        //return view
        return view('master', $this->layout);
    }


    //TODO: add over ajax
    public function add(Request $request, int $productId)
    {

        //Product model
        $product = Product::find($productId);

        //relevant cart item if exist
        $productInCart = Cart::get($product->product_id);
        $itemQtyInCart = ($productInCart == null ? 0 : $productInCart->quantity);


        //dont add if max qty reached or just out of stock
        if ($product->quantity > 0 && $itemQtyInCart < 2) {
            Cart::add(array(
                'id' => $product->product_id,
                'name' => $product->name,
                'price' => $product->price,
                'quantity' => 1,
                'associatedModel' => $product
            ));
            
            $request->session()->put('notification', array(
                'title' => "Added to cart",
                'body'  => "Product $product->name has been added to cart",
                'type'  => "primary"
            ));

        }else{
            
            $request->session()->put('notification', array(
                'title' => "Cannot add product",
                'body'  => "Product $product->name has reached limit",
                'type'  => "info"
            ));

        }

        return redirect('/');
    }

    //TODO: remove over ajax
    public function remove(Request $request, int $productId)
    {
        //remove cart item if exist
        $productInCart = Cart::remove($productId);

        $request->session()->put('notification', array(
            'title' => "Removed",
            'body'  => "Product has been removed from cart",
            'type'  => "primary"
        ));

        return redirect('/cart');
    }


    //TODO: update over ajax
    public function update(Request $request)
    {
        $request->validate([
            'rowId' => 'required',    //atleast 1 row required on cart
        ]);

        $action = $request->input('action');

        //prepare post data        
        $updateItemIds = $request->input('rowId');
        $updateItemQtys = $request->input('quantity');


        //Prepare product models indexed by their id
        $products = Product::whereIn('product_id', $updateItemIds)->get()->keyBy('product_id');

        $numberOfItemsUpdated = 0;
        $numberOfItemsDeleted = 0;

        foreach ($updateItemIds as $index => $id) {

            //relevant cart item if exist
            $productInCart = Cart::get($id);

            //cap the quantity if above 2
            $targetQuantity = ($updateItemQtys[$index] <= 2 ? $updateItemQtys[$index] : 2);
            //cap the quantity if above stock
            $targetQuantity = ($targetQuantity >= $products[$id]->quantity ? $products[$id]->quantity : $targetQuantity);

            if ($targetQuantity == 0) {
                //delete if set qty 0
                Cart::remove($id);
                $numberOfItemsDeleted++;
            }else if($productInCart['quantity'] != $targetQuantity){
                //target quantity needs change
                Cart::update($id, array(
                    'quantity' => array(
                        'relative' => false,
                        'value' => $targetQuantity
                    ),
                ));
                $numberOfItemsUpdated++;
            }
        }

        //came here by checkout
        if($action == "checkout"){
            return redirect('/cart/pay');
        }
        
        if($numberOfItemsUpdated || $numberOfItemsDeleted){

            $mBody = ( $numberOfItemsUpdated ? "$numberOfItemsUpdated rows updated " : "");
            $mBody .= ( $numberOfItemsDeleted ? "$numberOfItemsDeleted rows deleted" : "");

            $request->session()->put('notification', array(
                'title' => "Cart items updated",
                'body'  =>  $mBody,
                'type'  => "primary"
            ));
        }
        

        //came here by update
        return redirect('/cart');
    }


    /**
     * Show payment form
     */
    public function payment()
    {

        $items = Cart::getContent();        

        //menu count
        $count =  Cart::getTotalQuantity();
        $this->layout['menu']->with('cartCount',$count);

        //Load Component
        $this->layout['content'] = view('pages.checkout')
            ->with('cartProducts', $items)
            ->with('cartTotal', Cart::getTotal());

        //return view
        return view('master', $this->layout);
    }


    /**
     * Common operations used in different cases of purchase
     */
    private function cleanupAfterPayment(Request $request){

         //empty cart
         $sessionId =  $request->session()->get('_token');
         Cart::session($sessionId)->clear();
         //Cart::clear();

         //flush and regenrate session
         $request->session()->flush();
         $request->session()->regenerate();         
        
        $request->session()->put('notification', array(
            'title' => "Success",
            'body'  => "Your purchase request is complete",
            'type'  => "success"
        ));
    }
    

    /**
     * Payment form POST action
     */
    public function paymentSubmit(Request $request)
    {
              
        $status = "";
        $outputIcon = ""; 
        $outputMessage = ""; 
        $secure3dObj = [];          
        $mainContent = "";


        //validate general rules
        $request->validate([
            'holder' => 'required|max:32|regex:/^[\pL\s\-]+$/u',   
            'pan'    => 'required|digits_between:13,19',
            'cvv' => 'required|digits_between:3,4',
        ]);

        //secondary validate pan using Luhn algorithm
        $luhnAlgorithmPassed = checkLuhn($request->input('pan'));
        if(!$luhnAlgorithmPassed){
            $request->validate([
                'payment_instrument.pan'    => 'required',
            ]);
        }

        //set validation year limits
        $minYear = date('Y');
        $maxYear = date('Y', strtotime('+1000 years'));
        $request->validate([ 
            'exp_year' => "required|numeric|min:$minYear|max:$maxYear"                        
        ]);

        //set validation month limits
        $minMonth = ($request->input('exp_year') == $minYear ? date('n') : 1);        
        $request->validate([
            'exp_month' => "required|numeric|min:$minMonth|max:12",
        ]);
        //Validation END
        

        
        //uniq string for id
        $uniqString =  (string) ( uniqid() .  md5(uniqid()) );                             
        
        //prepare api client
        $client = Client::create([
            'consumerKey' => env('CARDINITY_KEY'),
            'consumerSecret' => env('CARDINITY_SECRET')
        ]);        

        //Prepare method data for API and TODO: future storage 
        $orderData = [
            'amount' => Cart::getTotal(),
            'currency' => 'EUR',
            'settle' => false,
            'description' => "Test Payment of $uniqString",
            'order_id' => $uniqString,
            'country' => 'LT',
            'payment_method' => Payment\Create::CARD,
            'payment_instrument' => [
                'pan' => $request->input('pan'),
                'exp_year' => (int) $request->input('exp_year'),
                'exp_month' => (int) $request->input('exp_month'),
                'cvc' => $request->input('cvv'),
                'holder' => $request->input('holder'),
            ],
        ];
        $method = new Payment\Create($orderData);    


        //execute payment order method and catch any errors or exceptions
        $exceptionCode= -1;
        $errorFields = [];
        try {

            $payment = $client->call($method);                                             
            $status = $payment->getStatus();


            /**
               * Create Order Start
               */ 
            $order = new Order;
            $order->payment_id = $payment->getId();              
            $order->amount = Cart::getTotal();
            $order->status = "new";
            $order->save();

            //Generate list of product ids ordered.
            $orderProducts = [];
            foreach(Cart::getContent() as $aCartItem){                    

                //reduce quantity 
                //TODO: realtime check if quantity has changed since add to cart;
                $product = Product::find($aCartItem->id);                
                $product->quantity = $product->quantity - $aCartItem->quantity;
                $product->save();
                
                //create and assign order product
                $orderProduct = new OrderProducts;
                $orderProduct->order_id = $order->order_id; 
                $orderProduct->product_id = $aCartItem->id;
                $orderProduct->quantity = $aCartItem->quantity;
                $orderProduct->save();   
            
            }
             /**
             * Create order end
             */

            
            if($status == 'approved') {                

              // Payment is approved
              $outputIcon = "text-success fa fa-check-circle-o";   
              $outputMessage = "Payment successful";   
              $order->status = "approved";
          
              //Common post purchase actions
              $this->cleanupAfterPayment($request);

              $mainContent = view('pages.status')
                ->with('output', $outputMessage)
                ->with('icon', $outputIcon);

            }
        
            if($status == 'pending') {

                // 3D-Secure authorization is required   \
                $outputIcon =  "text-primary fa fa-cog";   
                $outputMessage = "3DSecure action required";   
                $order->status = "pending";

                $url = $payment->getAuthorizationInformation()->getUrl();
                $data = $payment->getAuthorizationInformation()->getData();                

                $paymentId = $payment->getId();

                 /**
                  * Keep order info on cache for 3ds later
                  */
                Cache::put($paymentId, $order->order_id, now()->addMinutes(5)); 
                
                $secure3dObj = [
                    'Url3dSForm' => $payment->getAuthorizationInformation()->getUrl(),
                    'PaReq' => $payment->getAuthorizationInformation()->getData(),
                    'TermUrl' => url('/cart/pay/3dscallback'),                  
                    'identifier' => $paymentId,                  
                ];
                /**
                 * 3ds end
                 */

                $mainContent = view('pages.secure3d')
                    ->with('output', $outputMessage)
                    ->with('icon', $outputIcon)
                    ->with('secure3dObj', $secure3dObj);

            }  
            
            $order->save();
        
        //Catch any possible exception
        } catch (\Cardinity\Exception\ValidationFailed $exception) {            
            $exceptionCode = $exception->getCode();                        
            foreach ($exception->getErrors() as $key => $error) {                
                $errorFields[] =  $error['field'];
            }  
        } catch (\Exception $exception) {            
           $exceptionCode = $exception->getCode();
        }     


        //if there is error, 
        if ($exceptionCode != -1) {

            $exceptionMessage = "Unknown exception occured";   
            $outputIcon =  "text-danger fa fa-exclamation-triangle";
            
            //trigger appropriate validation
            switch ($exceptionCode) { 
                         
                case 0: 
                    
                    //will never come here if we supply the API proper numbers
                    //card number properly checked by luhn
                    //ccv,cvv right digits, year month within limits etc.

                    $notification = [
                        'title' => "Incorrect format Exception code 0",
                        'body'  => "Information provided is not formatted correctly",
                        'type'  => "warning"
                    ];       
                    $request->session()->put('notification', $notification);    

                    //invalid format re render form
                    $request->validate([
                        'exception_0'    => 'required',
                    ]);        
                    break;
                
                case 400:

                    $notification = [
                        'title' => "Invalid Data",
                        'body'  => "Your payment credentials are invalid, please check ".serialize($errorFields),
                        'type'  => "warning"
                    ];       
                    $request->session()->put('notification', $notification);    

                    //invalid data re render form
                    foreach($errorFields as $anErrorField){
                        if($anErrorField == "{CARD_BRAND}"){
                            $request->validate([
                                "payment_instrument.card_brand"  => 'required',
                            ]);
                        }elseif($anErrorField == 'payment_instrument.cvc'){
                            $request->validate([
                                'payment_instrument.cvc' => 'required',
                            ]);
                        }else{                                      
                            $request->validate([
                                'payment_instrument.fields'    => 'required',
                            ]);
                        }                                                
                    }             
                    break;
            
                case 401:
                    //Unauthorized API
                    $notification = [
                        'title' => "Gateway API not authorized",
                        'body'  => "Incorrect API key or secret",
                        'type'  => "danger"
                    ];       
                    $request->session()->put('notification', $notification);                
                    break;

                case 402:
                    //Declined
                    $notification = [
                        'title' => "Declined",
                        'body'  => "Transaction request was valid but declined",
                        'type'  => "warning"
                    ];       
                    $request->session()->put('notification', $notification); 
                    $exceptionMessage = "Declined";   
                    $outputIcon = "text-warning fa fa-exclamation-triangle";   
                    break;
                
                case 403:
                case 404:
                case 405:
                case 406:
                    //Notfound/Notccessible
                    $notification = [
                        'title' => "Unable to process",
                        'body'  => "The request you wanted to access is either forbidden or not found",
                        'type'  => "warning"
                    ];       
                    $request->session()->put('notification', $notification);    
                    break;

                case 500:
                case 503:
                    //Service offline or error
                    $notification = [
                        'title' => "Unable to Connect",
                        'body'  => "Unable to reach API gateway",
                        'type'  => "warning"
                    ];       
                    $request->session()->put('notification', $notification);    
                    break;
                    
                default:  
                    $notification = [
                        'title' => "Unknown error occured",
                        'body'  => "Unable to complete transaction",
                        'type'  => "warning"
                    ];       
                    $request->session()->put('notification', $notification);                     
                    break;
            }               
            
            $mainContent = view('pages.status')
                ->with('output', $exceptionMessage)
                ->with('icon',$outputIcon);
        }


        //load component
        $this->layout['content'] = $mainContent;

        //return view
        return view('master', $this->layout);
    }



    /**
     * api callback POST url
     */
    public function paymentCallback(Request $request){

        //validate callback from API
        $request->validate([
            "MD"  => 'required',
            "PaRes" => 'required',
        ]);  


        $message = "";
        $exceptionCode = 0;
        $notification = [];
        $paymentId = $request->input("MD");

        //get payment identifier from Cache, 
        //session cookie unavailable since we are here from a redirect by cardinity API              
        if (Cache::has($paymentId)) {

            $orderId = Cache::pull($paymentId); 
            $PaRes = $request->input("PaRes");
            $order = Order::find($orderId);
            
            //this is usual callback from cardinity API
            $client = Client::create([
                'consumerKey' => env('CARDINITY_KEY'),
                'consumerSecret' => env('CARDINITY_SECRET')
            ]);   

            //finalize payment 
            $method = new Payment\Finalize($paymentId, $PaRes ); 
            
            
            try {      
                
                $client->call($method);

                //finalize success time to update status                
                $order->status = "approved";                

                //common post purchase operations
                $this->cleanupAfterPayment($request);  
                
                $outputIcon = "text-success fa fa-check-circle-o";   
                $message = "Transaction successful";
                $notification = [
                    'title' => $message,
                    'body'  =>  "Purchase transaction secured",
                    'type'  => "success"
                ];                

            } catch (\Exception $exception) {     
                
                $order->status = "failed";
                  
                $exceptionCode =  $exception->getCode();     
                switch ($exceptionCode) {
                    
                    case 402:
                        $message = "Your request was valid but it was declined.";
                        break;
                    
                    default:
                        $message = $exception->getMessage();
                        break;
                }   
                
                $outputIcon = "text-warning fa fa-exclamation-triangle";   
                $notification = [
                    'title' => "Exception occured",
                    'body'  =>  $message,
                    'type'  => "warning"
                ];               
          
            }
            
            //whataver status we are in, save
            $order->save();
            
        }else{
            $outputIcon = "text-danger fa fa-exclamation-triangle";   
            $notification = [
                'title' => "Something went wrong",
                'body'  => "Payment ID mismatch or expired",
                'type'  => "danger"
            ];          
        }

        $request->session()->put('notification', $notification);     
        //return redirect('/');


        //Load Component
        $this->layout['content'] = view('pages.status')
           ->with('output', $message)
           ->with('icon',$outputIcon);


       //return view
       return view('master', $this->layout);
        
    }


    
}

