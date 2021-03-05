<?php

/*
  SITE-METHODS
  ----
  https://getkirby.com/docs/reference/plugins/extensions/site-methods
*/

return [

  // UPDATE STRIPE SUBSCRIPTION VIA WEBHOOK ------------------------------------------------------------------------------------------
  'updateStripeSubscriptionWebhook' => function ($subscription) {

    $stripe = new \Stripe\StripeClient(option('kreativ-anders.memberkit.secretKey'));

    try {

      // RETRIEVE STRIPE CUSTOMER 
      $customer = $stripe->customers->retrieve(
        $subscription->customer,
        []
      );

    } catch (Exception $e) {
      
      // LOG ERROR SOMEWHERE
      throw new Exception('Could not retrieve stripe customer!');
    }    

    // DETERMINE TIER NAME BY STRIPE PRICE ID
    $price = $subscription->items['data'][0]->price->id;
    $priceIndex = array_search($price, array_column(option('kreativ-anders.memberkit.tiers'), 'price'), false);
    $tier = option('kreativ-anders.memberkit.tiers')[$priceIndex]['name'];

    $kirby = kirby();
    $kirby->impersonate('kirby');

    try {

      // UPDATE KIRBY USER SUBSCRIPTION INFORMATION
      kirby()->user($customer->email)->update([
        'stripe_subscription' => $subscription->id,
        'stripe_status' => $subscription->status,
        'tier' => $tier
      ]);

    } catch (Exception $e) {
      
      // LOG ERROR SOMEWHERE
      throw new Exception('Could not update kirby user!');
    }
    
    $kirby->impersonate();        
  },
  // CANCEL STRIPE SUBSCRIPTION VIA WEBHOOK -----------------------------------------------------------------------------------------
  'cancelStripeSubscriptionWebhook' => function ($subscription) {

    $stripe = new \Stripe\StripeClient(option('kreativ-anders.memberkit.secretKey'));

    // GET STRIPE CUSTOMER
    $customer = $stripe->customers->retrieve(
      $subscription->customer,
      []
    );

    // DETERMINE TIER NAME BY STRIPE PRICE ID
    $price = $subscription->items['data'][0]->price->id;
    $priceIndex = array_search($price, array_column(option('kreativ-anders.memberkit.tiers'), 'price'), false);
    $tier = option('kreativ-anders.memberkit.tiers')[$priceIndex]['name'];

    $kirby = kirby();
    $kirby->impersonate('kirby');

    try {
      
      // RESET KIRBY USER SUBSCRIPTION INFORMATION
      kirby()->user($customer->email)->update([
        'stripe_subscription' => null,
        'stripe_status' => null,
        'tier' => option('kreativ-anders.memberkit.tiers')[0]['name']
      ]);

    } catch (Exception $e) {
      
      // LOG ERROR SOMEWHERE
      throw new Exception('Could not reset kirby user!');
    } 

    $kirby->impersonate();    
  },
  // UPDATE KIRBY USER EMAIL VIA STRIPE WEBHOOK --------------------------------------------------------------------------------------
  'updateStripeEmailWebhook' => function ($customer) {

    $kirby = kirby();
    $kirby->impersonate('kirby');

    try {
      
      // UPDATE KIRBY USER EMAIL
      $kirby->users()->findBy('stripe_customer', $customer->id)->changeEmail($customer->email);

    } catch (Exception $e) {
      
      // LOG ERROR SOMEWHERE
      throw new Exception('Could not change kirby user email!');
    } 

    $kirby->impersonate();    
  }

];