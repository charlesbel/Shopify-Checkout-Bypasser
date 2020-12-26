<?php 

include "../functions.class.php";
$functions = new Functions;

$payload = @file_get_contents('php://input');
$decoded_payload = json_decode($payload, true);
$data = $decoded_payload['data']['object'];
$event_type = $decoded_payload['type'];

if ($event_type == "checkout.session.completed" && $functions->checkStripeWebhookSign(getallheaders(), $payload)) {

    $email = $data["customer_email"];
    $price = ($data['amount_total']) / 100;

    list($buy_info, $token, $first_name, $last_name) = explode(":", $data['client_reference_id']);

    if ($data['mode'] == "payment") {

        $cart_token = $buy_info;

        $cart = $functions->getShopifyCart($cart_token);

        for ($i = 1; $i <= count($cart["line_items"]); $i++) {
            $line_items[$i-1]['variant_id'] = $cart["line_items"][$i-1]["variant_id"];
            $line_items[$i-1]['quantity'] = $cart["line_items"][$i-1]["quantity"];
        }
        
        $order =  [];
        $order['customer']['email'] = $email;
        $order['customer']['first_name'] = $first_name;
        $order['customer']['last_name'] = $last_name;
        $order['customer']['accepts_marketing'] = true;
        $order['financial_status'] = 'pending';
        $order['line_items'] = $line_items;
        $order['transactions'][0]['kind'] = 'authorization';
        $order['transactions'][0]['status'] = 'success';
        $order['transactions'][0]['amount'] = $price;
        $order['note'] = $token;
        $order['order'] = $order;
        
        $result = $functions->postRequestShopify('/admin/api/2020-01/orders.json', json_encode($order));
        
        $order = json_decode($result, true);
        $order_id = $order["order"]["id"];
        $customer_id = $order["order"]['customer']['id'];
        
        $response = $functions->getRequestShopify('/admin/api/2020-01/orders/' . $order_id . '/transactions.json');
        
        $transaction_id = json_decode($response, true)["transactions"][0]["id"];
        
        $transaction['currency'] = 'EUR';
        $transaction['amount'] = strval($price);
        $transaction['kind'] = 'capture';
        $transaction['parent_id'] = $transaction_id;
        $transaction['test'] = 'true';
        
        $functions->postRequestShopify('/admin/api/2020-01/orders/' . $order_id . '/transactions.json', json_encode($transaction));

        foreach($line_items as $item) {
            if ($item['variant_id'] == "34614715056264") {
                $functions->createGoogleUserAndSendEmail($first_name, $last_name, $email);
            } elseif ($item['variant_id'] == "34614574776456") {
                $functions->createMicrosoftUserAndSendEmail($first_name, $last_name, $email);
            }
        }

        $functions->sendAccountInvite($customer_id);

    } elseif ($data['mode'] == "subscription") {

        $sub_info = $functions->getRequestStripe("/v1/subscriptions/" . $data['subscription']);
        $price = (json_decode($sub_info, true)['items']['data'][0]['price']['unit_amount']) / 100;

        $variant_id = $buy_info;

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
        $order['transactions'][0]['amount'] = $price;
        $order['note'] = $token;
        $order['order'] = $order;

        $result = $functions->postRequestShopify('/admin/api/2020-01/orders.json', json_encode($order));

        $order = json_decode($result, true);
        $order_id = $order["order"]["id"];
        $customer_id = $order["order"]['customer']['id'];
        
        $response = $functions->getRequestShopify('/admin/api/2020-01/orders/' . $order_id . '/transactions.json');
        
        $transaction_id = json_decode($response, true)["transactions"][0]["id"];
        
        $transaction['currency'] = 'EUR';
        $transaction['amount'] = strval($price);
        $transaction['kind'] = 'capture';
        $transaction['parent_id'] = $transaction_id;
        $transaction['test'] = 'true';
        $transaction['transaction'] = $transaction;
        
        $functions->postRequestShopify('/admin/api/2020-01/orders/' . $order_id . '/transactions.json', json_encode($transaction));


        if ($variant_id == "34536152531080" || $variant_id == "34536152563848") {
            $functions->setupNewGoogleUser($first_name, $last_name, $email, $customer_id, "stripe", $data['subscription']);
        } elseif ($variant_id == "34536508129416" || $variant_id == "34536508162184") {
            $functions->setupNewMicrosoftUser($first_name, $last_name, $email, $customer_id, "stripe", $data['subscription']);
        }

        $functions->sendAccountInvite($customer_id);
        
    }

} elseif ($event_type == "customer.subscription.updated" && $functions->checkStripeWebhookSign(getallheaders(), $payload)) {

    $metafield_id = $data['metadata']['sub_metafield_id'];

    if (($data['status'] == "unpaid" || $data['status'] == "past_due") && !$functions->isSubSuspended($metafield_id)) {
        $functions->suspendUser($metafield_id);
    } elseif ($data['status'] == "canceled") {
        $functions->deleteUser($metafield_id);
    } elseif ($data['status'] == "active" && $functions->isSubSuspended($metafield_id)) {
        $functions->unsuspendUser($metafield_id);
    }

} elseif ($event_type == "invoice.payment_failed" && $functions->checkStripeWebhookSign(getallheaders(), $payload)) {

    $balance_uptdate = [];
    $balance_uptdate['amount'] = 1050;
    $balance_uptdate['currency'] = "eur";
    $balance_uptdate['description'] = "Frais de rejet bancaire";
        
    $functions->postRequestStripe('/v1/customers/' . $data['customer'] . '/balance_transactions', http_build_query($balance_uptdate));

}

?>