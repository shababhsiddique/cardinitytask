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


  public function index()
  {

    //fetch available products to display
    $products = Product::where('quantity','>',0)->get();


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
