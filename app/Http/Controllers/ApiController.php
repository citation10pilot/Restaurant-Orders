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
		// var_dump($request->header('host'));
		// var_dump($request->headers->all());
		if ( $request->header( 'HTTP_X_SHOPIFY_HMAC_SHA256' ) ) {
			$hmac_header = $request->header( 'HTTP_X_SHOPIFY_HMAC_SHA256' ); // X-Shopify-Hmac-Sha256
			$data = file_get_contents('php://input');
			$verified = $this->verify_webhook($data, $hmac_header);
			var_export($verified);
			
			$order = new \App\Order;
		    $order->data = $data;
		    $order->save();	   
		} else {
			return 'This Area Is Closed To The Public.';
		} 
	}

}
