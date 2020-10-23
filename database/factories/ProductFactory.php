<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\Product;
use Faker\Generator as Faker;

$factory->define(Product::class, function (Faker $faker) {

  return [
      'name' => $faker->colorName . " " . $faker->word ,
      'price' => $faker->randomNumber(2),
      'quantity' => $faker->numberBetween($min = 5, $max = 50),
  ];
});
