<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderProducts extends Model
{
    protected $primaryKey = "order_product_id";
    protected $table = 'order_products';

    

    public function product()
    {
        //Return the actual product obj
        return $this->hasOne('App\Models\OrderProducts', 'product_id', 'product_id');
    }
    
    
}
