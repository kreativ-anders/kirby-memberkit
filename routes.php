<?php

/*
  ROUTES
  ----
  https://getkirby.com/docs/reference/plugins/extensions/routes

*/
   
return function ($kirby) {
  return [
    
    // CREATE STRIPE CHECKOUT SESSION
    [
      // PATTERN --> CHECKOUT SLAG / ACTION NAME (SUBSCRIBE) / STRIPE TIER NAME
      'pattern' => Str::lower(option('kreativ-anders.memberkit.checkoutSlag')) . '/subscribe/(:all)',
      'action' => function ($tier) {

        if (!kirby()->user()) {go();};

        $tier = rawurldecode($tier);
        $tierIndex = array_search($tier, array_map("Str::lower", array_column(option('kreativ-anders.memberkit.tiers'), 'name')), false);
        $price = option('kreativ-anders.memberkit.tiers')[$tierIndex]['price'];

        $successURL  = kirby()->site()->url() . '/';
        $successURL .= Str::lower(option('kreativ-anders.memberkit.checkoutSlag')) . '/';
        $successURL .= Str::lower(rawurlencode($tier)) . '/success';

        $customer = kirby()->user()->stripe_customer();

        try {

          // STRIPE CHECKOUT SESSION
          $stripe = new \Stripe\StripeClient(option('kreativ-anders.memberkit.secretKey'));
          $checkout = $stripe->checkout->sessions->create([
            'success_url' => $successURL,
            'cancel_url' => option('kreativ-anders.memberkit.cancelURL'),
            'payment_method_types' => ['card'],
            'allow_promotion_codes' => true,
            'line_items' => [
              [
                'price' => $price,
                'quantity' => 1,
              ],
            ],
            'mode' => 'subscription',
            'customer' => kirby()->user()->stripe_customer(),
          ]);
      
        } catch(Exception $e) {
        
          // LOG ERROR SOMEWHERE !!!
        }     

        return [
          'id' => $checkout->id
        ];
      }
    ],
    // CREATE STRIPE CUSTOMER PORTAL SESSION
    [
      // PATTERN --> CHECKOUT SLAG / STRIPE PORTAL
      'pattern' => Str::lower(option('kreativ-anders.memberkit.checkoutSlag')) . '/portal',
      'action' => function () {

        if (!kirby()->user()) {go();};

        $customer = kirby()->user()->stripe_customer();

        $returnURL  = kirby()->site()->url() . '/';
        // $returnURL .= Str::lower(option('kreativ-anders.memberkit.checkoutSlag'));
        // $returnURL .= '/portal/update';

        try {

          // STRIPE PORTAL SESSION
          $stripe = new \Stripe\StripeClient(option('kreativ-anders.memberkit.secretKey'));
          $session = $stripe->billingPortal->sessions->create([
            'customer' => $customer,
            'return_url' => $returnURL,
          ]);

          $url = $session->url;
          
      
        } catch(Exception $e) {
        
          // LOG ERROR SOMEWHERE !!!
        }     

        return go($url);
      }
    ],
    // CANCEL STRIPE SUBSCRIPTION
    [
      // PATTERN --> CHECKOUT SLAG / ACTION NAME (CANCEL) / STRIPE TIER NAME
      'pattern' => Str::lower(option('kreativ-anders.memberkit.checkoutSlag')) . '/cancel/(:all)',
      'action' => function ($tier) {

        if (!kirby()->user()) {go();};

        $subscription = kirby()->user()->stripe_subscription();
        $email = kirby()->user()->email();

        try {

          // CANCEL STRIPE SUBSCRIPTION
          $stripe = new \Stripe\StripeClient(option('kreativ-anders.memberkit.secretKey'));
          $stripe->subscriptions->cancel(
            $subscription,
            []
          );

          // SUBSCRIPTION STATUS WILL BE "CANCELED"
          kirby()->user($email)->update([
            'stripe_subscription' => null,
            'stripe_status' => null,
            'tier' => option('kreativ-anders.memberkit.tiers')[0]['name']
          ]);
                
        } catch(Exception $e) {
        
          // LOG ERROR SOMEWHERE !!!
        }     

        return go();
      }
    ],
    // UPDATE USER AFTER SUCCESSFUL CHECKOUT
    [
      // PATTERN --> CHECKOUT SLAG / TIER NAME / success
      'pattern' => Str::lower(option('kreativ-anders.memberkit.checkoutSlag')) . '/(:all)/success',
      'action' => function ($tier) {

        if (!kirby()->user()) {go();};

        $subscription = null;

        /*
        
          Maybe there is more dynamic approach without directly checking the config array!!!
        */

        switch ($tier) {
          case Str::lower(option('kreativ-anders.memberkit.tiers')[1]['name']):
            $tier = option('kreativ-anders.memberkit.tiers')[1]['name'];
            break;

          case Str::lower(option('kreativ-anders.memberkit.tiers')[2]['name']):
            $tier = option('kreativ-anders.memberkit.tiers')[2]['name'];
            break;
          
          default:
            $tier = option('kreativ-anders.memberkit.tiers')[0]['name'];
            break;
        }

        try {

          $customer = kirby()->user()->retrieveStripeCustomer();
          $subscription = $customer->subscriptions['data'][0];
          

          // UPDATE KIRBY USER - FREE TIER 0
          kirby()->user()->update([
            'stripe_subscription' => $subscription->id,
            'stripe_status' => $subscription->status,
            'tier' => $tier
          ]);

                      
      
        } catch(Exception $e) {
        
          // LOG ERROR SOMEWHERE !!!

          $subscription = $e;
        }       

        return go(option('kreativ-anders.memberkit.successURL'));
      }
  ];
};

