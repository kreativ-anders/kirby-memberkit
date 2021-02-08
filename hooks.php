<?php

/* 
  HOOKS 
  ----
  https://getkirby.com/docs/reference/plugins/hooks

*/

return [

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
  }  
];