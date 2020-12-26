<?php

include "../functions.class.php";
$functions = new Functions;

include('../config.php');

$token =  $functions->generateRandomToken();


$data["payment_method_types[]"] = "card";
$data["customer_email"] = $_POST['email'];
$data["client_reference_id"] = $_POST['token'] . ':' . $token . ':' . $_POST['first_name']  . ':' . $_POST['last_name'];
$data["success_url"] = $return_url . $token;
$data["cancel_url"] = $cancel_url;


$cart = $functions->getShopifyCart($_POST['token']);

for ($i = 1; $i <= count($cart["line_items"]); $i++) {

    $response = $functions->getRequestShopify('/admin/api/2020-04/products/' . $cart["line_items"][$i-1]["product_id"] . '/images.json');
    $image_src = json_decode($response, true)["images"][0]["src"];

    $data["line_items[" . ($i-1) . "][name]"] = $cart["line_items"][$i-1]["title"];
    $data["line_items[" . ($i-1) . "][images][]"] = $image_src;
    $data["line_items[" . ($i-1) . "][amount]"] = ($cart["line_items"][$i-1]["price"]) * 100;
    $data["line_items[" . ($i-1) . "][currency]"] = strtolower($currency);
    $data["line_items[" . ($i-1) . "][quantity]"] = $cart["line_items"][$i-1]["quantity"];

}
$result = $functions->postRequestStripe('/v1/checkout/sessions', http_build_query($data));

$session_id = json_decode($result, true)["id"];
?>

<script src="https://js.stripe.com/v3/"></script>
<script>
    var stripe = Stripe('<?php echo($functions->stripe_public); ?>');
    stripe.redirectToCheckout({
    sessionId: '<?php echo($session_id); ?>'
    }).then(function (result) {
    });
</script>
