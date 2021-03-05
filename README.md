# Kirby Memberkit Plug-In (Pre-Release)

* [What do you get?](#what-do-you-get)
* [Why an Add-On?](#why-an-add-on)
* [Installation](#installation)
* [Notes](#notes)
    * [Kirby CMS Licence](#kirby-cms-licence)
* [Support](#support)  

## What do you get?
A versatile Kirby User Membership Plug-In (Pre-Release) powered by [Stripe](https://stripe.com/) for Kirby CMS.


## Abstract Overview:

**Function** | **Trigger** | **Logic** | **Comment**
---- | ---- | ---- | ----
Create stripe product(s) | Manual | [Stripe Products Dashboard](https://dashboard.stripe.com/products) | Here, you also add the prices inclusively the payment intervals (subscriptions), e.g. 1€/Month or 10€/Year. 
Configure subscription tier(s) | Manual | [Kirby Options](https://github.com/kreativ-anders/kirby-memberkit/blob/main/options.php) | Every price you create yield a distinct API-ID that is required in your kirby config.php. ([Learn more about subscription tiers](#subscription-tiers))
Create stripe user(s) | Automatic | [Kirby Hooks: user.create:after](https://github.com/kreativ-anders/kirby-memberkit/blob/main/hooks.php) | Creates a stripe customer and store the stripe customer id (*stripe_customer*) and the root subscription tier name (*tier*) in the kirby user information.
Update stripe user(s) email | Automatic | [Kirby Hooks: user.changeEmail:after](https://github.com/kreativ-anders/kirby-memberkit/blob/main/hooks.php) | 
Delete stripe user(s) | Automatic | [Kirby Hooks: user.delete:after](https://github.com/kreativ-anders/kirby-memberkit/blob/main/hooks.php) | The customer's billing information will be permanently removed from stripe and all current subscriptions will be immediately cancelled. But processed payments and invoices associated with the customer will remain. 
Subscribe user(s) | Manual/Automatic | [Kirby User methods: getStripeCheckoutURL($tier) mergeStripeCustomer()](https://github.com/kreativ-anders/kirby-memberkit/blob/main/userMethods.php) [Kirby Routes](https://github.com/kreativ-anders/kirby-memberkit/blob/main/routes.php) [Kirby Snippets](https://github.com/kreativ-anders/kirby-memberkit/blob/main/snippets/stripe-checkout-button.php) | You can generate a distinct URL for a specific tier (with the according payment interval) and pass it to the snippet which creates the checkout button. On click the route generates a dedicated session and redirects to stripe checkout page. After successful checkout an inbetween route handles the merge of the stripe user with the kirby user.
Manage user(s) subscription | Automatic | [Stripe Customer Portal](https://stripe.com/blog/billing-customer-portal) [Kirby User methods: getStripePortalURL()](https://github.com/kreativ-anders/kirby-memberkit/blob/main/userMethods.php) | Actions that are allowed to be performed can be set in [Stripe Customer Portal Dashboard](https://dashboard.stripe.com/settings/billing/portal), e.g. payment interval or change email address. Most of the actions should be working, but do not activate the option to change the quantity at the moment.
Check user(s) permission | Manual/Automatic | [Kirby User methods: isAllowed($tier)](https://github.com/kreativ-anders/kirby-memberkit/blob/main/userMethods.php) | Compare the parameter ($tier) with the users tier based on the index of the tiers config order.
Cancel user(s) subscription | Automatic | [Stripe Customer Portal](https://stripe.com/blog/billing-customer-portal) [Kirby Routes](https://github.com/kreativ-anders/kirby-memberkit/blob/main/routes.php) [Kirby User methods: getStripeCancelURL()](https://github.com/kreativ-anders/kirby-memberkit/blob/main/userMethods.php) | For debug purposes it is also possible to cancel a user subscription by redirecting to an URL (works only in debug mode). The best approach is to redirect the user to the stripe customer portal.
Keep everything in sync | Automatic | [Stripe Customer Portal](https://stripe.com/blog/billing-customer-portal) [Kirby Routes](https://github.com/kreativ-anders/kirby-memberkit/blob/main/routes.php) [Kirby Site methods: updateStripeSubscriptionWebhook($subscription) cancelStripeSubscriptionWebhook($subscription) updateStripeEmailWebhook($customer)](https://github.com/kreativ-anders/kirby-memberkit/blob/main/siteMethods.php) | Changes within the [Stripe Customer Portal](https://stripe.com/blog/billing-customer-portal) are comunicated via webhook notifications from stripe to a defined route that performs the corresponding actions. 


#### Why no API?
Kirby API is very restrictive, which is good on one hand. But, on the other hand it requires the user to have **panel access** what is imho not in your favor. So using routes is sofore the workaround for certain tasks. This alss applies to stripe webhooks. API calls to Kirby need to be authenticated which does not harmonize with stripe webhooks calls.

## Installation:

### Download
1. Unzip the files.
1. Paste inside _../site/plugins/_.
1. Head over to **Get Started**.

### Git Submodule
You can add the Kirby Memberkit plugin as a Git submodule as well.
````shell
$ cd your/project/root
$ git submodule add https://github.com/kreativ-anders/kirby-memberkit.git site/plugins/kirby-memberkit
$ git submodule update --init --recursive
$ git commit -am "Add Kirby Spreadsheet"
````
Run these commands to update the plugin (and all other submodules):
````shell
$ cd your/project/root
$ git submodule foreach git checkout master
$ git submodule foreach git pull
$ git commit -am "Update submodules"
$ git submodule update --init --recursive
````

## Get Started:

>Get familiar with [Stripe Checkout](https://stripe.com/de/payments/checkout) and also check the respective Docs of [Stripe Checkout](https://stripe.com/docs/payments/checkout) and [Stripe Customer Portal](https://stripe.com/docs/billing/subscriptions/customer-portal) before heading on .

### Set configs

````php
'kreativ-anders.stripekit.secretKey'     => 'sk_test_xxxx',
'kreativ-anders.stripekit.publicKey'     => 'pk_test_xxxx',
'kreativ-anders.stripekit.webhookSecret' => 'whsec_xxx',
'kreativ-anders.stripekit.checkoutSlag'  => 'stripe-checkout',
'kreativ-anders.stripekit.successURL'    => 'https://*DOMAIN*/success',
'kreativ-anders.stripekit.cancelURL'     => 'https://*DOMAIN*/cancel',
'kreativ-anders.stripekit.tiers'         => [
  [ 'name'  => 'Free'
   ,'price' => null],
  [ 'name'  => 'Basic'
   ,'price' => 'price_xxxx'],
  [ 'name'  => 'Premium'
   ,'price' => ''],
],
````


### Testing

For local testing start the stripe CLI and forward to your local machine:

````bash
C:\Path\to\stripe.exe listen --forward-to http://local.tld/*checkoutSlag*/webhook --forward-connect-to http://local.tld/*checkoutSlag*/webhook
````
Afterwards, the Terminal prompts line like this:
"Ready! Your webhook signing secret is whsec_xxxx (^C to quit)"

Maintain this code within your config.php:

````php
'kreativ-anders.stripekit.webhookSecret' => 'whsec_xxx',
````

### Going Live

Head over to [Stripe´s Webhook Dashboard](https://dashboard.stripe.com/webhooks) and add a new endpoint for your application.
The URL should look like "https://*YOUR_DOMAIN*.*TLD*/*CHECKOUTSLACK*/webhook".
Finally, add the following events that are handled by this Plug-In:

- customer.subscription.updated
- customer.subscription.deleted
- customer.updated

### Create stripe user

> Stripe users are created automaitcally via hook after a successful user creation / registration. 
The same applies for users email changes and users deletion!

### Create stripe checkout

1. Create URL for stripe checkout that returns the sessionID:

> This is handled via a route 😉 in the background. 

````php
$url = $kirby->user()->getStripeCheckoutURL( option('kreativ-anders.stripekit.tiers')[1]);
````

2. Create a stripe checkout button:

> This is handled via a snippet 😉. 

````php
snippet('stripe-checkout-button', [ 'id'      => 'basic-checkout-button'
                                   ,'classes' => ''
                                   ,'text'    => 'Basic Checkout'
                                   ,'url'     => $url]);
````

> The snippet also includes the required JavaScript to initialize the checkout and redirect to Stripe itself.

## Subscription Tiers

The subscription tier is an array and need to be in an ordered sequence! => Lowest tier first and highest tier last. The tiers config is a 2D-array that needs to be ordererd hierarchical. The first index is always the entry/default tier after registration. Due to consistency the tier on index 0 holds a price, but it is never ever checked, so keep it null. All the following tiers need to be greater than the one before, e.g., FREE --> BASIC --> PREMIUM --> SUPER DELUXE etc.

You have to maintain **all** price entries (intervals) within one product in your config.php file.

### Payment Intervals for subscriptions
The payment interval depends on the price_id within stripe. In case you are creating a product with a price X with an interval of every 6 months stripe checkout will adapt to this - that´s pretty neat imho. This enables you to create mutliple payment intervals that look like the following in the config.php:

````php
'kreativ-anders.memberkit.tiers' => [
    [ 'name'  => 'Free'
     ,'price' => null],
    [ 'name'  => 'Basic - Daily'
     ,'price' => 'price_xxxabc'],
    [ 'name'  => 'Basic - Weekly'
     ,'price' => 'price_xxxdef'],
    [ 'name'  => 'Premium - Monthly'
     ,'price' => 'price_yyyghi'],
    [ 'name'  => 'Premium - Biannual'
     ,'price' => 'price_yyyjkl'],
    [ 'name'  => 'Deluxe - Yearly'
     ,'price' => 'price_zzzmno'],
    [ 'name'  => 'Deluxe - Custom'    // BECOMING CREATIVE!
     ,'price' => 'price_yyyopq'],
  ],
````

###  Successful subscription

> In case the stripe checkout was successful, stripe redirects to a hidden URL (captured via another route internally) that handles the user update procedure, e.g., payment status or set the subscribed tier name. Afterward, the redirect to **YOUR** individual succuss page (set in config) is triggered.

### Cancel subscription

> In case the user return via cancel command on stripe checkout, the user will be immediately redirected to **YOUR** indiviudal cancel page (set in config as well).

### Change subscription

> Yeah, well ... Let´s get back on this later.

### Cancel subscription

> Same! Not possible at the moment.

### Show/Hide functions or text based on subscribed tier

````php
<?php if ($kirby->user() && $kirby->user()->isAllowed(option('kreativ-anders.stripekit.tiers')[1]['name'])): ?>
<p>
  Basic visible
</p>
<?php endif ?>
````

> This would be the safe version of passing the correct tier name, but this one is more user friendly...

````php
<?php if ($kirby->user() && $kirby->user()->isAllowed('Premium')): ?>
<p>
  Premium visible
</p>
<?php endif ?>
````

If you are using a construction with multiple pricing intervals for the same tier, make sure to use the first occurance of your version for the comparison!

For Instance a user with the tier **Premium - Monthly**:
````php
<?php if ($kirby->user() && $kirby->user()->isAllowed('Premium - Monthly')): ?>
<p>
  The user will see the content, since the tier is matching exactly.
</p>
<?php endif ?>

<?php if ($kirby->user() && $kirby->user()->isAllowed('Premium - Biannual')): ?>
<p>
  The user will NOT see the content, since "Premium - Biannual" is greater than "Premium - Monthly" 
  from a order persepctive. So make sure to always use the lower tier name for comparissons 
  to ensure all Premium users independent from their payment interval will able 
  to see/use the content/functionality behind!
</p>
<?php endif ?>
````

## Notes:
This Plug-In is built for Kirby CMS based on **Kirby´s Starterkit** with the Add-On **[kirby-userkit](https://github.com/kreativ-anders/kirby-userkit)** for easy front end user creation.

## Warning:
Do not subscribe multiple tiers to a user. Even though this should not be possible with the Plug-In by default, be aware not to do it within the stripe dashboard anyway!
Use with caution and test before of course.

**Kirby CMS requires a dedicated licence:**

*Go to https://getkirby.com/buy*

## Disclaimer

The source code is provided "as is" with no guarantee. Use it at your own risk and always test it yourself before using it in a production environment. If you find any issues, please create a new issue.

## Support

In case this Plug-In saved you some time and energy consider supporting kreativ-anders by donating via [PayPal](https://paypal.me/kreativanders), or becoming a **GitHub Sponsor**.
