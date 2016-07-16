<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;

class ApiController extends Controller {
	
	/* Ingests orders from Shopify Webhook */
	public function order() {
		$hmac_header = $_SERVER['HTTP_X_SHOPIFY_HMAC_SHA256'];
		$data = file_get_contents('php://input');
		$verified = verify_webhook($data, $hmac_header);
		var_export($verified, true);
		
		$order = new App\Order;
	    $order->data = $data;
	    $order->save();
	}

}
