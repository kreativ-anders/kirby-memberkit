<?php
@include_once __DIR__ . '/lib/stripe/init.php';

Kirby::plugin('kreativ-anders/stripekit', [
  'options' => [
    'privateKey' => 'sk_test_xxx',
    'publicKey' => 'pk_test_xxx',
    'checkoutSlag' => 'checkout',
    'successURL' => '../success',
    'cancelURL' => '../cancel',
    'tier0' => 'FREE',
    'tier1' => 'BASIC',
    'tier1Price' => 'price_xxxx',
    'tier2' => 'PREMIUM',
    'tier2Price' => 'price_xxxx',
  ],

  /* 
    SNIPPETS 
    -----
    https://getkirby.com/docs/reference/plugins/extensions/snippets

  */

  'snippets' => [
    'stripejs' => __DIR__ . '/snippets/stripejs.php',
    'stripe-checkout-button' => __DIR__ . '/snippets/stripe-checkout-button.php'    
  ],

  /* 
    HOOKS 
    -----
    https://getkirby.com/docs/reference/plugins/hooks

  */

  'hooks' => [
    // CREATE STRIPE USER -----------------------------------------------------------------------------------------
    // https://stripe.com/docs/api/customers/create
    'user.create:after' => function ($user) {

      try {

        $stripe = new \Stripe\StripeClient(option('kreativ-anders.stripekit.privateKey'));
        $customer = $stripe->customers->create([
          'email' => $user->email()
        ]);

        // UPDATE KIRBY USER - FREE TIER 0
        $user->update([
          'stripe_customer' => $customer->id,
          'tier' => option('kreativ-anders.stripekit.tier0')
        ]);

      } catch(Exception $e) {
      
        // LOG ERROR SOMEWHERE !!!
      }
    },
    // CHANGE STRIPE USER EMAIL -------------------------------------------------------------------------------------
    // https://stripe.com/docs/api/customers/update
    'user.changeEmail:after' => function ($newUser, $oldUser) {

      try {

        $stripe = new \Stripe\StripeClient(option('kreativ-anders.stripekit.privateKey'));
        $stripe->customers->update(
          $oldUser->stripe_customer(),
          ['email' => $newUser->email()]
        );

      } catch(Exception $e) {
      
        // LOG ERROR SOMEWHERE !!!
      }
    },
    // DELETE KIRBY USER & CANCEL ALL STRIPE SUBSCRIPTIONS ------------------------------------------------------------
    // https://stripe.com/docs/api/customers/delete
    'user.delete:after' => function ($status, $user) {
      
      try {

        $stripe = new \Stripe\StripeClient(option('kreativ-anders.stripekit.privateKey'));
        $stripe->customers->delete(
          $user->stripe_customer(),
          []
        );

      } catch(Exception $e) {
      
        // LOG ERROR SOMEWHERE !!!
      }
    }
  ],

  /*
    API
    ----------
    https://getkirby.com/docs/reference/plugins/extensions/api

  */

  // TIER 1
  'routes' => function ($kirby) {
    return [
      [
        // PATTERN --> CHECKOUT SLAG / BASIC TIER NAME / STRIPE CUSTOMER / BASIC TIER PRICE
        'pattern' => Str::lower(option('kreativ-anders.stripekit.checkoutSlag')) . '/' . Str::lower(option('kreativ-anders.stripekit.tier1')) . '/(:all)/(:all)',
        'test' => option('kreativ-anders.stripekit.checkoutSlag'),
        'action' => function ($user, $price) {

          try {

            // STRIPE CHECKOUT SESSION
            $stripe = new \Stripe\StripeClient(option('kreativ-anders.stripekit.privateKey'));
            $checkout = $stripe->checkout->sessions->create([
              'success_url' => option('kreativ-anders.stripekit.successURL'),
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

          // STRIPE CHECKOUT SESSION
          $successURL = option('kreativ-anders.stripekit.successURL');
          $cancelURL = option('kreativ-anders.stripekit.cancelURL');         

          return [
            'user' => base64_decode($user),
            'successURL' => $successURL,
            'cancelURL' => $cancelURL,
            'price' => base64_decode($price),
            'id' => $checkout->id,
          ];
        }
      ]
    ];
  },
]);


?>