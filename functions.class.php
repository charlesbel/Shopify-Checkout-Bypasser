<?php

class Functions {

    public $stripeUrl;
    public $stripe_public;
    public $stripe_secret;
    public $stripe_webhookSecret;

    public $paypalUrl;
    public $paypalWebhookId;
    public $paypalClientId;
    public $paypalSecret;

    public function __construct() {
        include('config.php');

        $this->stripeUrl = 'https://api.stripe.com';
        $this->stripe_public = $enableTest ? $test_public : $live_public;
        $this->stripe_secret = $enableTest ? $test_secret : $live_secret;
        $this->stripe_webhookSecret = $enableTest ? $stripe_webhookSecret_test : $stripe_webhookSecret_live;

        $this->paypalUrl = $enableTest ? 'https://api.sandbox.paypal.com' : 'https://api.paypal.com';
        $this->paypalWebhookId = $enableTest ? $paypal_webhookId_sandbox : $paypal_webhookId_live;
        $this->paypalClientId = $enableTest ? $paypal_clientId_sandbox : $paypal_clientId_live;
        $this->paypalSecret = $enableTest ? $paypal_secret_sandbox: $paypal_secret_live;
    }


//Payments

    function generateRandomToken() {
        return sha1(mt_rand(1, 90000) . 'SALT');
    }

    //Shopify

    function getShopifyCart($token) {
        include('config.php');

        $url = 'https://' . $app_id . '@' . $store_url . '/admin/api/2020-01/carts/' . $token . '.json';
    
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'X-Shopify-Access-Token: ' . $app_pass));
        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode($response, true)["cart"];
    }

    function postRequestShopify($url, $post_data) {
        include('config.php');

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://' . $app_id . '@' . $store_url . $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'X-Shopify-Access-Token: ' . $app_pass));   
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
        $result = curl_exec($ch);
        curl_close($ch);
    
        return $result;
    }

    function putRequestShopify($url, $post_data) {
        include('config.php');

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://' . $app_id . '@' . $store_url . $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'X-Shopify-Access-Token: ' . $app_pass));   
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
        $result = curl_exec($ch);
        curl_close($ch);
    
        return $result;
    }

    function getRequestShopify($url) {
        include('config.php');

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://' . $app_id . '@' . $store_url . $url);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'X-Shopify-Access-Token: ' . $app_pass)); 
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
        $result = curl_exec($ch);
        curl_close($ch);
    
        return $result;
    }

    function deleteRequestShopify($url) {
        include('config.php');

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://' . $app_id . '@' . $store_url . $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_HTTPHEADER,  array('Content-Type: application/json', 'X-Shopify-Access-Token: ' . $app_pass));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
        $result  = curl_exec($ch);
        curl_close($ch);

        return $result;
    }

    function getCustomerIdFromEmail($email) {
        $response = $this->getRequestShopify('/admin/customers/search.json?query=email:"' . $email . '"&fields=id,email');
        return json_decode($response, true)['customers'][0]['id'];
    }

    function sendAccountInvite($customer_id) {
        $data = '{"customer_invite": {}}';
        $response = $this->postRequestShopify("/admin/api/2020-10/customers/" . $customer_id . "/send_invite.json", $data);
        return $response;
    }

    //Stripe

    function getRequestStripe($url) {

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->stripeUrl . $url);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: Bearer ' . $this->stripe_secret));   
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
        $result = curl_exec($ch);
        curl_close($ch);

        return $result;
    }

    function postRequestStripe($url, $post_data) {

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->stripeUrl . $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded', 'Authorization: Bearer ' . $this->stripe_secret));   
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
        $result = curl_exec($ch);
        curl_close($ch);

        return $result;
    }

    function checkStripeWebhookSign($headers, $webhook) {
        $stripeSigHeader = $headers['Stripe-Signature'];
        parse_str(str_replace(",", "&", $stripeSigHeader), $sigData);
        $timestamp = $sigData['t'];
        $requestSig = $sigData['v1'];

        $signed_payload = "$timestamp.$webhook";

        $Sig = hash_hmac('sha256', $signed_payload, $this->stripe_webhookSecret);

        if ($Sig == $requestSig) {
            return true;
        } else {
            return false;
        }

        return false;
    }

    //PayPal

    function getPaypalAccessToken() {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->paypalUrl . '/v1/oauth2/token');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded', 'Authorization: Basic ' . base64_encode($this->paypalClientId . ":" . $this->paypalSecret)));
        curl_setopt($ch, CURLOPT_POSTFIELDS, "grant_type=client_credentials");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
        $result = curl_exec($ch);
        curl_close($ch);
        $result = json_decode($result, true);

        return $result['access_token'];
    }

    function getRequestPaypal($url) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->paypalUrl . $url);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'Authorization: Bearer ' . $this->getPaypalAccessToken()));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
        $result = curl_exec($ch);
        curl_close($ch);

        return $result;
    }

    function postRequestPaypal($url, $post_data) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->paypalUrl . $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'Authorization: Bearer ' . $this->getPaypalAccessToken()));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
        $result = curl_exec($ch);
        curl_close($ch);

        return $result;
    }

    function patchRequestPaypal($url, $post_data) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->paypalUrl . $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'Authorization: Bearer ' . $this->getPaypalAccessToken()));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
        $result = curl_exec($ch);
        curl_close($ch);

        return $result;
    }

    function capturePaypalPayment($id) {
        $url = $this->paypalUrl . "/v2/checkout/orders/" . $id . "/capture";
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'Authorization: Bearer ' . $this->getPaypalAccessToken()));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
        curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpcode == 201 || $httpcode == 200) {
            return true;
        } else {
            return false;
        }

        return false;
    }

    function checkPaypalWebhookSign($headers, $webhook) {

        $data['auth_algo'] = $headers['Paypal-Auth-Algo'];
        $data['cert_url'] = $headers['Paypal-Cert-Url'];
        $data['transmission_id'] = $headers['Paypal-Transmission-Id'];
        $data['transmission_sig'] = $headers['Paypal-Transmission-Sig'];
        $data['transmission_time'] = $headers['Paypal-Transmission-Time'];

        $data['webhook_id'] = $this->paypalWebhookId;
        $data['webhook_event'] = $webhook;

        $url = $this->paypalUrl . '/v1/notifications/verify-webhook-signature';

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'Authorization: Bearer ' . $this->getPaypalAccessToken()));
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
        $response = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $status = json_decode($response, true);

        if ($httpcode == 200 && $status['verification_status'] == "SUCCESS") {
                return true;
        } else {
                return false;
        }

        return false;

    }
    
}

?>