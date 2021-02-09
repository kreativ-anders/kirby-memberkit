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
      // PATTERN --> CHECKOUT SLAG / TIER NAME / STRIPE CUSTOMER / STRIPE BASIC TIER PRICE
      'pattern' => Str::lower(option('kreativ-anders.stripekit.checkoutSlag')) . '/(:all)/(:all)/(:all)',
      'action' => function ($tier, $user, $price) {

        $successURL  = kirby()->site()->url() . '/';
        $successURL .= Str::lower(option('kreativ-anders.stripekit.checkoutSlag')) . '/';
        $successURL .= Str::lower($tier) . '/success';

        try {

          // STRIPE CHECKOUT SESSION
          $stripe = new \Stripe\StripeClient(option('kreativ-anders.stripekit.secretKey'));
          $checkout = $stripe->checkout->sessions->create([
            'success_url' => $successURL,
            'cancel_url' => option('kreativ-anders.stripekit.cancelURL'),
            'payment_method_types' => ['card'],
            'line_items' => [
              [
                'price' => base64_decode($price),
                'quantity' => 1,
              ],
            ],
            'mode' => 'subscription',
            'customer' => base64_decode($user),
          ]);
      
        } catch(Exception $e) {
        
          // LOG ERROR SOMEWHERE !!!
        }     

        return [
          'id' => $checkout->id,
        ];
      }
    ],
    // UPDATE USER AFTER SUCCESSFUL CHECKOUT
    [
      // PATTERN --> CHECKOUT SLAG / TIER NAME / success
      'pattern' => Str::lower(option('kreativ-anders.stripekit.checkoutSlag')) . '/(:all)/success',
      'action' => function ($tier) {

        $subscription = null;

        /*
        
          Maybe there is more dynamic approach without directly checking the config array!!!
        */

        switch ($tier) {
          case Str::lower(option('kreativ-anders.stripekit.tiers')[1]['name']):
            $tier = option('kreativ-anders.stripekit.tiers')[1]['name'];
            break;

          case Str::lower(option('kreativ-anders.stripekit.tiers')[2]['name']):
            $tier = option('kreativ-anders.stripekit.tiers')[2]['name'];
            break;
          
          default:
            $tier = option('kreativ-anders.stripekit.tiers')[0]['name'];
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

        return go(option('kreativ-anders.stripekit.successURL'));
      }
    ]
  ];
};

