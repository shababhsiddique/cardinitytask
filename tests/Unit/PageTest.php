<?php

namespace Tests\Unit;


//use Illuminate\Foundation\Testing\RefreshDatabase;
//use Illuminate\Foundation\Testing\WithoutMiddleware;
use Tests\TestCase;
use App\Models\Product;
use Darryldecode\Cart\Cart;

class PageTest extends TestCase
{

    /**
     * Test main pages exist
     */
    public function testGetPages()
    {
        $response = $this->get('/');
        $response->assertStatus(200);

        $response = $this->get('/cart');
        $response->assertStatus(200);
    }

    /**
     * Check cart add remove and update links
     */
    public function testCartAddRemove()
    {
        $response = $this->get('/cart/add/1');
        $response->assertStatus(302);

        $response = $this->get('/cart/remove/1');
        $response->assertStatus(302);

        $response = $this->post('/cart/update');
        $response->assertStatus(302);
    }


    /**
     * Test shop page render
     */
    public function testShopPageRender()
    {

        $response = $this->call('GET', '/');

        $products = Product::all()->random(10);

        foreach ($products as $product) {
            $response->assertSee($product->name);
            $response->assertSee($product->quantity . " " . __('strings.shop.stock'));
            $response->assertSee(number_format($product->price, 2) . " " . __("strings.currency"));
        }
    }

    /**
     * Test cart page render
     */
    public function testCartPageRender()
    {

        $products = Product::all()->random(5);

        foreach ($products as $product) {
            \Cart::add(array(
                'id' => $product->product_id,
                'name' => $product->name,
                'price' => $product->price,
                'quantity' => 2,
                'associatedModel' => $product
            ));
        }

        $response = $this->call('GET', '/cart');

        \Cart::clear();

        foreach ($products as $product) {
            $response->assertSee($product->name);
            $response->assertSee(number_format($product->price, 0));
            $response->assertSee(number_format($product->price * 2, 0));
        }
    }


    /**
     * Test checkout page render
     */
    public function testCheckoutRender()
    {
        $products = Product::all()->random(5);

        foreach ($products as $product) {
            \Cart::add(array(
                'id' => $product->product_id,
                'name' => $product->name,
                'price' => $product->price,
                'quantity' => 2,
                'associatedModel' => $product
            ));
        }

        $response = $this->call('GET', '/cart');

        \Cart::clear();

        foreach ($products as $product) {
            $response->assertSee($product->name);
            $response->assertSee(number_format($product->price, 0));
            $response->assertSee(number_format($product->price * 2, 0));;
        }
    }
}
