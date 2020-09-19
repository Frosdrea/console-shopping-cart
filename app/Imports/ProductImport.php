<?php

namespace App\Imports;

use File;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Session;

class ProductImport
{
    public function getCurrencies() {

        return [
            'EUR' => 1,
            'USD' => 1.14,
            'GBP' => 0.88,
        ];
    }

    public function getDefaultCurrency() {

        return 'EUR';
    }

    public function getProductsFromFile()
    {
        $products = File::get(public_path('products.txt'));
        $products = explode(PHP_EOL, $products);
        $products = array_map(function ($productLine) {
            return collect(explode(';', $productLine))
                ->mapWithKeys(function ($info, $key) {
                    switch ($key) {
                        case 0:
                            return ['identifier' => $info];
                        case 1:
                            return ['name' => $info];
                        case 2:
                            return ['quantity' => $info];
                        case 3:
                            return ['price' => $info];
                        case 4:
                            return ['currency' => $info];
                    }
                })
                ->all();
        }, $products);

        return $products;
    }

    public function validate($product) {

        $validator = Validator::make($product, [
            'identifier' => ['required', 'max:10'],
            'name' => ['required', 'max:255'],
            'quantity' => ['required', 'integer'],
            'price' => ['required', 'numeric', 'min:0.01'],
            'currency' => ['required', Rule::in(array_keys($this->getCurrencies()))],
        ]);
        $errors = $validator->errors()->all();

        if (!$errors) {
            $errors = $this->validateMismatch($product);
        }

        return $errors;
    }

    protected function validateMismatch($product) {

        $errors = [];
        $allProducts = collect(Session::get('products', []));
        $alreadyExistingProduct = $allProducts->get($product['identifier']);

        if (!$alreadyExistingProduct) {
            return $errors;
        }

        $nameMismatch = $alreadyExistingProduct['name'] != $product['name'];
        $priceMismatch = $alreadyExistingProduct['price'] != $this->getPriceInDefaultCurrency($product);

        if ($nameMismatch) {
            $errors[] = 'Same product is already imported but with different name.';
        }

        if ($priceMismatch) {
            $errors[] = 'Same product is already imported but with different price.';
        }

        return $errors;
    }

    public function import($product) {

        $allProducts = collect(Session::get('products', []));
        $alreadyExistingProduct = $allProducts->pull($product['identifier']);

        if ($alreadyExistingProduct) {
            $newQuantity = $alreadyExistingProduct['quantity'] + $product['quantity'];

            if ($newQuantity > 0) {
                $alreadyExistingProduct['quantity'] = $newQuantity;
                $alreadyExistingProduct['currency'] = $this->getDefaultCurrency();
                $productToImport = $alreadyExistingProduct;
            }
        } elseif ($product['quantity'] > 0) {
            $productToImport = $product;
        }

        if (isset($productToImport)) {
            $productToImport['price'] = $this->getPriceInDefaultCurrency($productToImport);
            unset($productToImport['currency']);
            $allProducts->put($productToImport['identifier'], $productToImport);
        }

        Session::put('products', $allProducts->all());
    }

    protected function getPriceInDefaultCurrency($product) {

        $currencyRatio = $this->getCurrencies()[$product['currency']];
        $price = ($product['price'] * 100) / ($currencyRatio * 100);
        $price = round($price, 2);

        return $price;
    }

    public function getGrandTotal() {

        return collect(Session::get('products', []))
            ->sum(function ($product) {
                return $product['price'] * $product['quantity'];
            });
    }
}
