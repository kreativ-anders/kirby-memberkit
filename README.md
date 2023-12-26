![kirby-memberkit](https://socialify.git.ci/kreativ-anders/kirby-memberkit/image?description=1&language=1&logo=https%3A%2F%2Fstatic-assets.kreativ-anders.de%2Flogo%2Fdark.svg&pattern=Brick%20Wall&theme=Light)

# Kirby Memberkit Template

* [What do you get?](#what-do-you-get)
* [Functional Overview?](#function-overview)
  * [Logic Index](#logic-index)
* [Installation](#installation)
* [Get Started](#get-started)
* [Test](#test)
* [Examples](#examples)
* [Notes](#notes)
  * [Kirby CMS Licence](#kirby-cms-license)
* [Support](#support)  

## What do you get?
A versatile Kirby User Membership Template powered by [Stripe](https://stripe.com/) ([Checkout](https://stripe.com/payments/checkout) + [Customer Portal](https://stripe.com/blog/billing-customer-portal)) for [Kirby CMS](https://getkirby.com). This repository contains a git submodule which makes it working as a submodule itself more complex than it should be.

## Function Overview:

**Function** | **Trigger** | **Logic** | **Comment**
:---- | :---- | :---- | :----
Create stripe product(s) | Manual | [Stripe Products Dashboard](https://dashboard.stripe.com/products) | Here, you also add the prices inclusively the payment intervals (subscriptions), e.g. 1€/Month or 10€/Year. 
Configure subscription tier(s) | Manual | [Kirby Options](https://getkirby.com/docs/guide/configuration#using-options) | Every price you create yield a distinct API-ID that is required in your kirby config.php. ([Learn more about subscription tiers](#set-subscription-tiers))
Create stripe user(s) | Automatic | [Kirby Hooks](https://getkirby.com/docs/reference/system/options/hooks) | Creates a stripe customer and store the stripe customer id (*stripe_customer*) and the root subscription tier name (*tier*) in the Kirby user information.
Migrate existing user(s) | Manual | [Kirby Users methods](https://getkirby.com/docs/reference/plugins/extensions/users-methods) | Only an admin can execute the migration.
Update stripe user(s) email | Automatic | [Kirby Hooks](https://getkirby.com/docs/reference/system/options/hooks) | 
Delete stripe user(s) | Automatic | [Kirby Hooks](https://getkirby.com/docs/reference/system/options/hooks) | The customer's billing information will be permanently removed from stripe and all current subscriptions will be immediately canceled. But processed payments and invoices associated with the customer will remain. 
Subscribe user(s) | Manual/Automatic | [Kirby User methods](https://getkirby.com/docs/reference/plugins/extensions/user-methods), [Kirby Routes](https://getkirby.com/docs/guide/routing), [Kirby Snippets](https://getkirby.com/docs/guide/templates/snippets) | You can generate a distinct URL for a specific tier (with the corresponding payment interval) and pass it to the snippet which creates the checkout button (The button also includes the required stripe JavaScript). On click, the route generates a dedicated session and redirects to stripe checkout page. After successful checkout, an in-between route handles the merge of the stripe user with the Kirby user.
Manage user(s) subscription | Automatic | [Stripe Customer Portal](https://stripe.com/blog/billing-customer-portal), [Kirby User methods](https://getkirby.com/docs/reference/plugins/extensions/user-methods) | Actions that are allowed to be performed can be set in [Stripe Customer Portal Dashboard](https://dashboard.stripe.com/settings/billing/portal), e.g. payment interval or change email address. Most of the actions should be working, but do not activate the option to change the quantity at the moment.
Check user(s) permission | Manual/Automatic | [Kirby User methods](https://getkirby.com/docs/reference/plugins/extensions/user-methods) | Compare the parameter ($tier) with the users' tier based on the index of the tiers config order.
Cancel user(s) subscription | Automatic | [Stripe Customer Portal](https://stripe.com/blog/billing-customer-portal), [Kirby Routes](https://getkirby.com/docs/guide/routing), [Kirby User methods](https://getkirby.com/docs/reference/plugins/extensions/user-methods) | For debug purposes it is also possible to cancel a user subscription by redirecting to an URL (works only in debug mode). The best approach is to redirect the user to the stripe customer portal.
Keep everything in sync | Automatic | [Stripe Customer Portal](https://stripe.com/blog/billing-customer-portal), [Kirby Routes](https://getkirby.com/docs/guide/routing), [Kirby Site methods](https://getkirby.com/docs/reference/plugins/extensions/site-methods) | Changes within the [Stripe Customer Portal](https://stripe.com/blog/billing-customer-portal) are communicated via webhook notifications from stripe to a defined route that performs the corresponding actions. 

### Logic Index

**Kirby Logic** | **Abstract** |  **Comment**
:---- | :---- | :----
Options | | Jump to [config.php in Get Started](#configphp) or check [options.php](https://github.com/kreativ-anders/kirby-memberkit/blob/main/options.php)
Hooks | [user.create:after](https://getkirby.com/docs/reference/plugins/hooks/user-create-after), [user.delete:after](https://getkirby.com/docs/reference/plugins/hooks/user-delete-after), [user.changeEmail:after](https://getkirby.com/docs/reference/plugins/hooks/user-changeemail-after), [route:before](https://getkirby.com/docs/reference/plugins/hooks/route-before)
User methods | getStripeCancelURL(), getStripeWebhookURL(), getStripeCheckoutURL(), getStripePortalURL(), retrieveStripeCustomer(), mergeStripeCustomer(), isAllowed($tier) | Check [userMethods.php](https://github.com/kreativ-anders/kirby-memberkit/blob/main/userMethods.php)
Users methods | migrateStripeCustomers()| Check [usersMethods.php](https://github.com/kreativ-anders/kirby-memberkit/blob/main/usersMethods.php)
Site methods | updateStripeSubscriptionWebhook($subscription), cancelStripeSubscriptionWebhook($subscription), updateStripeEmailWebhook($customer) | Check [siteMethods.php](https://github.com/kreativ-anders/kirby-memberkit/blob/main/siteMethods.php)
Routes | | Check [routes.php](https://github.com/kreativ-anders/kirby-memberkit/blob/main/routes.php) 

#### Why no (Kirby) API?
Kirby API is very restrictive, which is good on one hand. But, on the other hand, it requires the user to have **panel access** permission what is IMHO not in your favor. So using routes is so for the workaround for certain tasks. This also applies to stripe webhooks. API calls to Kirby need to be authenticated which does not comply with stripe webhooks calls.

## Installation:

> The assets/code you can download on GitHub website does not include stripe´s PHP library on default (since it is a git submodule)! Either use the latest release download link below or install the Plug-In as a git submodule as well.

### Download
1. Download the [**latest release** .zip-file (_kirby-memberkit-vX.X.X.zip_)](https://github.com/kreativ-anders/kirby-memberkit/releases).
1. Unzip the files.
1. Paste inside _../site/plugins/_.
1. Head over to **[Get Started](#get-started)**.

### Git Submodule (Recommended)
You can add the Kirby Memberkit Plug-In as a git submodule as well:
````bash
$ cd YOUR/PROJECT/ROOT
$ git submodule add https://github.com/kreativ-anders/kirby-memberkit.git site/plugins/kirby-memberkit
$ git submodule update --init --recursive
$ git commit -am "Add Kirby Memberkit"
````
Run these commands to update the Plug-In (and all other submodules):
> **main** not "master"!
````bash
$ cd YOUR/PROJECT/ROOT
$ git submodule foreach git checkout main
$ git submodule foreach git pull
$ git commit -am "Update submodules"
$ git submodule update --init --recursive
````

## Get Started:

> Before diving deep, become familiar with [Stripe Checkout](https://stripe.com/de/payments/checkout), check the respective Docs of [Stripe Checkout](https://stripe.com/docs/payments/checkout) and check out [Stripe Customer Portal](https://stripe.com/docs/billing/subscriptions/customer-portal).

### Stripe Dashboard

* [Create Products on Stripe](https://dashboard.stripe.com/products)
  * Add **prices** to the product(s)
* [Configure Stripe Customer Portal](https://dashboard.stripe.com/settings/billing/portal)
* [Set up an endpoint for Stripe Webhooks](https://dashboard.stripe.com/webhooks)
  * The (default) URL looks like "https://YOUR-DOMAIN.TLD/stripe-checkout/webhook" (See [Overwrite stripe URL slug (optional)](#overwrite-stripe-url-slug-optional) to change "stripe-checkout".)

### config.php

#### Set stripe API keys
````php
'kreativ-anders.memberkit.secretKey'     => 'sk_test_xxxx',
'kreativ-anders.memberkit.publicKey'     => 'pk_test_xxxx',
````
#### Overwrite stripe URL slug (optional)
This setting is just an additional layer to create collision-free routes/URLs like "https://YOUR-DOMAIN.TLD/stripe-checkout/portal"
````php
'kreativ-anders.memberkit.stripeURLSlug' => 'stripe-checkout',
````
#### Set cancel/success URLs
Those pages do not exist! You need to create them by yourself. This is a great opportunity to welcome users after they successfully subscribed to a tier or show them help when they canceled the stripe checkout process.

> use absolute paths here. Not really happy with the current solution btw.

````php
'kreativ-anders.memberkit.successURL'    => 'https://YOUR-DOMAIN.TLD/success',
'kreativ-anders.memberkit.cancelURL'     => 'https://YOUR-DOMAIN.TLD/cancel',
````
#### Set stripe webhook secret
To keep everything (securely) in sync it is important to set a webhook secret. 
````php
'kreativ-anders.memberkit.webhookSecret' => 'whsec_xxx',
````

#### Set subscription tiers
The subscription tier is a 2D-array and needs to be in an ordered sequence. This means the lowest tier is first (Free) and the highest tier in the end (Premium). The first index is always the entry/default tier after registration/cancelation. 

> Due to consistency the tier on index 0 holds a price, but it is never checked, so keep it null. Again, all the following tiers need to be greater than the previous one, e.g., FREE --> BASIC --> PREMIUM --> SUPER DELUXE.

You also have to maintain **all** price API-IDs (payment intervals) within one product that have been created within stripe dashboard. This enables you to create mutliple payment intervals that look like the following in the config.php:

##### Basic Example
````php
'kreativ-anders.memberkit.tiers'         => [
  [ 'name'  => 'Free'
   ,'price' => null],
  [ 'name'  => 'Basic'
   ,'price' => 'price_xxxx'],
  [ 'name'  => 'Premium'
   ,'price' => 'price_yyyy'],
],
````
##### Creative (Crazy) Example

> I hope nobody will ever dare to do something like the following!

````php
'kreativ-anders.memberkit.tiers'         => [
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
    [ 'name'  => 'Deluxe - Custom'
     ,'price' => 'price_yyyopq'],
  ],
````

## Test:

### Local

For local tests use the [Stripe CLI](https://stripe.com/docs/stripe-cli). There is also a very handy [Extension for VS Code](https://stripe.com/docs/stripe-vscode) available.

> **Note**
> Authenticate your Stripe CLI with your account. (Make sure you are logged in with your stripe account into the desired app in your default browser.)

````shell
C:/PATH/TO/stripe.exe login
````

> Use the following line of code to listen and forward stripe request to your local environment.

````shell
C:/PATH/TO/stripe.exe listen --forward-to http://YOUR-DOMAIN.TLD/stripe-checkout/webhook --forward-connect-to http://YOUR-DOMAIN.TLD/stripe-checkout/webhook
````
Afterward, the (VS Code) terminal prompts a line like this "Ready! Your webhook signing secret is **whsec_xxxx** (^C to quit)".

**Maintain the secret within your [config.php](#set-stripe-webhook-secret)!**

### Going Live

Head over to [Stripe´s Webhook Dashboard](https://dashboard.stripe.com/webhooks) and add a new endpoint for your application.
The URL should look like "https://YOUR-DOMAIN.TLD/stripe-checkout/webhook".
Finally, add the following events that are handled by this Plug-In:

- customer.subscription.updated
- customer.subscription.deleted
- customer.updated
- invoice.payment_failed

## Examples:

### Migrate existing users

Call the dedicated users method once somewhere, e.g. snippet:

````php
var_dump($kirby->users()->migrateStripeCustomers()); // TO SEE THE NUMBER OF MIGRATIONS
````

> For every kirby user, a stripe user will be created, so (personal) information is shared with stripe, e.g. email address.

### Create a stripe user

Stripe users are created automatically via hook after a successful user creation/registration. Check out the [Kirby Add-on Userkit](https://github.com/kreativ-anders/kirby-userkit) to allow self-registration and updates to kirby users.
The logic applies to users' email changes and user deletion as well.

### Add a stripe checkout button

1. Create a URL for a dedicated tier:

````php
$url = $kirby->user()->getStripeCheckoutURL(option('kreativ-anders.memberkit.tiers')[1]['name']); // Basic tier
````
You can also call the method with a hardcoded string:
````php
$url = $kirby->user()->getStripeCheckoutURL('Premium'); // Premium tier
````

> The URL is handled via a route and returns a JSON with the required stripe session ID.

2. Add the stripe checkout button (via snippet):
For styling or additional logic (with your JavaScript) you can pass an id, classes, and a text - whatever you want.

````php
snippet('stripe-checkout-button', [ 'id'      => 'basic-checkout-button'
                                   ,'classes' => ''
                                   ,'text'    => 'Basic Checkout'
                                   ,'url'     => $url]);
````

The snippet also includes the required JavaScript to initialize the checkout and redirect to Stripe itself. 

> Just be careful not to add the same id twice!

### Successful subscription checkout

In case the stripe checkout was successful, stripe redirects to a hidden URL (captured via another route internally) that handles the user update procedure, e.g., payment status or set the subscribed tier name. Afterward, the redirect to **YOUR** individual succuss page ([set in config.php](#set-cancelsuccess-urls)) is triggered. In case the user return via cancel command on stripe checkout, the user will be immediately redirected to **YOUR** individual cancel page (set in config.php as well).

### Manage stripe subscription

Changes regarding payment interval, subscription upgrades/downgrades, or even cancelation is **all covered** within the [Stripe Customer Portal](https://stripe.com/blog/billing-customer-portal). The only thing you have to do is showing the link to the portal somewhere somehow:

````php
$portal_url = $kirby->user()->getStripePortalURL();
$portal = '(link: ' . $portal_url . ' text: Stripe Portal URL target: _blank)';
echo kirbytext($portal);
````

### Change subscription

> Yeah, well ... Let´s get back to the [previous section about managing subscriptions](#manage-stripe-subscription).

### Cancel subscription

> Same! [Stripe Customer Portal](https://stripe.com/blog/billing-customer-portal) rules all of it!

For debug purposes only it is possible to cancel a subscription by simply redirecting to an URL.

````php
$cancel_url = $kirby->user()->getStripeCancelURL();
$cancel = '(link: ' . $cancel_url . ' text: Stripe Cancel URL)';
echo kirbytext($cancel);
````

### Show/Hide functions or content based on subscribed tier

Use the following condition somewhere in your templates or snippets:

````php
<?php if ($kirby->user() && $kirby->user()->isAllowed(option('kreativ-anders.memberkit.tiers')[1]['name'])): ?>
<p>
  Basic visible
</p>
<?php endif ?>
````

> The example code above would be the "safe" version of passing the correct tier name, but the following is more user-friendly...

````php
<?php if ($kirby->user() && $kirby->user()->isAllowed('Premium')): ?>
<p>
  Premium visible
</p>
<?php endif ?>
````

If you are using a construction with multiple pricing intervals for the same tier, make sure to use the first occurrence of your version for the comparison!

> Remember: The 2D-array of your tiers needs to be in an ascending sequence!

For illustration we assume an user with the tier **Premium - Monthly** (Look at the [creative (crazy) tier example above](#creative-crazy-example)):
````php
<?php if ($kirby->user() && $kirby->user()->isAllowed('Premium - Monthly')): ?>
<p>
  The user will see the content since the tier is matching exactly!
</p>
<?php endif ?>

<?php if ($kirby->user() && $kirby->user()->isAllowed('Premium - Biannual')): ?>
<p>
  The user will NOT see the content, since "Premium - Biannual" is greater than "Premium - Monthly" 
  from an order perspective. So make sure to always use the lower tier name for comparison to ensure all Premium users independent from their payment interval will able to see/use the content/functionality behind!
</p>
<?php endif ?>
````

### Overall snippet

Place this snippet somewhere in your template, e.g. intro.php.

````php
if ($kirby->user()) {

  $checkout_url = $kirby->user()->getStripeCheckoutURL(option('kreativ-anders.memberkit.tiers')[1]['name']); // Basic Tier
  $checkout = '(link: ' . $checkout_url . ' text: Stripe Checkout Callback-URL (Tier 1) = ' . $checkout_url . 'target: _blank)';
  echo kirbytext($checkout);

  echo "<br/>";
  echo "Checkout Button (Tier 1) = ";

  snippet('stripe-checkout-button', [ 'id'      => 'basic-checkout-button'
                                   ,'classes' => ''
                                   ,'text'    => 'Basic Checkout'
                                   ,'url'     => $checkout_url]);

  echo "<br/><br/>";
  echo "Stripe Customer Subscription Debug = ";
  echo "<pre>";

  $customer = $kirby->user()->retrieveStripeCustomer();
  $subscription = $customer->subscriptions['data'];
  var_dump($subscription);

  echo "</pre>";
  echo "<br/>";  
  
  $portal_url = $kirby->user()->getStripePortalURL();
  $portal = '(link: ' . $portal_url . ' text: Stripe Customer Portal URL = ' . $portal_url . ' target: _blank)';
  echo kirbytext($portal);

  echo "<br/>"; 

  // $cancel_url = $kirby->user()->getStripeCancelURL();
  // $cancel = '(link: ' . $cancel_url . ' text: Stripe Customer Subscription Cancel URL = ' . $cancel_url . ')';
  // echo kirbytext($cancel);

}
````

## Notes:
This Plug-In is built for Kirby CMS based on **Kirby´s Starterkit v3.8.2** with the Add-On **[kirby-userkit](https://github.com/kreativ-anders/kirby-userkit)** and **Stripe API Version 2022-11-15**.

### Kirby CMS license

**Kirby CMS requires a dedicated license:**

*Go to [https://getkirby.com/buy](https://getkirby.com/buy)*

## Warning:
Do not subscribe multiple tiers to a user. Even though this should not be possible with the Plug-In by default (since the snippet "stripe-checkout-button" will check for subscriptions), be aware not to do it within the stripe dashboard anyway!
Use with caution and test before of course.

## Disclaimer

The source code is provided "as is" with no guarantee. Use it at your own risk and always test it yourself before using it in a production environment. If you find any issues, please create a new issue.

## Support

In case this Plug-In saved you some time and energy consider supporting kreativ-anders by donating via [PayPal](https://paypal.me/kreativanders), or becoming a **GitHub Sponsor**.
