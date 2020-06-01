<?php

//Your Shopify App Credentials
$app_id = 'YOUR APP ID';
$app_pass = 'YOUR APP PASS';
$store_url = 'YOUR SHOP MYSHOPIFY DOMAIN';

// For test payments we want to enable the sandbox mode. If you want to put live
// payments through then this setting needs changing to `false`.
$enableSandbox = true;

// PayPal settings. Change these to your account details and the relevant URLs
// for your site.
$paypalConfig = [
	'email' => 'YOUR_PAYPAL_EMAIL',
	'return_url' => 'redirect-order.php?email=' . $_POST['email'],
	'cancel_url' => 'YOUR_CANCEL_PAGE',
	'notify_url' => 'paypal.php'
];

$paypalUrl = $enableSandbox ? 'https://www.sandbox.paypal.com/cgi-bin/webscr' : 'https://www.paypal.com/cgi-bin/webscr';

function verifyTransaction($data) {
	global $paypalUrl;

	$req = 'cmd=_notify-validate';
	foreach ($data as $key => $value) {
		$value = urlencode(stripslashes($value));
		$value = preg_replace('/(.*[^%^0^D])(%0A)(.*)/i', '${1}%0D%0A${3}', $value); // IPN fix
		$req .= "&$key=$value";
	}

	$ch = curl_init($paypalUrl);
	curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $req);
	curl_setopt($ch, CURLOPT_SSLVERSION, 6);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
	curl_setopt($ch, CURLOPT_FORBID_REUSE, 1);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array('Connection: Close'));
	$res = curl_exec($ch);

	if (!$res) {
		$errno = curl_errno($ch);
		$errstr = curl_error($ch);
		curl_close($ch);
		throw new Exception("cURL error: [$errno] $errstr");
	}

	$info = curl_getinfo($ch);

	// Check the http response
	$httpCode = $info['http_code'];
	if ($httpCode != 200) {
		throw new Exception("PayPal responded with http code $httpCode");
	}

	curl_close($ch);

	return $res === 'VERIFIED';
}

// Check if paypal request or response
if (!isset($_POST["txn_id"]) && !isset($_POST["txn_type"])) {

	// Grab the post data so that we can set up the query string for PayPal.
	// Ideally we'd use a whitelist here to check nothing is being injected into
	// our post data.
	$data = [];

	//Set customer data
	$data['payer_email'] = $_POST['email'];
	$data['first_name'] = $_POST['first_name'];
	$data['last_name'] = $_POST['last_name'];

	// Set the PayPal account.
	$data['business'] = $paypalConfig['email'];
	$data['upload'] = 1;
	$data['cmd'] = "_cart";
	$data['no_note'] = 1;
	$data['lc'] = "FR";
	$data['bn'] = "PP-BuyNowBF:btn_buynow_LG.gif:NonHostedGuest";

	// Set the PayPal return addresses.
	$data['return'] = stripslashes($paypalConfig['return_url']);
	$data['cancel_return'] = stripslashes($paypalConfig['cancel_url']);
	$data['notify_url'] = stripslashes($paypalConfig['notify_url']);

	// Set the details about the product being purchased, including the amount
	// and currency so that these aren't overridden by the form data.
	
	$data['currency_code'] = 'EUR';

	$url = 'https://' . $app_id . '@' . $store_url . '/admin/api/2020-01/carts/' . $_POST['token'] . '.json';

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_TIMEOUT, 30);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'X-Shopify-Access-Token: ' . $app_pass));
	$response = curl_exec($ch);
	curl_close($ch);

	$cart = json_decode($response, true);

	$cart = $cart["cart"];

	for ($i = 1; $i <= count($cart["line_items"]); $i++) {
		$data['item_name_'.$i] = $cart["line_items"][$i-1]["title"];
		$data['amount_'.$i] = $cart["line_items"][$i-1]["price"];
		$data['quantity_'.$i] = $cart["line_items"][$i-1]["quantity"];
	}

	// Add any custom fields for the query string.
	$data['custom'] = 'shop-order:' . $_POST['token'] . ':' . $_POST['email'] . ':' . $_POST['first_name'] . ':' . $_POST['last_name'];

	// Build the query string from the data.
	$queryString = http_build_query($data);

	// Redirect to paypal IPN
	header('location:' . $paypalUrl . '?' . $queryString);
	exit();

} else {

	// Handle the PayPal response.
	// Assign posted variables to local data array.
	$data = [
		'item_name' => $_POST['item_name'],
		'item_number' => $_POST['item_number'],
		'payment_status' => $_POST['payment_status'],
		'payment_amount' => $_POST['mc_gross'],
		'payment_currency' => $_POST['mc_currency'],
		'txn_id' => $_POST['txn_id'],
		'receiver_email' => $_POST['receiver_email'],
		'payer_email' => $_POST['payer_email'],
		'custom' => $_POST['custom'],
	];

	list($payment_type, $cart_token, $email, $first_name, $last_name) = explode(":", $data['custom']);

	if ($payment_type == 'shop-order') {
	// We need to verify the transaction comes from PayPal
	if (verifyTransaction($_POST)) {

		$url = 'https://' . $app_id . '@' . $store_url . '/admin/api/2020-01/carts/' . $cart_token . '.json';

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_TIMEOUT, 30);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'X-Shopify-Access-Token: ' . $app_pass));
		$response = curl_exec($ch);
		curl_close($ch);

		$cart = json_decode($response, true);
	
		$cart = $cart["cart"];

		$line_items = "";
		for ($i = 1; $i <= count($cart["line_items"]); $i++) {
			$line_items = $line_items . '{ "variant_id" : ' . $cart["line_items"][$i-1]["variant_id"] . ', "quantity" : ' . $cart["line_items"][$i-1]["quantity"] . ' }';
		}
		$line_items = '[' . $line_items . ']';

		$post_data='{"order":{"customer":{"email":"' . $email . '","first_name":"' . $first_name . '","last_name":"' . $last_name . '"},"financial_status":"pending", "line_items":' . $line_items . ', "transactions":[{"kind":"authorization","status":"success","amount":' . $data['payment_amount'] . '}]}}';
		$url="https://' . $app_id . '@' . $store_url . '/admin/api/2020-01/orders.json";
		
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_TIMEOUT, 30);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'X-Shopify-Access-Token: ' . $app_pass));   
		curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
		$result = curl_exec($ch);
		curl_close($ch);

		$order_id = json_decode($result, true);
		$order_id = $order_id["order"]["id"];

		$url = 'https://' . $app_id . '@' . $store_url . '/admin/api/2020-01/orders/' . $order_id . '/transactions.json';

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_TIMEOUT, 30);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'X-Shopify-Access-Token: ' . $app_pass));
		$response = curl_exec($ch);
		curl_close($ch);

		$transaction_id = json_decode($response, true);
		$transaction_id = $transaction_id["transactions"][0]["id"];

		$post_data='{"transaction": {"currency": "EUR", "amount": ' . $data['payment_amount'] . ', "kind": "capture", "parent_id": ' . $transaction_id . ', "test": "true" }}';
		$url = 'https://' . $app_id . '@' . $store_url . '/admin/api/2020-01/orders/' . $order_id . '/transactions.json';
		
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_TIMEOUT, 30);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'X-Shopify-Access-Token: ' . $app_pass));   
		curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
		$result = curl_exec($ch);
		curl_close($ch);

		}
	}
}


?>
