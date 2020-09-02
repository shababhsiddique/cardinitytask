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
     

        //validate for name and pan
        $request->validate([
            'holder' => 'required|max:32|regex:/^[\pL\s\-]+$/u',   
            'pan'    => 'required|digits:16',
        ]);

        $luhnAlgorithmPassed = checkLuhn($request->input('pan'));
        if(!$luhnAlgorithmPassed){
            $request->validate([
                'incorrect_pan'    => 'required',
            ]);
        }


        //set validation year limits
        $minYear = date('Y');
        $maxYear = date('Y', strtotime('+50 years'));
        $request->validate([ 
            'exp_year' => "required|numeric|min:$minYear|max:$maxYear"                        
        ]);


        //set validation month limits
        $minMonth = ($request->input('exp_year') == $minYear ? date('n') : 1);        
        $request->validate([
            'exp_month' => "required|numeric|min:$minMonth|max:12",
        ]);


        //set validation for cvv
        $cardtype = getCardType($request->input("pan"));
        if($cardtype == "American Express"){
            $request->validate([
                'cvv' => 'required|digits_between:3,4',
            ]);
        }else{
            $request->validate([
                'cvv' => 'required|digits:3',
            ]);
        }

        
        
        
        //uniq string for id
        $uniqString = strval(time());


        //Prepare order data for API and TODO future storage 
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

        

        //prepare api client
        $client = Client::create([
            'consumerKey' => 'test_jhcm1kuiowcs2s9dj03vryr4v8yf4e',
            'consumerSecret' => 'uczqtwmhh2dj1m2vkulspssqisqc2qzjo8v23auqssux4opvag',
        ]);        


        $method = new Payment\Create($orderData);        


        try {

            //execute payment order method
            $payment = $client->call($method);
            $status = $payment->getStatus();

            
            
            if($status == 'approved') {
              // Payment is approved
              $outputMessage = "Payment has been approved";
            }
        
            if($status == 'pending') {
              // Retrieve information for 3D-Secure authorization
              $url = $payment->getAuthorizationInformation()->getUrl();
              $data = $payment->getAuthorizationInformation()->getData();

              $outputMessage = "Payment is pending ".$url. " " .$data;
            }

            
        
        } catch (Exception\Declined $exception) {
            
            $errors = $exception->getErrors(); // list of errors occured
            $outputMessage = "Transaction declined, ".serialize($errors);

        } catch (Exception\ValidationFailed $exception) {
            
            $errors = $exception->getErrors(); // list of errors occured
            $outputMessage = "Validation failed, ".serialize($errors);

        } catch (Exception $exception) {            
            
            $errors = $exception->getErrors(); // list of errors occured
            $outputMessage = "Transaction failed, ".serialize($errors);

        }


        


        //Load Component
        $this->layout['content'] = view('pages.confirm')
            ->with('output', $outputMessage);

        //return view
        return view('master', $this->layout);
    }


    
}
