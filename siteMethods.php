<?php

/*
  SITE-METHODS
  ----
  https://getkirby.com/docs/reference/plugins/extensions/site-methods
*/

return [

  // UPDATE STRIPE SUBSCRIPTION VIA WEBHOOK
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
  // CANCEL STRIPE SUBSCRIPTION VIA WEBHOOK
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
  },
  // UPDATE KIRBY USER EMAIL VIA STRIPE WEBHOOK
  'updateStripeEmailWebhook' => function ($customer) {

    $kirby = kirby();
    $kirby->impersonate('kirby');

    $kirby->users()->findBy('stripe_customer', $customer->id)->changeEmail($customer->email);

    $kirby->impersonate();    
  }

];