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

//Getting cart
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

$line_items = "";
for ($i = 1; $i <= count($cart["line_items"]); $i++) {

    $url = 'https://' . $app_id . '@' . $store_url . '/admin/api/2020-04/products/' . $cart["line_items"][$i-1]["product_id"] . '/images.json';
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'X-Shopify-Access-Token: ' . $app_pass));
    $response = curl_exec($ch);
    curl_close($ch);
    $image = json_decode($response, true);
    $image = $image["images"][0]["src"];

    $line_items = $line_items . '&line_items' . urlencode('[' . ($i-1) . '][name]') . '=' . urlencode($cart["line_items"][$i-1]["title"]) . '&line_items' . urlencode('[' . ($i-1) . '][images][]') . '=' . urlencode($image) . '&line_items' . urlencode('[' . ($i-1) . '][amount]') . '=' . (($cart["line_items"][$i-1]["price"])*100) . '&line_items' . urlencode('[' . ($i-1) . '][currency]') . '=eur&line_items' . urlencode('[' . ($i-1) . '][quantity]') . '=' . $cart["line_items"][$i-1]["quantity"];
}
$line_items = urlencode('payment_method_types[]') . '=card' . '&customer_email=' . urlencode($_POST['email']) . '&client_reference_id=' . $_POST['token'] . ':' . urlencode($_POST['first_name'])  . ':' . urlencode($_POST['last_name']) . $line_items . '&success_url=' . urlencode($success_url) . '&cancel_url=' . urlencode($cancel_url);

$post_data = $line_items;

$url = 'https://api.stripe.com/v1/checkout/sessions';
    
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded', 'Authorization: Bearer ' . $stripe_secret));   
curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
$result = curl_exec($ch);
curl_close($ch);

$session_id = json_decode($result, true);
$session_id = $session_id["id"];

?>

<script src="https://js.stripe.com/v3/"></script>
<script>
    var stripe = Stripe('<?php echo($stripe_public); ?>');
    stripe.redirectToCheckout({
    sessionId: '<?php echo($session_id); ?>'
    }).then(function (result) {
    });
</script>
