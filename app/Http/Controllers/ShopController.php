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
  }


  public function index(Request $request)
  {

    $sessionId =  $request->session()->get('_token');
    Cart::session($sessionId);

    $products = Product::all();

    //Load Component
    $this->layout['content'] = view('pages.home')
      ->with('products', $products);


    //return view
    return view('master', $this->layout);
  }

  
}
