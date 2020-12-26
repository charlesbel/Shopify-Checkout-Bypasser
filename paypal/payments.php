<?php

include "../functions.class.php";
$functions = new Functions;

include('../config.php');

$token =  $functions->generateRandomToken();

$price = 0.00;

$cart = $functions->getShopifyCart($_POST['token']);

for ($i = 1; $i <= count($cart["line_items"]); $i++) {
	$price += floatval($cart["line_items"][$i-1]["price"]) * $cart["line_items"][$i-1]["quantity"];
	$line_items[$i-1]['name'] = $cart["line_items"][$i-1]["title"];
	$line_items[$i-1]['unit_amount']['currency_code'] = $currency;
	$line_items[$i-1]['unit_amount']['value'] = $cart["line_items"][$i-1]["price"];
	$line_items[$i-1]['quantity'] = strval($cart["line_items"][$i-1]["quantity"]);
	$line_items[$i-1]['category'] = "DIGITAL_GOODS";
}

$data['payer']['name']['given_name'] = $_POST['first_name'];
$data['payer']['name']['surname'] = $_POST['last_name'];
$data['payer']['email_address'] = $_POST['email'];
$data['intent'] = "CAPTURE";
$data['purchase_units'][0]['reference_id'] = $_POST['token'] . ':' . $token . ':' . $_POST['email'] . ':' . $_POST['first_name'] . ':' . $_POST['last_name'];
$data['purchase_units'][0]['amount']['currency_code'] = $currency;
$data['purchase_units'][0]['amount']['value'] = strval($price);
$data['purchase_units'][0]['amount']['breakdown']['item_total']['currency_code'] = $currency;
$data['purchase_units'][0]['amount']['breakdown']['item_total']['value'] = strval($price);
$data['purchase_units'][0]['items'] = $line_items;
$data['application_context']['brand_name'] = $brandName;
$data['application_context']['locale'] = $lang;
$data['application_context']['landing_page'] = "LOGIN";
$data['application_context']['shipping_preference'] = "NO_SHIPPING";
$data['application_context']['user_action'] = "PAY_NOW";
$data['application_context']['return_url'] = $return_url . $token;
$data['application_context']['cancel_url'] = $cancel_url;
$data['application_context']['payment_method']['payee_preferred'] = "IMMEDIATE_PAYMENT_REQUIRED";

$result = $functions->postRequestPaypal('/v2/checkout/orders', json_encode($data));
$links = json_decode($result, true);

$checkout_url;

foreach ($links['links'] as $link) {
	if ($link['rel'] == "approve") {
		$checkout_url = $link['href'];
		break;
	}
}

header('location:' . $checkout_url);
exit();

?>