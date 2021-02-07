<?php

// INCLUDE EXTERNAL LIBRARIES
@include_once __DIR__ . '/lib/stripe/init.php';

Kirby::plugin('kreativ-anders/stripekit', [

  /* 
    OPTIONS 
    ----
    https://getkirby.com/docs/reference/plugins/extensions/options

  */

  'options' => [
    'secretKey'     => 'sk_test_xxx',
    'publicKey'     => 'pk_test_xxx',
    'checkoutSlag'  => 'checkout',
    'successURL'    => '../success',
    'cancelURL'     => '../cancel',
    'tier0'         => 'FREE',
    'tier1'         => 'BASIC',
    'tier1Price'    => 'price_xxxx',
    'tier2'         => 'PREMIUM',
    'tier2Price'    => 'price_xxxx',
  ],

  /* 
    SNIPPETS 
    ----
    https://getkirby.com/docs/reference/plugins/extensions/snippets

  */

  'snippets' => [
    'stripejs' => __DIR__ . '/snippets/stripejs.php',
    'stripe-checkout-button' => __DIR__ . '/snippets/stripe-checkout-button.php'    
  ],

  /* 
    HOOKS 
    ----
    https://getkirby.com/docs/reference/plugins/hooks

  */

  'hooks' => [
    // CREATE STRIPE USER -----------------------------------------------------------------------------------------
    // https://stripe.com/docs/api/customers/create
    'user.create:after' => function ($user) {

      try {

        $stripe = new \Stripe\StripeClient(option('kreativ-anders.stripekit.secretKey'));
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

        $stripe = new \Stripe\StripeClient(option('kreativ-anders.stripekit.secretKey'));
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

        $stripe = new \Stripe\StripeClient(option('kreativ-anders.stripekit.secretKey'));
        $stripe->customers->delete(
          $user->stripe_customer(),
          []
        );

      } catch(Exception $e) {
      
        // LOG ERROR SOMEWHERE !!!
      }
    },
  ],

  /*
    ROUTES
    ----
    https://getkirby.com/docs/reference/plugins/extensions/routes

  */

  'routes' => function ($kirby) {
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

          switch ($tier) {
            case Str::lower(option('kreativ-anders.stripekit.tier1')):
              $tier = option('kreativ-anders.stripekit.tier1');
              break;

            case Str::lower(option('kreativ-anders.stripekit.tier2')):
              $tier = option('kreativ-anders.stripekit.tier2');
              break;
            
            default:
              $tier = option('kreativ-anders.stripekit.tier0');
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
  },

  /*
    USER-METHODS
    ----
    https://getkirby.com/docs/reference/plugins/extensions/user-methods

  */

  'userMethods' => [
    // SUBSCRIBE USER TO TIER X
    'subscripe' => function ($tier) {

      /*
        This might be great for user experience, but it is painful regarding process variants.
        More variants yield more complexity. Keep it simple and stupid.

        => Cancel subscription and subscripe to new one (Upgrade & Downgrade)

        Furthermore, it requires you to know the payment information. Sensetive data like this should be handled via Stripe´s checkout.
        In case the user is already subscribed to a tier you might have the payment information, but as mentioned earlier - 
        This increase the process variants that need to be considered.

        https://stripe.com/docs/billing/subscriptions/upgrade-downgrade
      */
      return false;
    },
    // RETURN STRIPE SUBSCRIPTION CHECKOUT URL FOR TIER X
    'getStripeCheckoutURL' => function ($tier, $price) {

      /*
        Combination of $tier + $price is not validated at all!
      */

      $url  = Str::lower(option('kreativ-anders.stripekit.checkoutSlag'));  // CHECKOUT SLAG
      $url .= '/' . Str::lower($tier);                                      // TIER NAME
      $url .= '/' . base64_encode($this->stripe_customer());                // STRIPE CUSTOMER
      $url .= '/' . base64_encode($price);                                  // STRIPE TIER PRICE

      return $url;
    },
    // RETRIEVE STRIPE CUSTOMER (WITH SUBSCRIPTIONS)
    'retrieveStripeCustomer' => function () {

      $customer = null;

      try {

        $stripe = new \Stripe\StripeClient(option('kreativ-anders.stripekit.secretKey'));
        $customer = $stripe->customers->retrieve(
          $this->stripe_customer(),
          ['expand' => ['subscription']]
        );

      } catch(Exception $e) {
          
        // LOG ERROR SOMEWHERE !!!
      }

      return $customer;
    },
    // RETRIEVE STRIPE CUSTOMER SUBSCRIPTION
    'retrieveStripeSubscription' => function () {

      $subscription = $this->retrieveStripeCustomer()->subscriptions['data'][0];

      return $subscription;
    }
  ],

]);


?>