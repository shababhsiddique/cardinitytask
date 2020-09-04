<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $primaryKey = "order_id";
    protected $table = 'orders';


    public function products()
    {
        //Each order has many products
        return $this->hasMany('App\Models\OrderProducts', 'order_id', 'order_id');
    }
    
}
