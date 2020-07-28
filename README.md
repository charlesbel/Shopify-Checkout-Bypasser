# Shopify Payment Bypass
A script to bypass the Shopify integrated payment system and create test orders with live payments.
This script was write in order to bypass the 50 orders limit of the developpements stores on Shopify, so using this script, you can manage as many live orders as you want without paying a Shopify Plan.
# Installation
You'll have to edit all php files. :
- **paypal.php** : edit the three first lines with your shopify private app credentials, change $enableSandbox to false if you want to go to live, and edit the $paypalConfig with the paypal email where you want to receive money, the url of the redirect-order.php, the cancel page and the url of the paypal.php wich will receive the payment notification.
- **redirect-order.php** : edit the three first lines with your shopify private app credentials
- **stripe.php** : edit the first 4 lines with your stripe api keys, edit the following three lines with your shopify private app credentials, edit the url of the redirect-order.php and the cancel page. Edit also the $enableTest to true or false to pass to live or test mode.
- **webhook.php** : edit the three first lines with your shopify private app credentials
Then host your php files somewhere else as Shopify.
You'll have now to copy the content of form.html and copy the content in your Shopify cart template (make sure to also modify the path to the php files in the html code).
