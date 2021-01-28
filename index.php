<?php
@include_once __DIR__ . '/lib/stripe-php-7.69.0/init.php';

Kirby::plugin('kreativ-anders/stripekit', [
  'options' => [
    'sKey' => 'sk_test_xxx'
  ],
  'hooks' => [
    'user.create:after' => function ($user) {
      $stripe = new \Stripe\StripeClient(
        'sk_test_66EkZ3GZowFWgxrXszrRnSd200EmvYltOK'
      );
      $stripe->customers->create([
        'description' => $user->email(),
      ]);
    }
  ]
]);


?>