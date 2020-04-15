<?php

//Your Stripe Account Api Keys
$live_public = 'LIVE PUBLIC API KEY';
$live_secret = 'LIVE SECRET API KEY';
$test_public = 'TEST PUBLIC API KEY';
$test_secret = 'TEST SECRET API KEY';

//Your Shopify App Credentials
$app_id = 'APP ID';
$app_pass = 'APP PASS';
$store_url = 'MYSHOPIFY DOMAIN';

//Website Pages
$success_url = 'redirect-order.php?email=' . $_POST['email'];
$cancel_url = 'CANCEL PAGE';

$enableTest = true;

$stripe_public = $enableTest ? $test_public : $live_public;
$stripe_secret = $enableTest ? $test_secret : $live_secret;

$body = @file_get_contents('php://input');

$decoded = json_decode($body, true);

$decoded = $decoded['data']['object'];

$email = $decoded["customer_email"];

list($cart_token, $first_name, $last_name) = explode(":", $decoded['client_reference_id']);

print($cart_token);
print($first_name);
print($last_name);

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
for ($p = 1; $p <= count($cart["line_items"]); $p++) {
    $price = $price + (($cart["line_items"][$p-1]["quantity"])*($cart["line_items"][$p-1]["price"]));
}

print($price);

for ($i = 1; $i <= count($cart["line_items"]); $i++) {
    if ($i == count($cart["line_items"])) {
    $line_items = $line_items . '{ "variant_id" : ' . $cart["line_items"][$i-1]["variant_id"] . ', "quantity" : ' . $cart["line_items"][$i-1]["quantity"] . ' }';
    }
    else {
    $line_items = $line_items . '{ "variant_id" : ' . $cart["line_items"][$i-1]["variant_id"] . ', "quantity" : ' . $cart["line_items"][$i-1]["quantity"] . ' },';
    }
}
$line_items = '[' . $line_items . ']';

$post_data='{"order":{"customer":{"email":"' . $email . '","first_name":"' . $first_name . '","last_name":"' . $last_name . '"},"financial_status":"pending", "line_items":' . $line_items . ', "transactions":[{"kind":"authorization","status":"success","amount":' . $price . '}]}}';
$url='https://' . $app_id . '@' . $store_url . '/admin/api/2020-01/orders.json';

print($post_data);
print($url);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'X-Shopify-Access-Token: ' . $app_pass));   
curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
$result = curl_exec($ch);
curl_close($ch);

print('Result : '.$result);

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

$post_data='{"transaction": {"currency": "EUR", "amount": ' . $price . ', "kind": "capture", "parent_id": ' . $transaction_id . ', "test": "true" }}';
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


?>
