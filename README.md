# Kirby Memberkit Plug-In (Pre-Release)


## What do you get?
A Kirby User Membership Plug-In (Pre-Release) powered by Stripe for Kirby CMS.

**Functionality** | **Comment**
---- | ----
Tiers| Endless
Create Stripe User | Kirby User Hook 
Subscribtion | Stripe Checkout
Change Subscribtion | Not yet
Cancel Subscribtion | Nope
User Privileges | Kirby User Method
Error Handling | under construction
CSS | Nope
Logic | Kirby Routes
Felixibility | Hell yeah!

## Installation:
1. Unzip the files.
1. Paste inside _../site/plugins/_.
1. Head over to **Get Started**.

## Get Started:

### Set configs

````php
'kreativ-anders.stripekit.secretKey'    => 'sk_test_xxxx',
'kreativ-anders.stripekit.publicKey'    => 'pk_test_xxxx',
'kreativ-anders.stripekit.checkoutSlag' => 'stripe-checkout',
'kreativ-anders.stripekit.successURL'   => 'https://*DOMAIN*/success',
'kreativ-anders.stripekit.cancelURL'    => 'https://*DOMAIN*/cancel',
'kreativ-anders.stripekit.tiers'        => [
  [ 'name'  => 'Free'
   ,'price' => null],
  [ 'name'  => 'Basic'
   ,'price' => 'price_xxxx'],
  [ 'name'  => 'Premium'
   ,'price' => ''],
],
````

>The tier array need to be in an ordered sequence! => Lowest tier first and highest tier last.

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
  The user will NOT see the content, since "Premium - Biannual" is greater than "Premium - Monthly" from a order persepctive.
  So make sure to always use the lower tier name for comparissons to ensure all Premium users independent from their payment interval will able to see/use the content/functionality behind!
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

## Support

In case this Add-On saved you some time and energy consider supporting kreativ-anders by purchasing the latest release on [Gumroad](https://gumroad.com/l/MFhDM), donating via [PayPal](https://paypal.me/kreativanders), or becoming a **GitHub Sponsor**.
