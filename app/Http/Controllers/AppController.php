<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App;
use Input;
use App\Http\Requests;
use App\Http\Controllers\Controller;

class AppController extends Controller {
		
	public function shopifyAuth() {
		$shopDomain = Input::get('shop');	
	    if (isset($shopDomain)) {
		    $shop = App\Shop::where('url', $shopDomain)->count();	
			if ($shop < 1) {
				$shopifyClient = App::make('ShopifyAPI', [
					'API_KEY'		=> \Config::get('shopify.APP_KEY'),
					'API_SECRET'	=> \Config::get('shopify.APP_SECRET'),
					'SHOP_DOMAIN'	=> $shopDomain,
					'ACCESS_TOKEN'	=> ''
				]);
				
				return \Redirect::to($shopifyClient->installURL(['permissions' => \Config::get('shopify.APP_SCOPE'), 'redirect' => \Config::get('app.url').'/connect']));
			} else {
				echo $shopDomain.' Already exists, please login from your Shopify Admin';
			}
		}
	}
	
	public function connect(Request $request) {
		$code = Input::get('code');
		$shopDomain = Input::get('shop');	
		if (isset($code) && isset($shopDomain)) {
			$shopifyClient = App::make('ShopifyAPI', [
				'API_KEY'		=> \Config::get('shopify.APP_KEY'),
				'API_SECRET'	=> \Config::get('shopify.APP_SECRET'),
				'SHOP_DOMAIN'	=> $shopDomain,
				'ACCESS_TOKEN'	=> ''
			]);

			try	{
			    $accessToken = $shopifyClient->getAccessToken($code);
				$shopifyClient->setup(['ACCESS_TOKEN' => $accessToken]);
				
				$request->session()->put('shopify_token', $accessToken);
			    $request->session()->put('shop_domain', $shopDomain);
			    
			    $shop = new App\Shop;
			    $shop->url = $shopDomain;
				$shop->token = $accessToken;
			    $shop->save();
			} catch (Exception $e) {
			    echo '<pre>Error: ' . $e->getMessage() . '</pre>';
			}
			return \Redirect::to('setup');
		}
	}
	
	public function setup(Request $request) {
		$data = new \stdClass();
		$data->webhook = new \stdClass();
		$data->webhook->topic = 'orders/create';
		$data->webhook->address = 'http://foodmenupro.com/api/order';
		$data->webhook->format = 'json';
		
		$token = $request->session()->get('shopify_token');
		$domain = $request->session()->get('shop_domain');
		
		$shopifyClient = App::make('ShopifyAPI', [
			'API_KEY'		=> \Config::get('shopify.APP_KEY'),
			'API_SECRET'	=> \Config::get('shopify.APP_SECRET'),
			'SHOP_DOMAIN'	=> $domain,
			'ACCESS_TOKEN'	=> $token
		]);
		
		try {
			$call = $shopifyClient->call([
				'URL' => 'webhooks.json', 
				'METHOD' => 'GET',
				'ALLDATA' => 1
			]);
			foreach($call->webhooks as $hook) {
				if ( strpos($hook->address, 'foodmenupro.com') ) {
					$setup = true;
/*
					$call = $shopifyClient->call([
						'URL' => 'webhooks/'.$hook->id.'.json', 
						'METHOD' => 'DELETE',
						'ALLDATA' => 1
					]);
*/
					echo '<pre>';
					print_r($hook);
					echo '</pre>';
					break;
				}
			}
		} catch (Exception $e) {
			echo $e->getMessage();
		}
		if (!$setup) {
			try {
				$call = $shopifyClient->call([
					'URL' => 'webhooks.json', 
					'METHOD' => 'POST', 
					'ALLDATA' => 1,
					'DATA' => json_encode($data)
				]);
			} catch (Exception $e) {
				echo $e->getMessage();
			}
		}
	}
	
    
}
