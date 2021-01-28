<?php
@include_once __DIR__ . '/lib/stripe/init.php';

Kirby::plugin('kreativ-anders/stripekit', [
  'options' => [
    'privateKey' => 'sk_test_xxx'
  ],
  'hooks' => [
    'user.create:after' => function ($user) {
      // CREATE STRIPE USER
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
    }
]
]);


?>