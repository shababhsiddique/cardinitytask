<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Cart;
use Cardinity\Client;
use Cardinity\Method\Payment;
use Validator;
use Illuminate\Support\Facades\Redis;

class CartController extends Controller
{
    //Layout holder
    private $layout;    
    private $redis;


    //build common layout components
    public function __construct()
    {
        $this->layout['notification'] = view('common.notification');
        $this->redis = new Redis();
    }


    public function index()
    {

        $items = Cart::getContent();
        
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
        }

        $request->session()->put('notification', array(
            'title' => "Added to cart",
            'body'  => "Product $product->name has been added to cart",
            'type'  => "primary"
        ));

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


        foreach ($updateItemIds as $index => $id) {

            //relevant cart item if exist
            $productInCart = Cart::get($id);

            //cap the quantity if above 2
            $targetQuantity = ($updateItemQtys[$index] <= 2 ? $updateItemQtys[$index] : 2);
            //cap the quantity if above stock
            $targetQuantity = ($targetQuantity >= $products[$id]->quantity ? $products[$id]->quantity : $targetQuantity);


            //delete if set qty 0
            if ($targetQuantity == 0) {
                Cart::remove($id);
            }
            //dont update if out of stock
            else{
                Cart::update($id, array(
                    'quantity' => array(
                        'relative' => false,
                        'value' => $targetQuantity
                    ),
                ));
            }
        }


        if($action == "checkout"){
            return redirect('/cart/pay');
        }

        
        $request->session()->put('notification', array(
            'title' => "Cart items updated",
            'body'  => "Items quantities updated on cart",
            'type'  => "primary"
        ));

        return redirect('/cart');
    }


    public function payment()
    {

        $items = Cart::getContent();

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
    

    public function paymentSubmit(Request $request)
    {

              
        $status = "";
        $outputMessage = ""; 
        $secure3dObj = [];  
        
        $mainContent = "";


        //validate general rules
        $request->validate([
            'holder' => 'required|max:32|regex:/^[\pL\s\-]+$/u',   
            'pan'    => 'required|digits:16',
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
        $errors = [];
        $exceptionCode= 0;
        $errorFields = [];
        try {

            $payment = $client->call($method);                                             
            $status = $payment->getStatus();

            if($status == 'approved') {
              // Payment is approved
              $outputMessage = "Payment has been approved";


              //Common post purchase actions
              $this->cleanupAfterPayment($request);

              $mainContent = view('pages.status')
                ->with('output', $outputMessage);

            }
        
            if($status == 'pending') {

                // 3D-Secure authorization is required              
                $url = $payment->getAuthorizationInformation()->getUrl();
                $data = $payment->getAuthorizationInformation()->getData();

                $outputMessage = "Payment is pending, additional action required";

                $paymentId = $payment->getId();
                Redis::set('payment_id', $paymentId);

                $secure3dObj = [
                    'Url3dSForm' => $payment->getAuthorizationInformation()->getUrl(),
                    'PaReq' => $payment->getAuthorizationInformation()->getData(),
                    'TermUrl' => url('/cart/pay/3dscallback'),                  
                    'identifier' => $paymentId,                  
                ];

                $mainContent = view('pages.secure3d')
                    ->with('output', $outputMessage)
                    ->with('secure3dObj', $secure3dObj);

            }    
            
            
        
        } catch (\Cardinity\Exception\InvalidAttributeValue $exception) {
            foreach ($exception->getViolations() as $key => $violation) {
                array_push($errors, $violation->getPropertyPath() . ' ' . $violation->getMessage());
            }
            $exceptionCode = $exception->getCode();            
        } catch (\Cardinity\Exception\ValidationFailed $exception) {
            foreach ($exception->getErrors() as $key => $error) {                
                $errorFields[] =  $error['field'];
                $errors[] =  $error['message'];       
            }          

            $exceptionCode = $exception->getCode();
        } catch (\Cardinity\Exception\Declined $exception) {
            foreach ($exception->getErrors() as $key => $error) {
                array_push($errors, $error['message']);
            }
            $exceptionCode = $exception->getCode();
        } catch (\Cardinity\Exception\NotFound $exception) {
            foreach ($exception->getErrors() as $key => $error) {
                array_push($errors, $error['message']);
            }
            $exceptionCode = $exception->getCode();
        } catch (\Exception $exception) {
            foreach ($exception->getErrors() as $key => $error) {
                array_push($errors, $error['message']);
                
            }
            $exceptionCode = $exception->getCode();                                
            
        }       

        //if there is error, 
        if ($exceptionCode != 0) {
            //trigger appropriate validation
            switch ($exceptionCode) {                
                case 400:
                    //invalid format                     
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
                    //Unauthorized    
                    $request->validate([
                        "payment_instrument.unauthorized"  => 'required',
                    ]);
                    break;

                case 402:
                    //Declined
                    $request->validate([
                          "payment_instrument.declined"  => 'required',
                    ]);
                    break;

                case 500:
                    //Server error
                    $request->validate([
                       "payment_instrument.offline"  => 'required',
                    ]);    
                    break;

                case 503:
                    //Service offline
                    $request->validate([
                        "payment_instrument.offline"  => 'required',
                    ]);    
                    break;
                    
                default:                    
                    break;
            }   
            
            
            $mainContent = view('pages.status')
                ->with('output', "Unknown Exception occured");
        }
        
        

        //Load Component
       /* $this->layout['content'] = view('pages.status')
            ->with('output', $outputMessage)
            ->with('secure3dObj', $secure3dObj);*/


        //load component
        $this->layout['content'] = $mainContent;

        //return view
        return view('master', $this->layout);
    }



    public function paymentCallback(Request $request){

        //validate callback from API
        $request->validate([
            "MD"  => 'required',
            "PaRes" => 'required',
        ]);  


        $message = "";
        $exceptionCode = 0;
        $notification = [];

        //get session identifier from Redis, 
        //session cookie unavailable since we are here from a redirect by cardinity API              
        $paymentId = Redis::get("payment_id");        
        
        if($paymentId == $request->input("MD")){
            
            $PaRes = $request->input("PaRes");

            //this is usual callback from cardinity API
            $client = Client::create([
                'consumerKey' => env('CARDINITY_KEY'),
                'consumerSecret' => env('CARDINITY_SECRET')
            ]);   

            //finalize payment 
            $method = new Payment\Finalize($paymentId, $PaRes );            
            
            try {               
                
                $payment = $client->call($method);

                //common post purchase operations
                $this->cleanupAfterPayment($request);  
                
                $message = "Transaction successful";
                $notification = [
                    'title' => $message,
                    'body'  =>  "Purchase transaction secured",
                    'type'  => "success"
                ];                

            } catch (\Exception $exception) {    

                //TODO
                $exceptionCode =  $exception->getCode();     
                switch ($exceptionCode) {
                    
                    case 402:
                        $message = "Your request was valid but it was declined.";
                        break;
                    
                    default:
                        $message = $exception->getMessage();
                        break;
                }   
                
                $notification = [
                    'title' => "Exception occured",
                    'body'  =>  $message,
                    'type'  => "danger"
                ];
                

          
            }   
            
        }else{
            $notification = [
                'title' => "Something went wrong",
                'body'  => "Payment ID mismatch",
                'type'  => "danger"
            ];

          
        }

        $request->session()->put('notification', $notification);        

        //return redirect('/');


        //Load Component
        $this->layout['content'] = view('pages.status')
           ->with('output', $message);


       //return view
       return view('master', $this->layout);
        
    }


    
}

