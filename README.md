# Kirby Memberkit Plug-In (Pre-Release)


## What do you get?
A Kirby User Membership Plug-In (Pre-Release) powered by Stripe for Kirby CMS.

**Functionality** | **Comment**
---- | ----
Tiers| Endless
Create Stripe User | Kirby User Hook 
Subscribtion | Stripe Checkout
Change Subscribtion | Not yet
Cancel Subscribtion | Nope (Users are trapped 😅)
User Privileges | Kirby User Method
Error Handling | under construction
CSS | Nope
Logic | Kirby Routes
Felixibility | Hell yeah!

*See Gumroad link below, to check out some screenshots.*

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

> This would be the safe version of passing the correct tier name, but this one is more developer friendly...

````php
<?php if ($kirby->user() && $kirby->user()->isAllowed('Premium')): ?>
<p>
  Premium visible
</p>
<?php endif ?>
````

## Notes:
This Plug-In is built for Kirby CMS based on **Kirby´s Starterkit** with the Add-On **[kirby-userkit](https://github.com/kreativ-anders/kirby-userkit)** for easy front end user creation.

## Warning:
Do not subscribe multiple tiers to a user. Even though this should not be possible with the Plug-In, be aware not to do it within the stripe dashboard anyway!
Use with caution and test before of course.

**Kirby CMS requires a dedicated licence:**

*Go to https://getkirby.com/buy*

## Support

In case this Add-On saved you some time and energy consider supporting kreativ-anders by purchasing the latest release on [Gumroad](https://gumroad.com/l/MFhDM), donating via [PayPal](https://paypal.me/kreativanders), or becoming a **GitHub Sponsor**.
