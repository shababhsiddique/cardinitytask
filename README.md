# About This Project

This simple shopping cart application was built as part of a test for Cardinity online payment gateway. 

Techonoly used
- Laravel 7.26.1
- PHP 7.4
- mySql 5.7

Server
- Homestead 10.0 (Ubuntu 20.04)

## Requirements fulfilled

- Landing page shows product list with add to cart button
- User able to add upto 2 items of each product
- Cart screen display selected products and total amount
- Quantity can be edited on cart
- Product can be removed from cart
- User can proceed to payment and checkout
- Card payment form with 2 stage validation
  - Basic laravel validation pan can be 13 to 19, exp_date cant be lower than now, cvv must be 3-4.
  - Second stage validation pan must be passed by luhn, any format or validation error (incorrect cvv for the brand)
- PAN number brand recognized as typed
- 3D secure authorization used when necessary
- User able to see payment status after transaction end
- Unit test
  - Helper : luhn helper
  - Page: Link, View, Render
  - Action: Checkout behaviour

## Live Demo

   Live demo available at [http://worksample.shababhsiddique.com/](http://worksample.shababhsiddique.com/)  
  
## How to Install

1. Clone project
   ```
   git clone https://github.com/shababhsiddique/cardinitytask.git
   ```

2. Install via composer
   ```
   cd cardinitytask
   composer install
   ```

3. Create .env and fill up your variables using your preferred editor
   ```
   cp .env.example .env
   ```

4. Create your database 

5. Migrate and seed db
   ```
   php artisan migrate --seed
   ```

Done!

I recommend you run some tests before diving in

   ```
   php artisan test
   ```

