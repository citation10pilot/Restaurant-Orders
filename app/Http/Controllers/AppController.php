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
	public function artOrders() {
		$token = 'b4fc4f0fa3061a52752fa632bb64b95d';
		$domain = 'artnaturals.myshopify.com';
		$fromClient = App::make('ShopifyAPI', [
			'API_KEY'		=> 'bd2cd87a456a4c225ca687a30578e448',
			'API_SECRET'	=> '50617402d44e2d3e5ce32f21a07ce9a5',
			'SHOP_DOMAIN'	=> $domain,
			'ACCESS_TOKEN'	=> $token
		]);
		$toClient = App::make('ShopifyAPI', [
			'API_KEY'		=> '94ad8b3d856d05fdf1ac0a4a684efa2a',
			'API_SECRET'	=> '12ef6c2f99b32f3518dbaa15f68dd0e3',
			'SHOP_DOMAIN'	=> 'art-naturals.myshopify.com',
			'ACCESS_TOKEN'	=> 'd700770c1b60d8ca53aa6214a6d92de5'
		]);
 
		$page = 0;
		$count = 250;
		while ( $count >= 250) {
			try {
				$page ++;
				$call = $fromClient->call(['URL' => 'orders.json', 'METHOD' => 'GET', 'DATA' => ['limit' => 250, 'page' => $page, 'published_status' => 'any']]);
				$count = $call->orders;
				foreach ($call->orders as $order) {
					$data = new \stdClass();
					$data->order = $order;
					echo $order->number.'<br>';
					$call = $toClient->call(['URL' => 'orders.json', 'METHOD' => 'POST', 'ALLDATA' => 1, 'DATA' => json_encode($data)]);	
				}
				break;
			} catch (Exception $e) {
				echo $e->getMessage();
				break;
			}
			break;
		}		
	}
	private function sanitize($string, $force_lowercase = true, $anal = false) {
		$strip = array("~", "`", "!", "@", "#", "$", "%", "^", "&", "*", "(", ")", "=", "+", "[", "{", "]",
		               "}", "\\", "|", ";", ":", "\"", "'", "&#8216;", "&#8217;", "&#8220;", "&#8221;", "&#8211;", "&#8212;",
		               "â€”", "â€“", ",", "<", "®", ">", "/", "?");
		$clean = trim(str_replace($strip, "-", strip_tags($string)));
		$clean = preg_replace('/\s+/', "-", $clean);
		$clean = ($anal) ? preg_replace("/[^a-zA-Z0-9]/", "", $clean) : $clean ;
		return ($force_lowercase) ?
	    (function_exists('mb_strtolower')) ?
	        mb_strtolower($clean, 'UTF-8') :
	        strtolower($clean) :
	    $clean;
	}
	public function artImages() {
		$token = 'b4fc4f0fa3061a52752fa632bb64b95d';
		$domain = 'artnaturals.myshopify.com';
		$shopifyClient = App::make('ShopifyAPI', [
			'API_KEY'		=> 'bd2cd87a456a4c225ca687a30578e448',
			'API_SECRET'	=> '50617402d44e2d3e5ce32f21a07ce9a5',
			'SHOP_DOMAIN'	=> $domain,
			'ACCESS_TOKEN'	=> $token
		]);
		try {
			
			$filename = 'apiupload.gif';
			$attachment = 'R0lGODlhAQABAPABAP\/\/\/wAAACH5BAEKAAAALAAAAAABAAEAAAICRAEAOw==\n';
			$directory = '/Users/JJohnson/Downloads/TIPS';
			$files = \File::allFiles($directory);
			foreach ($files as $file) {
				$path = explode('/', $file);
				$filename = array_pop($path);
				$filename = $this->sanitize($filename);
				$data = file_get_contents($file);
				$type = pathinfo($file, PATHINFO_EXTENSION);
				$attachment = base64_encode($data);
				$call = $shopifyClient->call(['URL' => '/admin/themes/135924934/assets.json', 'METHOD' => 'PUT', 'DATA' => ['asset' => ['key' => 'assets/'.$filename, 'attachment' => $attachment] ] ]);
				echo $filename.'<br>';
			}

		} catch (Exception $e) {
			echo $e->getMessage();
			exit;
		}	
	}
	public function artRoutes() {
		$token = 'b4fc4f0fa3061a52752fa632bb64b95d';
		$domain = 'artnaturals.myshopify.com';
		$shopifyClient = App::make('ShopifyAPI', [
			'API_KEY'		=> 'bd2cd87a456a4c225ca687a30578e448',
			'API_SECRET'	=> '50617402d44e2d3e5ce32f21a07ce9a5',
			'SHOP_DOMAIN'	=> $domain,
			'ACCESS_TOKEN'	=> $token
		]);
		$redirects = array(
			array('/get-summer-ready-skin-with-these-beach-bag-essentials', '/blogs/art-naturals/get-summer-ready-skin-with-these-beach-bag-essentials')
		);
		foreach ($redirects as $redirect) {
			$data = new \stdClass();
			$data->redirect = new \stdClass();
			$data->redirect->path = $redirect[0];
			$data->redirect->target = $redirect[1];
			
			try {
				$call = $shopifyClient->call(['URL' => '/admin/redirects.json', 'METHOD' => 'POST', 'DATA' => [ 'redirect' => [ 'path' => $redirect[0], 'target' => $redirect[1] ] ] ]);
				print_r($call);
				break;
			} catch (Exception $e) {
				echo $e->getMessage();
				break;
			}
		}
	}
	public function art() {
		echo 'Building Page';
		$titles = array(
			'Organic Jojoba Oil');
		$token = 'b4fc4f0fa3061a52752fa632bb64b95d';
		$domain = 'artnaturals.myshopify.com';
		
		$shopifyClient = App::make('ShopifyAPI', [
			'API_KEY'		=> 'bd2cd87a456a4c225ca687a30578e448',
			'API_SECRET'	=> '50617402d44e2d3e5ce32f21a07ce9a5',
			'SHOP_DOMAIN'	=> $domain,
			'ACCESS_TOKEN'	=> $token
		]);
		$data = new \stdClass();
		$data->page = new \stdClass();
		$data->page->title = '';
		$data->page->body_html = '';
		
		foreach ($titles as $page) {
			$data->page->title = $page." -- In Your 20's";
			try {
				$call = $shopifyClient->call([
				'URL' => 'pages.json', 
				'METHOD' => 'POST',
				'ALLDATA' => 1,
				'DATA' => json_encode($data)
				]);
			} catch (Exception $e) {
				echo $e->getMessage();
			}
			$data->page->title = $page." -- In Your 30's";
			try {
				$call = $shopifyClient->call([
				'URL' => 'pages.json', 
				'METHOD' => 'POST',
				'ALLDATA' => 1,
				'DATA' => json_encode($data)
				]);
			} catch (Exception $e) {
				echo $e->getMessage();
			}
			$data->page->title = $page." -- In Your 40's";
			try {
				$call = $shopifyClient->call([
				'URL' => 'pages.json', 
				'METHOD' => 'POST',
				'ALLDATA' => 1,
				'DATA' => json_encode($data)
				]);
			} catch (Exception $e) {
				echo $e->getMessage();
			}
			$data->page->title = $page." -- In Your 50's";
			try {
				$call = $shopifyClient->call([
				'URL' => 'pages.json', 
				'METHOD' => 'POST',
				'ALLDATA' => 1,
				'DATA' => json_encode($data)
				]);
			} catch (Exception $e) {
				echo $e->getMessage();
			}
			$data->page->title = $page." -- Men";
			try {
				$call = $shopifyClient->call([
				'URL' => 'pages.json', 
				'METHOD' => 'POST',
				'ALLDATA' => 1,
				'DATA' => json_encode($data)
				]);
			} catch (Exception $e) {
				echo $e->getMessage();
			}
		}
		
		
	}
	
	private $url = 'https://www.upwork.com/services/api/auth?oauth_token=';
	
	public function upworkCallback( Request $request ) {
		$config = new \Upwork\API\Config(
		    array(
		        'consumerKey'       => 'fbbd7db551600a8d5054b041ff3385c1',
		        'consumerSecret'    => '097fc447778e03cc',
		        'requestToken'      => $request->session()->get('oauth_token'),
				'requestSecret'     => $request->session()->get('oauth_token_secret'),
		        'verifier'          => Input::get('oauth_verifier'),
				'authType'          => 'OAuthPHPLib',
		        'debug'             => true,
				'mode'              => 'web'
		    )
		);
		
		$client = new \Upwork\API\Client($config);
		$accessTokenInfo = $client->auth();
		
		$request->session()->put('access_token', $accessTokenInfo['access_token']);
		$request->session()->put('access_secret', $accessTokenInfo['access_secret']);
		
		// gets info of the authenticated user
	    $auth = new \Upwork\API\Routers\Auth($client);
	    $info = $auth->getUserInfo();
	
	    print_r($info);
	}
	public function upwork( Request $request ) {  
		$config = new \Upwork\API\Config(
		    array(
		        'consumerKey'       => 'fbbd7db551600a8d5054b041ff3385c1',
		        'consumerSecret'    => '097fc447778e03cc',
				'authType'          => 'OAuthPHPLib',
		        'debug'             => true,
				'mode'              => 'web'
		    )
		);
		// Create Client
		$client = new \Upwork\API\Client($config);
		
		// Get Tokens
		$requestTokenInfo = $client->getRequestToken();
		
		// Store Tokens
		$request->session()->put('oauth_token', $requestTokenInfo['oauth_token']);
		$request->session()->put('oauth_token_secret', $requestTokenInfo['oauth_token_secret']);
		
		// Generate Auth URL and Redirect
		$client->auth();
	}
}
