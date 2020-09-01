<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class ShopController extends Controller
{
  //Layout holder
  private $layout;

  public function index()
  {

    $products = Product::all();

    //Load Component
    $this->layout['content'] = view('pages.home')
      ->with('products', $products);


    //return view
    return view('master', $this->layout);
  }

  public function cart()
  {

    $products = Product::all()->take(5);

    //Load Component
    $this->layout['content'] = view('pages.cart')
      ->with('products', $products);


    //return view
    return view('master', $this->layout);
  }
}
