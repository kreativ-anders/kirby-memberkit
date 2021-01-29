<?php
@include_once __DIR__ . '/lib/stripe/init.php';

Kirby::plugin('kreativ-anders/stripekit', [
  'options' => [
    'privateKey' => 'sk_test_xxx',
    'publicKey' => 'pk_test_xxx',
    'checkoutSlag' => 'checkout',
    'succuessURL' => '../success',
    'cancelURL' => '../cancel',
    'free' => 'FREE',
    'basic' => 'BASIC',
    'premium' => 'PREMIUM'
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

        // UPDATE KIRBY USER - FREE TIER
        $user->update([
          'stripe_customer' => $customer->id,
          'tier' => option('kreativ-anders.stripekit.free')
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

  // BASIC TIER
  'routes' => function ($kirby) {
    return [
      [
        'pattern' => Str::lower(option('kreativ-anders.stripekit.checkoutSlag')) . '/' . Str::lower(option('kreativ-anders.stripekit.basic')) . '/(:all)/(:all)/(:all)/(:all)',
        'test' => option('kreativ-anders.stripekit.checkoutSlag'),
        'action' => function ($url, $url2, $hallo, $ho) {

          // STRIPE CHECKOUT SESSION
          $id = $url;
          

          return [
            'id' => $id
          ];
        }
      ]
    ];
  },

  /*
    EXTENSIONS
    ----------
    https://getkirby.com/docs/reference/plugins/extensions/user-methods

  */

  'userMethods' => [
    // START SUBSCRIPTION
    // https://stripe.com/docs/api/subscriptions/create
    'stripeSubscripe' => function ($price) {

      // CHECK USER SUBSCRIPTIONS
      if ($this->stripe_subscription() || $this->stripe_subscription() == '') {
        
        try {

          // STRIPE CHECKOUT SESSION
          $stripe = new \Stripe\StripeClient(option('kreativ-anders.stripekit.privateKey'));
          $checkout = $stripe->checkout->sessions->create([
            'success_url' => 'https://example.com/success',
            'cancel_url' => 'https://example.com/cancel',
            'payment_method_types' => ['card'],
            'line_items' => [
              [
                'price' => $price,
                'quantity' => 1,
              ],
            ],
            'mode' => 'subscription',
            'customer' => $this->stripe_customer(),
          ]);
  
          // UPDATE KIRBY USER
          $this->update([
            'stripe_subscription' => $checkout->id
          ]);
  
        } catch(Exception $e) {
        
          // LOG ERROR SOMEWHERE !!!

          $checkout = $e;
        }
      }
      else {
        return $this->stripe_subscription();
      }

      return $checkout;
    },

    // DOWNGRADE OR UPGRADE SUBSCRIPTION

    // CANCEL SUBSCRIPTION
  ]
]);


?>