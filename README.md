[![ForTheBadge built-by-developers](http://ForTheBadge.com/images/badges/built-by-developers.svg)](https://github.com/charlesbel/Shopify-Checkout-Bypasser/)
[![ForTheBadge uses-git](http://ForTheBadge.com/images/badges/uses-git.svg)](https://GitHub.com/)
[![ForTheBadge built-with-love](http://ForTheBadge.com/images/badges/built-with-love.svg)](https://github.com/charlesbel/Shopify-Checkout-Bypasser/)

[![Maintenance](https://img.shields.io/badge/Maintained%3F-yes-green.svg)](https://github.com/charlesbel/Shopify-Checkout-Bypasser/graphs/commit-activity)
[![MIT license](https://img.shields.io/badge/License-MIT-blue.svg)](https://lbesson.mit-license.org/)
[![Average time to resolve an issue](http://isitmaintained.com/badge/resolution/charlesbel/Shopify-Checkout-Bypasser.svg)](http://isitmaintained.com/project/charlesbel/Shopify-Checkout-Bypasser "Average time to resolve an issue")
[![Percentage of issues still open](http://isitmaintained.com/badge/open/charlesbel/Shopify-Checkout-Bypasser.svg)](http://isitmaintained.com/project/charlesbel/Shopify-Checkout-Bypasser "Percentage of issues still open")
[![Open Source Love svg3](https://badges.frapsoft.com/os/v3/open-source.svg?v=103)](https://github.com/ellerbrock/open-source-badges/)

# Shopify Checkout Bypasser #
A script to bypass the Shopify integrated payment system and create test orders with live payments.
This script was write in order to bypass the 50 orders limit of the developpements stores on Shopify, so using this script, you can manage as many live orders as you want without paying a Shopify Plan.

# Installation #
First, choose your checkout domain. For exeample, if your store domain is store.com, I would recommend you to choose checkout.store.com as checkout domain. You'll configure it after.
### Shopify ###
Create a private app on your Shopify Store. Then store somewhere the app id, the app pass, and your shopify domain.
### PayPal ###
#### Apps ####
You'll have to create a sandbox and a live app on https://developer.paypal.com/developer/applications/

Tutorial : https://www.angelleye.com/how-to-create-paypal-app/

Then store somwhere the client id and the secret of the 2 apps.
#### WebHooks ####
Then create a webhook on the 2 apps. Select all events. The url for the webhook is the domain you choosed before with "/paypal/webhook". For exeample: https://checkout.store.com/paypal/webhook

Tutorial : https://developer.paypal.com/docs/api-basics/notifications/webhooks/rest/#subscribe-to-events

Then store somwhere the webhook id of the 2 webhooks.
### Stripe ###
Just create a live and a sandbox webhook with the following events :
```
invoice.payment_failed
customer.subscription.updated
checkout.session.completed
```
As for Paypal, the url for the webhook is the domain you choosed before with "/stripe/webhook". For exeample: https://checkout.store.com/stripe/webhook

Tutorial : https://www.wpcharitable.com/documentation/setting-up-a-stripe-webhook/

Then store somwhere the webhook secret of the 2 webhooks.
### Heroku ###
[![Deploy](https://www.herokucdn.com/deploy/button.svg)](https://heroku.com/deploy)

I would recommend to host the checkout on heroku because you have a free hosting.
If you choose heroku, just click on the button above.

You'll have to configure some env variables on your PHP Server (if you choosed heroku, it will automatically be asked on deploy):
- ENABLE_TEST: Enable the test payment on your checkout in order to test it
- CURRENCY: The currency code of the checkout
- LANG: The language tag of the checkout
- BRANDNAME: The brand name of your store
- SHOPIFY_APPID: Your Shopify app id
- SHOPIFY_APPPASS: Your Shopify app pass
- SHOPIFY_DOMAIN: The domain of your Shopify store
- SHOPIFY_URL_CANCEL: The url of the "Canceled Payment" page
- SHOPIFY_URL_RETURN: The url of the redirect-order.php
- STRIPE_LIVE_PUBLIC: Your Stripe live public api key
- STRIPE_LIVE_SECRET: Your Stripe live secret api key
- STRIPE_TEST_PUBLIC: Your Stripe test public api key
- STRIPE_TEST_SECRET: Your Stripe test secret api key
- STRIPE_LIVE_WEBHOOK_SECRET: Your Stripe live webhook secret
- STRIPE_TEST_WEBHOOK_SECRET: Your Stripe test webhook secret
- PAYPAL_LIVE_CLIENTID: Your PayPal App live client id
- PAYPAL_LIVE_SECRET: Your PayPal App live secret
- PAYPAL_SANDBOX_CLIENTID: Your PayPal App test client id
- PAYPAL_SANDBOX_SECRET: Your PayPal App test secret
- PAYPAL_LIVE_WEBHOOKID: The id of the live webhook
- PAYPAL_SANDBOX_WEBHOOKID: The id of the sandbox webhook
### Shopify Templates ###
Then, copy the paypal-button.liquid file indise your theme snippets. And then paste the cart-template code inside your cart-template.liquid of your theme where you want. Just edit formaction urls on line 125, 129, 146 and 156 of my cart-template.
