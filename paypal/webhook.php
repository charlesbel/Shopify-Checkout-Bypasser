<?php    

include "../functions.class.php";
$functions = new Functions;

$data = json_decode(@file_get_contents('php://input'), true);
$event_type = $data['event_type'];

if ($event_type == "CHECKOUT.ORDER.APPROVED" && $functions->checkPaypalWebhookSign(getallheaders(), $data)) {
    
    if ($functions->capturePaypalPayment($data['resource']['id'])) {

        list($cart_token, $token, $email, $first_name, $last_name) = explode(":", $data['resource']['purchase_units'][0]['reference_id']);

        $cart = $functions->getShopifyCart($cart_token);

        for ($i = 1; $i <= count($cart["line_items"]); $i++) {
            $line_items[$i-1]['variant_id'] = $cart["line_items"][$i-1]["variant_id"];
            $line_items[$i-1]['quantity'] = $cart["line_items"][$i-1]["quantity"];
        }

        $order['customer']['email'] = $email;
        $order['customer']['first_name'] = $first_name;
        $order['customer']['last_name'] = $last_name;
        $order['customer']['accepts_marketing'] = true;
        $order['financial_status'] = 'pending';
        $order['line_items'] = $line_items;
        $order['transactions'][0]['kind'] = 'authorization';
        $order['transactions'][0]['status'] = 'success';
        $order['transactions'][0]['amount'] = floatval($data['resource']['purchase_units'][0]['amount']['value']);
        $order['note'] = $token;
        $order['order'] = $order;

        $result = $functions->postRequestShopify('/admin/api/2020-01/orders.json', json_encode($order));

        $order = json_decode($result, true);
        $order_id = $order["order"]["id"];
        $customer_id = $order["order"]['customer']['id'];

        $response = $functions->getRequestShopify('/admin/api/2020-01/orders/' . $order_id . '/transactions.json');

        $transaction_id = json_decode($response, true)["transactions"][0]["id"];

        $transaction['currency'] = 'EUR';
        $transaction['amount'] = $data['resource']['purchase_units'][0]['amount']['value'];
        $transaction['kind'] = 'capture';
        $transaction['parent_id'] = $transaction_id;
        $transaction['test'] = 'true';
        $transaction['transaction'] = $transaction;

        $functions->postRequestShopify('/admin/api/2020-01/orders/' . $order_id . '/transactions.json', json_encode($transaction));

        foreach($line_items as $item) {
            if ($item['variant_id'] == "34614715056264") {
                $functions->createGoogleUserAndSendEmail($first_name, $last_name, $email);
            } elseif ($item['variant_id'] == "34614574776456") {
                $functions->createMicrosoftUserAndSendEmail($first_name, $last_name, $email);
            }
        }

        $functions->sendAccountInvite($customer_id);

    } else {
        exit("Cannot Capture Payment");
    }

} elseif ($event_type == "BILLING.SUBSCRIPTION.ACTIVATED" && $functions->checkPaypalWebhookSign(getallheaders(), $data)) {

    $price = $functions->getPlanRegularPrice($data['resource']['plan_id']);

    list($variant_id, $token, $email, $first_name, $last_name) = explode(":", $data['resource']['custom_id']);

    $order =  [];
    $order['customer']['email'] = $email;
    $order['customer']['first_name'] = $first_name;
    $order['customer']['last_name'] = $last_name;
    $order['customer']['accepts_marketing'] = true;
    $order['financial_status'] = 'pending';
    $order['line_items'][0]['variant_id'] = $variant_id;
    $order['line_items'][0]['quantity'] = 1;
    $order['transactions'][0]['kind'] = 'authorization';
    $order['transactions'][0]['status'] = 'success';
    $order['transactions'][0]['amount'] = floatval($price);
    $order['note'] = $token;
    $order['order'] = $order;

    $result = $functions->postRequestShopify('/admin/api/2020-01/orders.json', json_encode($order));

    $order = json_decode($result, true);
    $order_id = $order["order"]["id"];
    $customer_id = $order["order"]['customer']['id'];

    $response = $functions->getRequestShopify('/admin/api/2020-01/orders/' . $order_id . '/transactions.json');

    $transaction_id = json_decode($response, true)["transactions"][0]["id"];

    $transaction['currency'] = 'EUR';
    $transaction['amount'] = $price;
    $transaction['kind'] = 'capture';
    $transaction['parent_id'] = $transaction_id;
    $transaction['test'] = 'true';
    $transaction['transaction'] = $transaction;

    $functions->postRequestShopify('/admin/api/2020-01/orders/' . $order_id . '/transactions.json', json_encode($transaction));

    if ($variant_id == "34536152531080" || $variant_id == "34536152563848") {
        $functions->setupNewGoogleUser($first_name, $last_name, $email, $customer_id, "paypal", $data['resource']['id']);
    } elseif ($variant_id == "34536508129416" || $variant_id == "34536508162184") {
        $functions->setupNewMicrosoftUser($first_name, $last_name, $email, $customer_id, "paypal", $data['resource']['id']);
    }

    $functions->sendAccountInvite($customer_id);

} elseif (($event_type == "BILLING.SUBSCRIPTION.PAYMENT.FAILED" || $event_type == "BILLING.SUBSCRIPTION.SUSPENDED") && $functions->checkPaypalWebhookSign(getallheaders(), $data)) {
    
    $metadata = json_decode($data['resource']['custom_id'], true);

    if (!$functions->isSubSuspended($metadata['sub_metafield_id'])) {
        $functions->suspendUser($metadata['sub_metafield_id']);
    }

} elseif ($event_type == "BILLING.SUBSCRIPTION.CANCELLED" && $functions->checkPaypalWebhookSign(getallheaders(), $data)) {

    $metadata = json_decode($data['resource']['custom_id'], true);

    $functions->deleteUser($metadata['sub_metafield_id']);

} elseif ($event_type == "BILLING.SUBSCRIPTION.UPDATED" && $functions->checkPaypalWebhookSign(getallheaders(), $data)) {

    $metadata = json_decode($data['resource']['custom_id'], true);

    if ($data['resource']['status'] == "ACTIVE" && $functions->isSubSuspended($metafield_id)) {
        $functions->unsuspendUser($metafield_id);
    }

}

?>