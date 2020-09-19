<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Facades\App\Imports\ProductImport;

class ImportProducts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'product:import';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import products';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $products = ProductImport::getProductsFromFile();

        foreach ($products as $product) {
            $this->line('');
            $this->info('Importing product...');

            $errors = ProductImport::validate($product);

            if ($errors) {
                $this->error('Product import has failed because:');

                foreach ($errors as $error) {
                    $this->error($error);
                }

                continue;
            }

            ProductImport::import($product);
            $this->info('Product has been imported:');

            foreach ($product as $productInfoName => $productInfo) {
                $this->line($productInfoName.': '.$productInfo);
            }

            $this->info('Shopping cart grand total: '.ProductImport::getGrandTotal().' '.ProductImport::getDefaultCurrency());
        }

        $this->line('');
        $this->info('All products have been imported.');
    }
}
