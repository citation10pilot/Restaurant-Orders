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
			array('http://artnaturals.com/product/youth-eye-gel', 'http://artnaturals.com/products/youth-eye-gel'),
			array('/product/tea-tree-essential-oil-pure-natural', '/products/tea-tree-essential-oil-pure-natural'),
			array('/product/artnaturals-rosehip-oil', '/products/artnaturals-rosehip-oil'),
			array('/product/artnaturals-peppermint-oil', '/products/artnaturals-peppermint-oil'),
			array('/product/enhanced-vitamin-c-serum-with-hyaluronic-acid', '/products/enhanced-vitamin-c-serum-with-hyaluronic-acid'),
			array('/product/artnaturals-hyaluronic-acid-serum', '/products/artnaturals-hyaluronic-acid-serum'),
			array('/product/artnaturals-aloe-vera-gel', '/products/aloe-vera-gel'),
			array('/product/blackhead-remover-tool-set', '/products/blackhead-remover-tool-set'),
			array('/product/art-naturals-argan-oil-hair-mask', '/products/art-naturals-argan-oil-hair-mask'),
			array('/product/art-naturals-vitamin-c-hydrating-facial-toner', '/products/art-naturals-vitamin-c-hydrating-facial-toner'),
			array('/product/beard-and-stache-oil', '/products/beard-and-stache-oil'),
			array('/product/ingrown-hair-removal-serum', '/products/ingrown-hair-removal-serum'),
			array('/product/scalp-18-therapeutic-anti-dandruff-shampoo', '/products/scalp-18-therapeutic-anti-dandruff-shampoo'),
			array('/product/top-8-essential-oils', '/products/top-8-essential-oils'),
			array('/product/hair-fusion', '/products/hair-fusion'),
			array('/product/essential-oil-diffuser', '/products/essential-oil-diffuser'),
			array('/product/cellulite-away-treatment-cream', '/products/cellulite-away-cream'),
			array('/product/dead-sea-mud-mask', '/products/dead-sea-mud-mask'),
			array('/product/rechargeable-electric-callus-remover', '/products/rechargeable-electric-callus-remover'),
			array('/product/detangling-hair-brush-set', '/products/detangling-hair-brush-set'),
			array('/product_variation/product-16134-variation', '/products/hair-fusion'),
			array('/product/art-naturals-face-neck-firming-cream-for-wrinkles-1-7-oz', '/products/face-neck-firming-cream-for-wrinkles'),
			array('/product/art-naturals-anti-acne-serum-1-oz', '/products/anti-acne-serum'),
			array('/product/root-cover-up', '/products/root-cover-up'),
			array('/product_variation/product-16413-variation', '/products/root-cover-up'),
			array('/product/art-naturals-konjac-facial-sponge-set', '/products/konjac-facial-sponge-set'),
			array('/product/art-naturals-bath-bombs-gift-set-6-pack', '/products/art-naturals-bath-bombs-gift-set-6-pack'),
			array('/product/art-naturals-stretch-mark-scar-removal-cream-4-0-oz', '/products/art-naturals-stretch-mark-scar-removal-cream-4-0-oz'),
			array('/product_variation/product-16413-variation-2', '/products/root-cover-up'),
			array('/product_variation/product-16413-variation-3', '/products/root-cover-up'),
			array('/product_variation/product-16413-variation-4', '/products/root-cover-up'),
			array('/product_variation/product-16413-variation-5', '/products/root-cover-up'),
			array('/product_variation/product-16134-variation-2', '/products/hair-fusion'),
			array('/product_variation/product-16134-variation-3', '/products/hair-fusion'),
			array('/product_variation/product-16134-variation-4', '/products/hair-fusion'),
			array('/product_variation/product-16134-variation-5', '/products/hair-fusion'),
			array('/product/frankincense-oil', '/products/frankincense-oil'),
			array('/product/art-naturals-organic-arabica-coffee-scrub-8-8-oz', '/products/art-naturals-organic-arabica-coffee-scrub-8-8-oz'),
			array('/product/art-naturals-organic-20-vitamin-c-serum-1-0-oz-2-5-vitamin-a-retinol-serum-1-0-oz-holiday-gift-set', '/products/art-naturals-organic-20-vitamin-c-serum-1-0-oz-2-5-vitamin-a-retinol-serum-1-0-oz-holiday-gift-set'),
			array('/product/art-naturals-brush-hair-straightener', '/products/art-naturals-brush-hair-straightener'),
			array('/product/art-naturals-thermal-hair-protector-8-0-oz', '/products/art-naturals-thermal-hair-protector-8.0-oz'),
			array('/product/art-naturals-enhanced-retinol-serum-2-5-with-20-vitamin-c-hyaluronic-acid-1-oz', '/products/art-naturals-enhanced-retinol-serum-2-5-with-20-vitamin-c-hyaluronic-acid-1-oz'),
			array('/product/art-naturals-magnesium-oil-12-oz', '/products/art-naturals-magnesium-oil-12-oz'),
			array('/product/art-naturals-organic-argan-oil-hair-loss-shampoo-for-hair-regrowth-16-oz', '/products/art-naturals-organic-argan-oil-hair-loss-shampoo-for-hair-regrowth-16-oz'),
			array('/product/art-naturals-argan-oil-hair-loss-prevention-shampoo', '/products/art-naturals-argan-oil-hair-loss-prevention-shampoo'),
			array('/product/art-naturals-enhanced-retinol-cream-moisturizer-2-5-with-20-vitamin-c-hyaluronic-acid-1-oz', '/products/art-naturals-enhanced-retinol-cream-moisturizer-2-5-with-20-vitamin-c-hyaluronic-acid-1-7-oz'),
			array('/product/art-naturals-cellulite-body-massager-brush', '/products/art-naturals-cellulite-body-massager-brush'),
			array('/product/art-naturals-argan-oil-4-oz', '/products/art-naturals-argan-oil-4-oz'),
			array('/product/art-naturals-argan-oil-daily-hair-conditioner', '/products/art-naturals-argan-oil-daily-hair-conditioner'),
			array('/product/24515', '/products/art-naturals-100-pure-castor-oil-16-oz'),
			array('/product/argan-oil-shampoo-and-conditioner-set', '/products/argan-oil-shampoo-and-conditioner-set'),
			array('/product/lavender-oil-3-piece-set', '/products/lavender-oil-3-piece-set'),
			array('/product/anti-aging-set', '/products/anti-aging-set'),
			array('/product/fractionated-coconut-oil', '/products/fractionated-coconut-oil'),
			array('/product/make-up-remover', '/products/make-up-remover'),
			array('/product/organic-clarifying-acne-face-wash', '/products/organic-clarifying-acne-face-wash'),
			array('/product/natural-kiss-lip-gloss-plumper', '/products/natural-kiss-lip-gloss-plumper'),
			array('/product/organic-sunless-tanning-lotion-set', '/products/natural-sunless-tanning-lotion-set'),
			array('/product/ultrasonic-aroma-essential-oil-humidifier', '/products/ultrasonic-aroma-essential-oil-humidifier'),
			array('/product/clear-skin-drying-lotion', '/products/clear-skin-drying-lotion'),
			array('/product/callus-remover-heads', '/products/callus-remover-heads'),
			array('/product/tea-tree-foot-soak', '/products/tea-tree-foot-soak-salt'),
			array('/product/makup-setting', '/products/makup-setting-spray'),
			array('/product/rosewater-toner', '/products/rose-water-toner'),
			array('/product/silky-soft-foot-peel-exfoliation-mask', '/products/silky-soft-foot-peel-exfoliation-mask'),
			array('/product/6-piece-soap-bar-set', '/products/6-piece-soap-bar-set'),
			array('/product/argan-oil-leave-in-conditioner', '/products/argan-oil-leave-in-conditioner'),
			array('/product/hot-air-styler-brush', '/products/hot-air-styler-brush'),
			array('/product/soleil-spf-30-broad-spectrum-sunscreen', '/products/soleil-spf-30-broad-spectrum-sunscreen'),
			array('/product/soleil-protective-body-tanning-oil', '/products/soleil-protective-body-tanning-oil'),
			array('/product/demrelax-pain-relief-cream', '/products/demrelax-pain-relief-cream'),
			array('/contact-us', '/'),
			array('/faq', '/'),
			array('/contact-page-2', '/'),
			array('/blog', '/blogs/art-naturals'),
			array('/wishlist', '/'),
			array('/shop', '/'),
			array('/cart', '/cart'),
			array('/checkout', '/cart'),
			array('/my-account', '/account/login'),
			array('/about-us', '/pages/about-us'),
			array('/products', '/'),
			array('/privacy-policy', '/pages/privacy-policy'),
			array('/terms-conditions', '/pages/terms-conditions'),
			array('/checkout/review-order', '/cart'),
			array('/return-policy', '/'),
			array('/brand-ambassador-program', '/pages/affiliate'),
			array('/learning', '/blogs/art-naturals'),
			array('/how-to-naturally-achieve-eyelash-growth', '/blogs/art-naturals/how-to-naturally-achieve-eyelash-growth/'),
			array('/the-best-ways-to-relax-at-home', '/blogs/art-naturals/the-best-ways-to-relax-at-home/'),
			array('/how-to-get-skin-relief-this-holiday-season', '/blogs/art-naturals/how-to-get-skin-relief-this-holiday-season/'),
			array('/the-correct-way-to-use-a-hair-brush', '/blogs/art-naturals/the-correct-way-to-use-a-hair-brush/'),
			array('/how-to-get-over-a-bad-day', '/blogs/art-naturals/how-to-get-over-a-bad-day/'),
			array('/easing-the-struggles-of-shaving', '/blogs/art-naturals/easing-the-struggles-of-shaving/'),
			array('/tips-on-all-natural-anti-aging', '/blogs/art-naturals/tips-on-all-natural-anti-aging/'),
			array('/the-beard-care-basics', '/blogs/art-naturals/the-beard-care-basics/'),
			array('/the-best-diy-lip-balm-recipes', '/blogs/art-naturals/the-best-diy-lip-balm-recipes/'),
			array('/the-best-ways-to-used-avocados', '/blogs/art-naturals/the-best-ways-to-used-avocados/'),
			array('/how-to-make-your-hair-grow-naturally', '/blogs/art-naturals/how-to-make-your-hair-grow-naturally/'),
			array('/travel-beauty-essentials', '/blogs/art-naturals/travel-beauty-essentials/'),
			array('/thanksgiving-starter-pack', '/blogs/art-naturals/thanksgiving-starter-pack/'),
			array('/holiday-gift-guide', '/blogs/art-naturals/holiday-gift-guide/'),
			array('/best-ways-get-rid-blackheads-naturally', '/blogs/art-naturals/best-ways-get-rid-blackheads-naturally/'),
			array('/top-5-pamper-weekend-essentials', '/blogs/art-naturals/top-5-pamper-weekend-essentials/'),
			array('/how-to-get-a-natural-energy-boost', '/blogs/art-naturals/how-to-get-a-natural-energy-boost/'),
			array('/the-best-diy-christmas-candle-recipes', '/blogs/art-naturals/the-best-diy-christmas-candle-recipes/'),
			array('/the-best-organic-food-in-la', '/blogs/art-naturals/the-best-organic-food-in-la/'),
			array('/the-best-holiday-hairdos', '/blogs/art-naturals/the-best-holiday-hairdos/'),
			array('/christmas-starter-pack', '/blogs/art-naturals/christmas-starter-pack/'),
			array('/19310-2-newyearsresolution', '/blogs/art-naturals/19310-2-newyearsresolution/'),
			array('/the-best-homemade-soap-reicpes', '/blogs/art-naturals/the-best-homemade-soap-reicpes/'),
			array('/how-to-reduce-cellulite-naturally', '/blogs/art-naturals/how-to-reduce-cellulite-naturally/'),
			array('/organic-beauty-bloggers-you-should-follow', '/blogs/art-naturals/organic-beauty-bloggers-you-should-follow/'),
			array('/the-best-recipes-for-vegan-sweets', '/blogs/art-naturals/the-best-recipes-for-vegan-sweets/'),
			array('/overnight-beauty-tips', '/blogs/art-naturals/overnight-beauty-tips/'),
			array('/the-best-parks-in-los-angeles', '/blogs/art-naturals/the-best-parks-in-los-angeles/'),
			array('/why-organic-skincare', '/blogs/art-naturals/why-organic-skincare/'),
			array('/creative-diy-bathroom-projects', '/blogs/art-naturals/creative-diy-bathroom-projects/'),
			array('/21735-2', '/blogs/art-naturals/21735-2/'),
			array('/21958-2', '/blogs/art-naturals/21958-2/'),
			array('/valentines-day-gift-guide', '/blogs/art-naturals/valentines-day-gift-guide/'),
			array('/valentines-day-look', '/blogs/art-naturals/valentines-day-look/'),
			array('/romantic-restaurants-los-angeles-valentines-day', '/blogs/art-naturals/romantic-restaurants-los-angeles-valentines-day/'),
			array('/quickandhealthymeals', '/blogs/art-naturals/quickandhealthymeals/'),
			array('/konjacsponges', '/blogs/art-naturals/konjacsponges/'),
			array('/straighthairstyles', '/blogs/art-naturals/straighthairstyles/'),
			array('/jojobaoil-beauty', '/blogs/art-naturals/jojobaoil-beauty/'),
			array('/quick-morning-exercises', '/blogs/art-naturals/quick-morning-exercises/'),
			array('/festival-hair-accessories', '/blogs/art-naturals/festival-hair-accessories/'),
			array('/preventing-acne', '/blogs/art-naturals/preventing-acne/'),
			array('/drugstore-beauty-products', '/blogs/art-naturals/drugstore-beauty-products/'),
			array('/socal-weekend-getaways', '/blogs/art-naturals/socal-weekend-getaways/'),
			array('/the-benefits-of-yoga', '/blogs/art-naturals/the-benefits-of-yoga/'),
			array('/the-best-essential-oils-for-skin', '/blogs/art-naturals/the-best-essential-oils-for-skin/'),
			array('/spring-break-essentials', '/blogs/art-naturals/spring-break-essentials/'),
			array('/the-health-hazards-of-sitting-down', '/blogs/art-naturals/the-health-hazards-of-sitting-down/'),
			array('/st-patricks-day-look', '/blogs/art-naturals/st-patricks-day-look/'),
			array('/tips-to-become-more-productive', '/blogs/art-naturals/tips-to-become-more-productive/'),
			array('/healthy-on-the-go-breakfast-meals', '/blogs/art-naturals/healthy-on-the-go-breakfast-meals/'),
			array('/ab-workouts-for-swimsuit-season', '/blogs/art-naturals/ab-workouts-for-swimsuit-season/'),
			array('/vitamin-c-beauty-benefits', '/blogs/art-naturals/vitamin-c-beauty-benefits/'),
			array('/coachella-essentials', '/blogs/art-naturals/coachella-essentials/'),
			array('/everyday-anti-aging-tips', '/blogs/art-naturals/everyday-anti-aging-tips/'),
			array('/the-best-acai-bowls-in-la', '/blogs/art-naturals/the-best-acai-bowls-in-la/'),
			array('/best-foods-to-shrink-fat', '/blogs/art-naturals/best-foods-to-shrink-fat/'),
			array('/proper-instructions-uses-for-a-hair-mask', '/blogs/art-naturals/proper-instructions-uses-for-a-hair-mask/'),
			array('/green-smoothie-recipes', '/blogs/art-naturals/green-smoothie-recipes/'),
			array('/important-meditation-tips', '/blogs/art-naturals/important-meditation-tips/'),
			array('/mothers-day-gift-guide', '/blogs/art-naturals/mothers-day-gift-guide/'),
			array('/outdoor-skin-protection-tips', '/blogs/art-naturals/outdoor-skin-protection-tips/'),
			array('/how-to-get-a-natural-makeup-look', '/blogs/art-naturals/how-to-get-a-natural-makeup-look/'),
			array('/things-to-always-be-happy-about', '/blogs/art-naturals/things-to-always-be-happy-about/'),
			array('/volunteer-opportunities-in-los-angeles', '/blogs/art-naturals/volunteer-opportunities-in-los-angeles/'),
			array('/essential-oils-with-major-health-benefits', '/blogs/art-naturals/essential-oils-with-major-health-benefits/'),
			array('/dos-and-donts-of-oily-skin', '/blogs/art-naturals/dos-and-donts-of-oily-skin/'),
			array('/how-to-prepare-for-a-cleanse', '/blogs/art-naturals/how-to-prepare-for-a-cleanse/'),
			array('/the-best-natural-diy-makeup-brush-cleanser-tutorials', '/blogs/art-naturals/the-best-natural-diy-makeup-brush-cleanser-tutorials/'),
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
			'Facial Sunscreen and Tinted Moisturizer',
			'Sunscreen Stick 2 Pack',
			'Beard & Mustache Balm',
			'Black Head Removal Peel Off Mask',
			'SPF 30 Broad Spectrum Sunscreen Spray',
			'Cooling Mineral Water Misting Spray',
			'Beeswax Lip Balm Set',
			'Demrelax Pain Relief Cream',
			'Soleil Protective Body Tanning Oil',
			'Soleil SPF 30 Broad Spectrum Sunscreen',
			'Hot Air Styler Brush',
			'Argan Oil Leave in Conditioner',
			'6 Piece Soap Bar Set',
			'Silky Soft Foot Peel Exfoliation Mask',
			'Rose Water Toner',
			'Makup Setting Spray',
			'Tea Tree Foot Soak Salt',
			'Callus Remover Heads',
			'Clear Skin Drying Lotion',
			'Ultrasonic Aroma Essential Oil Humidifier',
			'Natural Sunless Tanning Lotion Set',
			'Natural Kiss Lip Gloss Plumper',
			'Organic Clarifying Acne Face Wash',
			'Make-up Remover',
			'Fractionated Coconut Oil',
			'Anti-Aging Set',
			'Lavender Oil 3 Piece Set',
			'Argan Oil Shampoo and Conditioner Set',
			'Art Naturals 100% Pure Castor Oil 16 oz',
			'Art Naturals Argan Oil Daily Hair Conditioner',
			'Art Naturals Argan Oil 4 oz',
			'Art Naturals Cellulite Body Massager Brush',
			'Art Naturals Enhanced Retinol Cream Moisturizer 2.5% with 20% Vitamin C & Hyaluronic Acid 1.7 oz',
			'Art Naturals Argan Oil Hair Loss Prevention Shampoo',
			'Art Naturals Organic Argan Oil Hair Loss Shampoo for Hair Regrowth 16 Oz',
			'Art Naturals Magnesium Oil 12 Oz',
			'Art Naturals Enhanced Retinol Serum 2.5% with 20% Vitamin C & Hyaluronic Acid 1 oz',
			'Art Naturals Thermal Hair Protector 8.0 Oz',
			'Art Naturals Brush Hair Straightener',
			'Art Naturals Organic 20% Vitamin C Serum 1.0 oz & 2.5% Vitamin A (Retinol) Serum 1.0 oz - Holiday Gift Set',
			'Art Naturals Organic Arabica Coffee Scrub 8.8 oz',
			'Frankincense Oil',
			'Artnaturals Antifungal Soap with Tea Tree Oil',
			'Art Naturals Stretch Mark & Scar Removal Cream 4.0 Oz',
			'Art Naturals Bath Bombs Gift Set - 6 Pack',
			'Konjac Facial Sponge Set',
			'Root Cover Up',
			'Anti Acne Serum',
			'Face & Neck Firming Cream for Wrinkles',
			'Detangling Hair Brush Set',
			'Rechargeable Electric Callus Remover',
			'Dead Sea Mud Mask',
			'Cellulite-Away Cream',
			'Essential Oil Diffuser',
			'Hair Fusion',
			'Top 8 Essential Oils',
			'Scalp 18 Therapeutic Anti Dandruff Shampoo',
			'Organic Daily Argan Oil Daily Shampoo',
			'Ingrown Hair Removal Serum',
			'Beard and Stache Oil',
			'Art Naturals Vitamin C Hydrating Facial Toner',
			'Art Naturals Argan Oil Hair Mask',
			'Blackhead Remover Tool Set',
			'Aloe Vera Gel',
			'ArtNaturals Hyaluronic Acid Serum',
			'Enhanced Vitamin C Serum with Hyaluronic Acid',
			'ArtNaturals Peppermint Oil',
			'ArtNaturals Rosehip Oil',
			'Tea Tree Essential Oil Pure & Natural',
			'Youth Eye Gel',
			'LUSH Eyelash Serum',
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
		        'requestToken'      => Input::get('access_token', null),
		        'verifier'          => Input::get('oauth_verifier', null),
				'authType'          => 'OAuthPHPLib',
		        'debug'             => true,
				'mode'              => 'web'
		    )
		);
		
		$client = new \Upwork\API\Client($config);
		$accessTokenInfo = $client->auth();
		
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
		$client = new \Upwork\API\Client($config);
		$requestTokenInfo = $client->getRequestToken();
		    
		$client->auth();
		echo '<br><a href="'.$this->url.$requestTokenInfo['oauth_token'].'">Authorize</a><br>';
		
	}
}
