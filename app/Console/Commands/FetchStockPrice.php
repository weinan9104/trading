<?php

namespace App\Console\Commands;

use App\Models\Trade;
use GuzzleHttp\Client;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class FetchStockPrice extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:fetch-stock-price';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
		$apiKey = 'rVepuhvI6BzfIXsCa6P3JdygCmXAYL7p';
		$apiDomain = 'https://financialmodelingprep.com/api/v3';

		$openStockTrades = Trade::where('trade_type', 'stock')->where('trade_status','open')->whereNull('exit_price')->whereNull('exit_date')->get();
		$openOptionsTrades = Trade::where('trade_type', 'option')->where('trade_status','open')->whereNull('exit_price')->whereNull('exit_date')->get();
		
		Log::info("Stock Price Fetch Started...");

		foreach ($openStockTrades as $stockTrade) {

			$url_info = "$apiDomain/profile/$stockTrade->trade_symbol?apikey=$apiKey";
			
			$http = new Client();
			$response = $http->request('GET', $url_info);
			$company = json_decode($response->getBody());
			if($company ?? false) {
				$company = $company[0];
	
				$stockTrade->current_price = $company->price;
				$stockTrade->symbol_image = $company->image;
				$stockTrade->timestamps=false;
				$stockTrade->save();
			}
			sleep(1);
		}

		foreach ($openOptionsTrades as $optionTrade) {
			
			$url_info = "$apiDomain/profile/$optionTrade->trade_symbol?apikey=$apiKey";
			
			$http = new Client();
			$response = $http->request('GET', $url_info);
			$company = json_decode($response->getBody());

			if($company ?? false) {
				$company = $company[0];
				$optionTrade->current_price = $company->price;
				$optionTrade->symbol_image = $company->image;
				$optionTrade->timestamps=false;
				$optionTrade->save();
			}
			sleep(1); 
		}

		Log::info("Stock Price Fetched Successfully...");
    }
}
