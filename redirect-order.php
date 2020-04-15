<?php

//Your Shopify App Credentials
$app_id = 'YOUR APP ID';
$app_pass = 'YOUR APP PASS';
$store_url = 'YOUR SHOP MYSHOPIFY DOMAIN';

$url = 'https://' . $app_id . '@' . $store_url . '/admin/api/2020-04/customers.json?email=' . $_GET['email'];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'X-Shopify-Access-Token: ' . $app_pass));
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
$response = curl_exec($ch);
curl_close($ch);

$customer = json_decode($response, true);

$last_order_id = $customer["customers"][0]["last_order_id"];


$url = 'https://' . $app_id . '@' . $store_url . '/admin/api/2020-04/orders/' . $last_order_id . '.json';

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'X-Shopify-Access-Token: ' . $app_pass));
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
$response = curl_exec($ch);
curl_close($ch);

$order = json_decode($response, true);

$link = $order["order"]["order_status_url"];


header('location:' . $link);
exit();

?>
