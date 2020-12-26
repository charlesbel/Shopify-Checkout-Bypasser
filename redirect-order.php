<?php

include "functions.class.php";
$functions = new Functions;

$response = $functions->getRequestShopify('/admin/api/2020-04/orders.json?status=any');

$orders = json_decode($response, true)["orders"];

for ($i = 1; $i <= count($orders); $i++) {

    if($orders[$i-1]['note'] == $_GET['id']) {
        $link = $orders[$i-1]["order_status_url"];
        break;
    }

}

if ($link != null) {
    header('Location: ' . $link);
} else {
    sleep(5);
    header("Refresh:0");
}

exit();


?>
