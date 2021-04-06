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
    
    $stripe = new \Stripe\StripeClient(option('kreativ-anders.memberkit.secretKey'));

    try {

      // CREATE STRIPE CUSTOMER
      $customer = $stripe->customers->create([
        'email' => $user->email()
      ]);

    } catch (Exception $e) {
      
      // LOG ERROR SOMEWHERE
      throw new Exception('Could not create stripe customer!');
    } 

    try {

      // UPDATE KIRBY USER - ROOT TIER (INDEX=0)
      kirby()->user($user->email())->update([
        'stripe_customer' => $customer->id,
        'tier' => option('kreativ-anders.memberkit.tiers')[0]['name']
      ]);

    } catch (Exception $e) {
    
      // LOG ERROR SOMEWHERE !!!
      throw new Exception('Could not update kirby user!');
    }
  },
  // CHANGE STRIPE USER EMAIL -------------------------------------------------------------------------------------
  // https://stripe.com/docs/api/customers/update
  'user.changeEmail:after' => function ($newUser, $oldUser) {

    $stripe = new \Stripe\StripeClient(option('kreativ-anders.memberkit.secretKey'));

    try {

      // UPDATE STRIPE CUSTOMER
      $stripe->customers->update(
        $oldUser->stripe_customer(),
        ['email' => $newUser->email()]
      );

    } catch(Exception $e) {
    
      // LOG ERROR SOMEWHERE !!!
      throw new Exception('Could not update stripe customer!');
    }
  },
  // DELETE KIRBY USER & CANCEL ALL STRIPE SUBSCRIPTIONS ------------------------------------------------------------
  // https://stripe.com/docs/api/customers/delete
  'user.delete:after' => function ($status, $user) {

    $stripe = new \Stripe\StripeClient(option('kreativ-anders.memberkit.secretKey'));
    
    try {

      // DELETE STRIPE CUSTOMER
      $stripe->customers->delete(
        $user->stripe_customer(),
        []
      );

    } catch(Exception $e) {
    
      // LOG ERROR SOMEWHERE !!!
      throw new Exception('Could not delete stripe customer!');
    }
  },
  // RESERVE STRIPE ROUTES TO LOGGED-IN USERS
  // https://getkirby.com/docs/guide/routing#before-and-after-hooks__route-before
  'route:before' => function ($route, $path, $method) {

    // DETERMINE ROUTE PATH AS BEST AS POSSIBLE (TRUE = MATCH)
    $subscribe = Str::contains($path, Str::lower(option('kreativ-anders.memberkit.stripeURLSlug')) . '/subscribe/');
    $portal = Str::contains($path, Str::lower(option('kreativ-anders.memberkit.stripeURLSlug')) . '/portal');
    $checkout = Str::contains($path, Str::lower(option('kreativ-anders.memberkit.stripeURLSlug')) . '/success');
    $cancel = Str::contains($path, Str::lower(option('kreativ-anders.memberkit.stripeURLSlug')) . '/cancel/subscription');

    // CANCEL ROUTE IS DEBUG MODE EXCLUSIVE
    if ($cancel && !option('debug')) {

      throw new Exception('Cancel stripe subscription via URL is only available in debug mode!');
    }

    // REDIRECT TO HOMEPAGE WHEN USER IS NOT LOGGED-IN
    if (($subscribe || $portal || $checkout || $cancel) && !kirby()->user()) {
      go();
    }
  }

];
