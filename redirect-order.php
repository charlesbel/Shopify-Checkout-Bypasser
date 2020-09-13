<?php

//Your Shopify App Credentials
$app_id = 'YOUR APP ID';
$app_pass = 'YOUR APP PASS';
$store_url = 'YOUR SHOP MYSHOPIFY DOMAIN';

$url = 'https://' . $app_id . '@' . $store_url . '/admin/api/2020-04/orders.json?status=any';

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, array('X-Shopify-Access-Token: ' . $app_pass));
$response = curl_exec($ch);
curl_close($ch);

$order = json_decode($response, true);

$order = $order["orders"];

for ($i = 1; $i <= count($order); $i++) {

    if($order[$i-1]['email'] == $_GET['email']) {
        $link = $order[$i-1]["order_status_url"];
        break;
    }

}

header('Location: ' . $link);
exit();

?>
