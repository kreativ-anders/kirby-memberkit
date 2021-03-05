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

      $stripe = new \Stripe\StripeClient(option('kreativ-anders.memberkit.secretKey'));
      $customer = $stripe->customers->create([
        'email' => $user->email()
      ]);

      // UPDATE KIRBY USER - FREE TIER 0
      $user->update([
        'stripe_customer' => $customer->id,
        'tier' => option('kreativ-anders.memberkit.tiers')[0]['name']
      ]);

    } catch(Exception $e) {
    
      // LOG ERROR SOMEWHERE !!!
    }
  },

  // CHANGE STRIPE USER EMAIL -------------------------------------------------------------------------------------
  // https://stripe.com/docs/api/customers/update
  'user.changeEmail:after' => function ($newUser, $oldUser) {

    try {

      $stripe = new \Stripe\StripeClient(option('kreativ-anders.memberkit.secretKey'));
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

      $stripe = new \Stripe\StripeClient(option('kreativ-anders.memberkit.secretKey'));
      $stripe->customers->delete(
        $user->stripe_customer(),
        []
      );

    } catch(Exception $e) {
    
      // LOG ERROR SOMEWHERE !!!
    }
  },

  // RESERVE STRIPE ROUTES TO LOGGED-IN USERS
  // https://getkirby.com/docs/guide/routing#before-and-after-hooks__route-before
  'route:before' => function ($route, $path, $method) {

    $subscribe = Str::contains($path, Str::lower(option('kreativ-anders.memberkit.stripeURLSlug')) . '/subscribe/');
    $portal = Str::contains($path, Str::lower(option('kreativ-anders.memberkit.stripeURLSlug')) . '/portal');
    $success = Str::contains($path, Str::lower(option('kreativ-anders.memberkit.stripeURLSlug')) . '/success');
    $cancel = Str::contains($path, Str::lower(option('kreativ-anders.memberkit.stripeURLSlug')) . '/cancel/subscription');

    if ($cancel && !option('debug')) {

      throw new Exception('Cancel stripe subscription via URL is only available in debug mode!');
    }

    if (($subscribe || $portal || $success || $cancel) && !kirby()->user()) {
      go();
    }
  }

];