<?php

// INCLUDE EXTERNAL LIBRARIES
include_once __DIR__ . '/lib/stripe/init.php';

Kirby::plugin('kreativ-anders/memberkit', [

  'options'     => include __DIR__ . '/options.php',
  'snippets'    => include __DIR__ . '/snippets.php',
  'hooks'       => include __DIR__ . '/hooks.php',
  'routes'      => include __DIR__ . '/routes.php',
  'userMethods' => include __DIR__ . '/userMethods.php',
  'siteMethods' => [
    'updateStripeSubscriptionWebhook' => function ($subscription) {

      $stripe = new \Stripe\StripeClient(option('kreativ-anders.memberkit.secretKey'));

      $customer = $stripe->customers->retrieve(
        $subscription->customer,
        []
      );

      $price = $subscription->items['data'][0]->price->id;
      $priceIndex = array_search($price, array_column(option('kreativ-anders.memberkit.tiers'), 'price'), false);

      $tier = option('kreativ-anders.memberkit.tiers')[$priceIndex]['name'];

      $kirby = kirby();
      $kirby->impersonate('kirby');

      kirby()->user($customer->email)->update([
        'stripe_subscription' => $subscription->id,
        'stripe_status' => $subscription->status,
        'tier' => $tier
      ]);

      $kirby->impersonate();    
    },
    'cancelStripeSubscriptionWebhook' => function ($subscription) {

      $stripe = new \Stripe\StripeClient(option('kreativ-anders.memberkit.secretKey'));

      $customer = $stripe->customers->retrieve(
        $subscription->customer,
        []
      );

      $price = $subscription->items['data'][0]->price->id;
      $priceIndex = array_search($price, array_column(option('kreativ-anders.memberkit.tiers'), 'price'), false);

      $tier = option('kreativ-anders.memberkit.tiers')[$priceIndex]['name'];

      $kirby = kirby();
      $kirby->impersonate('kirby');

      kirby()->user($customer->email)->update([
        'stripe_subscription' => null,
        'stripe_status' => null,
        'tier' => option('kreativ-anders.memberkit.tiers')[0]['name']
      ]);

      $kirby->impersonate();    
    }
]

]);


?>