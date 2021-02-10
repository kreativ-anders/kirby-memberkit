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
      // PATTERN --> CHECKOUT SLAG / TIER NAME / STRIPE BASIC TIER PRICE
      'pattern' => Str::lower(option('kreativ-anders.stripekit.checkoutSlag')) . '/(:all)/(:all)',
      'action' => function ($tier, $price) {

        if (!kirby()->user()) {go();};

        $successURL  = kirby()->site()->url() . '/';
        $successURL .= Str::lower(option('kreativ-anders.stripekit.checkoutSlag')) . '/';
        $successURL .= Str::lower($tier) . '/success';

        $customer = kirby()->user()->stripe_customer();

        try {

          // STRIPE CHECKOUT SESSION
          $stripe = new \Stripe\StripeClient(option('kreativ-anders.stripekit.secretKey'));
          $checkout = $stripe->checkout->sessions->create([
            'success_url' => $successURL,
            'cancel_url' => option('kreativ-anders.stripekit.cancelURL'),
            'payment_method_types' => ['card'],
            'allow_promotion_codes' => true,
            'line_items' => [
              [
                'price' => base64_decode($price),
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
    // CANCEL STRIPE SUBSCRIPTION
    [
      // PATTERN --> CANCEL / CHECKOUT SLAG / KIRBY USER / STRIPE SUBSCRIPTION
      'pattern' => 'cancel/' . Str::lower(option('kreativ-anders.stripekit.checkoutSlag')) . '/(:all)/(:all)',
      'action' => function ($user, $subscription) {

        if (!kirby()->user()) {go();};

        $user  = base64_decode($user);
        $subscription  = base64_decode($subscription);


        try {

          // CANCEL STRIPE SUBSCRIPTION
          $stripe = new \Stripe\StripeClient(option('kreativ-anders.stripekit.secretKey'));
          $stripe->subscriptions->cancel(
            $subscription,
            []
          );

          // SUBSCRIPTION STATUS WILL BE "CANCELED"
          kirby()->user($user)->update([
            'stripe_subscription' => null,
            'stripe_status' => null,
            'tier' => option('kreativ-anders.stripekit.tiers')[0]['name']
          ]);

          go();

                
        } catch(Exception $e) {
        
          // LOG ERROR SOMEWHERE !!!
        }     

        return [
          'id' => false,
          'user' => $user,
          'sub' => $subscription,
        ];
      }
    ],
    // UPDATE USER AFTER SUCCESSFUL CHECKOUT
    [
      // PATTERN --> CHECKOUT SLAG / TIER NAME / success
      'pattern' => Str::lower(option('kreativ-anders.stripekit.checkoutSlag')) . '/(:all)/success',
      'action' => function ($tier) {

        if (!kirby()->user()) {go();};

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

