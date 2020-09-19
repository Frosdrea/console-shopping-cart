# Console Shopping Cart

## About

Shopping cart where user can add/remove products using txt file. Only in console mode.
Supported currencies `EUR`, `USD`, `GBP`
Default currency `EUR`

## Used technologies

Laravel 8.5.0

## Setup guide

### Clone repo

### Change to directory

````
cd console-shopping-cart

````   
### Install dependencies

````
composer install
````

### Copy .env file

```
cp .env.example .env
```

### Generate application key:

````
php artisan key:generate
````

## Usage examples

Enter all products into `products.txt` file in public folder. New product in each line. Product has 5 columns:

1. Unique product identifier
2. Product name
3. Product quantity
4. Product price
5. Product's price currency

Columns are separated by `;` character.

Launch console command
````
php artisan product:import
````