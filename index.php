<?php
@include_once __DIR__ . '/lib/stripe/init.php';

Kirby::plugin('kreativ-anders/stripekit', [
  'options' => [
    'privateKey' => 'sk_test_xxx',
    'tier1' => 'price_xxx',
    'tier2' => null,
    'tier3' => null
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

        // UPDATE KIRBY USER
        $user->update([
          'stripe_customer' => $customer->id,
          'stripe_subscription' => false
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
        return $this->stripe_subscription();
      }

      try {

        $stripe = new \Stripe\StripeClient(option('kreativ-anders.stripekit.privateKey'));
        $subscription = $stripe->subscriptions->create([
          'customer' => $this->stripe_customer(),
          'items' => [
            ['price' => $price],
          ],
        ]);

        // UPDATE KIRBY USER
        $this->update([
          'stripe_subscription' => $subscription->id
        ]);

      } catch(Exception $e) {
      
        // LOG ERROR SOMEWHERE !!!
      }

      return $subscription;
    },

    // DOWNGRADE OR UPGRADE SUBSCRIPTION

    // CANCEL SUBSCRIPTION
    'fullname' => function () {
        // provided there are firstname and lastname fields in the user blueprints
        return $this->firstname() . ' ' . $this->lastname();
    }
  ]
]);


?>