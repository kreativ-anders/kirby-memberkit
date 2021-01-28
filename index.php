<?php
@include_once __DIR__ . '/lib/stripe/init.php';

Kirby::plugin('kreativ-anders/stripekit', [
  'options' => [
    'privateKey' => 'sk_test_xxx'
  ],
  /* 
    Using hooks make use of Kirby´s built checks, e.g. duplicate user.
    Side effect -> No error logging. :/ 
  */
  'hooks' => [
    // CREATE STRIPE USER -----------------------------------------------------------------------------------------
    'user.create:after' => function ($user) {

      try {

        $stripe = new \Stripe\StripeClient(option('kreativ-anders.stripekit.privateKey'));
        $customer = $stripe->customers->create([
          'email' => $user->email()
        ]);

        // UPDATE KIRBY USER
        $user->update([
          'stripe_customer' => $customer->id
        ]);

      } catch(Exception $e) {
      
        // LOG ERROR SOMEWHERE !!!
      }
    },
    // CHANGE STRIPE USER EMAIL -------------------------------------------------------------------------------------
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
    // UPDATE STRIPE USER SUBSCRIPTION (FREE TIER!!!)
    'user.update:after' => function ($newUser, $oldUser) {
      // your code goes here
    },
    // CANCEL ALL STRIPE SUBSCRIPTION
    'user.delete:after' => function ($status, $user) {
      // your code goes here
    }
  ]
]);


?>