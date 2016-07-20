<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;

class ApiController extends Controller {
	private function verify_webhook($data, $hmac_header) {
		$calculated_hmac = base64_encode(hash_hmac('sha256', $data, \Config::get('shopify.APP_SECRET'), true));
		return ($hmac_header == $calculated_hmac);
	}

	/* Ingests orders from Shopify Webhook */
	public function order( Request $request ) {
		$order = new \App\Order;
	    $order->data = 'Endpoint Hit';
	    $order->save();	
		// var_dump($request->header('host'));
		// var_dump($request->headers->all());
		if ( $request->header( 'X-Shopify-Hmac-SHA256' ) ) {
			$order = new \App\Order;
		    $order->data = 'Header Found ';
		    $order->save();
			$hmac_header = $request->header( 'X-Shopify-Hmac-SHA256' ); // X-Shopify-Hmac-Sha256 / HTTP_X_SHOPIFY_HMAC_SHA256
			$data = file_get_contents('php://input');
			$verified = $this->verify_webhook($data, $hmac_header);
			var_export($verified);
			
			$order = new \App\Order;
		    $order->data = $data;
		    $order->save();	   
		} else {
			$data = file_get_contents('php://input');
			$order = new \App\Order;
		    $order->data = 'Header Not Found '.$data;
		    $order->save();
			return 'This Area Is Closed To The Public.';
		} 
	}

}
