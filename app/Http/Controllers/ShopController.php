<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Cart;

class ShopController extends Controller
{
  //Layout holder
  private $layout;
  private $sessionId;


  //build common layout components
  public function __construct()
  {
    $this->layout['notification'] = view('common.notification');  
    $this->layout['menu'] = view("common.menu");
  }


  public function index(Request $request)
  {

    //keep track of cart by browser session
    /*$cartKey = $request->session()->get('cart_key', function () {
        return session('_token');
    });
    
    Cart::clear();    
    Cart::session($cartKey)->clear();  */

    $products = Product::all();


    //menu count
    $count =  Cart::getTotalQuantity();
    $this->layout['menu']->with('cartCount',$count);
    
    //Load Component
    $this->layout['content'] = view('pages.home')
      ->with('products', $products);


    //return view
    return view('master', $this->layout);
  }

  
}
