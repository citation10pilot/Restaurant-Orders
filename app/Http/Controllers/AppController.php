<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App;
use Input;
use Config;
use Redirect;
use App\Http\Requests;
use App\Http\Controllers\Controller;

class AppController extends Controller {
		
	public function shopifyAuth() {
		$shopDomain = Input::get('shop');	
	    if (isset($shopDomain)) {
		    $shop = App\Shop::where('url', $shopDomain)->count();	
			if ($shop < 1) {
				$shopifyClient = App::make('ShopifyAPI', [
					'API_KEY'		=> Config::get('shopify.APP_KEY'),
					'API_SECRET'	=> Config::get('shopify.APP_SECRET'),
					'SHOP_DOMAIN'	=> $shopDomain,
					'ACCESS_TOKEN'	=> ''
				]);
				
				return Redirect::to($shopifyClient->installURL(['permissions' => Config::get('shopify.APP_SCOPE'), 'redirect' => Config::get('app.url').'/connect']));
			} else {
				echo $shopDomain.' Already exists, please login from your Shopify Admin';
			}
		}
	}
	
	public function connect() {
		$code = Input::get('code');
		$shopDomain = Input::get('shop');	
		if (isset($code) && isset($shopDomain)) {
			$shopifyClient = App::make('ShopifyAPI', [
				'API_KEY'		=> Config::get('shopify.APP_KEY'),
				'API_SECRET'	=> Config::get('shopify.APP_SECRET'),
				'SHOP_DOMAIN'	=> $shopDomain,
				'ACCESS_TOKEN'	=> ''
			]);

			try	{
			    $accessToken = $shopifyClient->getAccessToken($code);
				$shopifyClient->setup(['ACCESS_TOKEN' => $accessToken]);
			    $shop = new App\Shop;
			    $shop->url = $shopDomain;
				$shop->token = $accessToken;
			    $shop->save();
				try {
					$call = $shopifyClient->call([
						'URL' => 'webhooks.json', 
						'METHOD' => 'POST', 
						'DATA' => [
							"topic" => "orders\/create",
						    "address" => "http:\/\/whatever.hostname.com\/",
						    "format" => "json"
					    ]
					]);
				} catch (Exception $e) {
					$call = $e->getMessage();
				}
			} catch (Exception $e) {
			    echo '<pre>Error: ' . $e->getMessage() . '</pre>';
			}
			// return Redirect::to();
		}
	}
	
	public function setup() {
		
	}
	
    
}
