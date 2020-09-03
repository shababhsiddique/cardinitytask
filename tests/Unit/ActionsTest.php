<?php

namespace Tests\Unit;


use Tests\TestCase;
use App\Models\Product;
use Darryldecode\Cart\Cart;

class ActionsTest extends TestCase
{


    //sample Credit Card PAN numbers
    private string $testVisaPan = "4111111111111111";
    private string $testMasterCardPan = "5555555555554444";
    private string $test3dsDeclinedPan = "4200000000000000";
    private string $test3dsPassedPan = "5454545454545454";

    /**
     * method to simulate checkout with given product and PAN
     */
    private function checkoutAction(Product $product, string $pan){
         
         \Cart::add(array(
            'id' => $product->product_id,
            'name' => $product->name,
            'price' => $product->price,
            'quantity' => 2,
            'associatedModel' => $product
        ));      

        $response = $this->call('POST', '/cart/pay/submit', [
                '_token' => csrf_token(),
                'holder' => 'Shabab Haider Siddique',
                'pan' => $pan,
                'exp_month' => '12',
                'exp_year' => '3000',
                'cvv' => "123",
            ]
        );

        \Cart::clear();

        return $response;
    }
  
    /**
     * Test cart successful checkout scenario
     */
    public function testSuccess(){    

        $buyableProduct = Product::where('price', '<', 70)->first();

        //test card 1
        $response = $this->checkoutAction($buyableProduct, $this->testVisaPan);
        $response->assertSee("Payment successful");


        //test card 2
        $response = $this->checkoutAction($buyableProduct, $this->testMasterCardPan);
        $response->assertSee("Payment successful");        
       
    }

    /**
     * Test cart declined checkout scenario
     */
    public function testDeclined(){    

        $unBuyableProduct = Product::where('price', '>', 75)->first(); 

        //test card 1
        $response = $this->checkoutAction($unBuyableProduct, $this->testVisaPan);
        $response->assertSee("Declined");


        //test card 2
        $response = $this->checkoutAction($unBuyableProduct, $this->testMasterCardPan);
        $response->assertSee("Declined");        
       
    }

    
    /**
     * Test 3ds pass
     */
    public function test3dsPass(){    

        $buyableProduct = Product::where('price', '<', 70)->first();

        //this card will show the redirect HTML for 3ds validation
        $response = $this->checkoutAction($buyableProduct, $this->test3dsPassedPan);        
        $response->assertSee("3DSecure action required");
        $response->assertSee('<form name="ThreeDForm" method="POST" action="https://acs.cardinity.com">', false);
        $response->assertSee('<input type="hidden" name="PaReq" value="3d-pass" />', false);
       
    }

    /**
     * Test 3ds fail
     */
    public function test3dsFail(){    

        $buyableProduct = Product::where('price', '<', 70)->first();

        //this card will show the redirect HTML for 3ds validation
        $response = $this->checkoutAction($buyableProduct, $this->test3dsDeclinedPan);        
        $response->assertSee("3DSecure action required");
        $response->assertSee('<form name="ThreeDForm" method="POST" action="https://acs.cardinity.com">', false);
        $response->assertSee('<input type="hidden" name="PaReq" value="3d-fail" />', false);
       
    }
}
