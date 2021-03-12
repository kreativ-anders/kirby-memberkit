<?php

/*
  USERS-METHODS
  ----
  https://getkirby.com/docs/reference/plugins/extensions/users-methods
*/

return [

  // MAKE NON-STRIPE KIRBY USERS TO STRIPE CUSTOMERS --------------------------------------------------------------------------
  'migrateStripeCustomers' => function () {

    $users = kirby()->users();
    $counter = 0;

    // THIS IS A TASK FOR THE ADMIN
    if (kirby()->user()->isAdmin()) {

      $stripe = new \Stripe\StripeClient(option('kreativ-anders.memberkit.secretKey'));

      // NO TRY CATCH BLOCK - LET EXCEPTION ARISE
      foreach($users as $user) {

        if ($user->stripe_customer()->isEmpty()) {

          // CREATE STRIPE CUSTOMER
          $customer = $stripe->customers->create([
            'email' => $user->email()
          ]);

          // UPDATE KIRBY USER - ROOT TIER (INDEX=0)
          $kirby = kirby();
          $kirby->impersonate('kirby');

          $kirby->user($user->email())->update([
            'stripe_customer' => $customer->id,
            'tier' => option('kreativ-anders.memberkit.tiers')[0]['name']
          ]);

          $kirby->impersonate();  

          $counter++; 
        }
      }
  
    } else {

      throw new Exception('This is an admin task!');
    }     

    return [

      'users'       => count($users),
      'migrations'  => $counter
    ];
  }
];