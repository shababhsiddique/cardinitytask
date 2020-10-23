<?php

use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
       //$users = factory(App\Models\Product::class, 50)->create();
       factory(App\Models\Product::class, 50)->create();
    }
}
