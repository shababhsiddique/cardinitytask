<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Cart;
use Cardinity\Client;
use Cardinity\Method\Payment;
use Validator;

class CartController extends Controller
{
    //Layout holder
    private $layout;
    private $sessionId;


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


        return redirect('/');
    }

    //TODO: remove over ajax
    public function remove(Request $request, int $productId)
    {
        //remove cart item if exist
        $productInCart = Cart::remove($productId);

        return redirect('/cart');
    }


    //TODO: update over ajax
    public function update(Request $request)
    {
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

    
    public function paymentSubmit(Request $request)
    {

        $outputMessage = "";            

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
        $maxYear = date('Y', strtotime('+100 years'));
        $request->validate([ 
            'exp_year' => "required|numeric|min:$minYear|max:$maxYear"                        
        ]);

        //set validation month limits
        $minMonth = ($request->input('exp_year') == $minYear ? date('n') : 1);        
        $request->validate([
            'exp_month' => "required|numeric|min:$minMonth|max:12",
        ]);
        
        //uniq string for id
        $uniqString = strval(time());
        
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
            }
        
            if($status == 'pending') {
              // 3D-Secure authorization is required              
              $url = $payment->getAuthorizationInformation()->getUrl();
              $data = $payment->getAuthorizationInformation()->getData();

              $outputMessage = "Payment is pending ".$url. " " .$data;
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

            echo  $exception->getCode();
             
            
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
                        }else{                            
                            $request->validate([
                                $anErrorField    => 'required',
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
        }


        //Load Component
        $this->layout['content'] = view('pages.confirm')
            ->with('output', $outputMessage);

        //return view
        return view('master', $this->layout);
    }


    
}
