<?php

/*
  USER-METHODS
  ----
  https://getkirby.com/docs/reference/plugins/extensions/user-methods
*/

return [

  // RETURN STRIPE SUBSCRIPTION CANCEL URL
  'getStripeCancelURL' => function () {

    if ($this->stripe_subscription()->isEmpty()) {

      throw new Exception('No subscription to cancel!');
    }

    // BUILD URL => STRIPE SLUG / ACTION NAME (CANCEL) / TYPE NAME (SUBSCRIPTION)
    $url =  Str::lower(option('kreativ-anders.memberkit.stripeURLSlug'));
    $url .= '/cancel/subscription';   

    return $url;
  },
  // RETURN STRIPE WEBHOOK URL
  'getStripeWebhookURL' => function () {

    // BUILD URL => STRIPE SLUG / ACTION NAME (WEBHOOK)
    $url =  Str::lower(option('kreativ-anders.memberkit.stripeURLSlug'));
    $url .= '/webhook';

    return $url;
  },
  // RETURN STRIPE SUBSCRIPTION CHECKOUT URL FOR TIER X (NAME AS PARAMETER)
  'getStripeCheckoutURL' => function ($tier) {

    // SEARCH TIER NAME AND CHECK FOR EXISTENCE
    $tierIndex = array_search($tier, array_column(option('kreativ-anders.memberkit.tiers'), 'name'), false);
    if (!$tierIndex || $tierIndex < 1) {

      throw new Exception('Tier does not exist!');
    }

    // BUILD URL => STRIPE SLUG / ACTION NAME (SUBSCRIBE) / STRIPE TIER NAME
    $url  = Str::lower(option('kreativ-anders.memberkit.stripeURLSlug'));
    $url .= '/subscribe';
    $url .= '/' . rawurlencode(Str::lower(Str::trim(option('kreativ-anders.memberkit.tiers')[$tierIndex]['name'])));

    return $url;
  },
  // RETURN STRIPE CUSTOMER PORTAL URL
  'getStripePortalURL' => function () {

    // STRIPE SLUG / STRIPE PORTAL
    $url  = Str::lower(option('kreativ-anders.memberkit.stripeURLSlug'));
    $url .= '/portal';

    return $url;
  },
  // RETRIEVE STRIPE CUSTOMER (WITH SUBSCRIPTIONS)
  'retrieveStripeCustomer' => function () {

    $customer = null;

    try {

      $stripe = new \Stripe\StripeClient(option('kreativ-anders.memberkit.secretKey'));
      $customer = $stripe->customers->retrieve(
        $this->stripe_customer(),
        ['expand' => ['subscription']]
      );

    } catch(Exception $e) {
        
      // LOG ERROR SOMEWHERE !!!
    }

    return $customer;
  },
  // MERGE STRIPE CUSTOMER WITH KIRBY USER
  'mergeStripeCustomer' => function () {

    $customer = null;

    try {

      $stripe = new \Stripe\StripeClient(option('kreativ-anders.memberkit.secretKey'));

      // $customer = $this->retrieveStripeCustomer() // would work as well, but will fail when called within a route!
      $customer = $stripe->customers->retrieve(
        $this->stripe_customer(),
        ['expand' => ['subscription']]
      );

      $subscription = $customer->subscriptions['data'][0];

      $price = $subscription->items['data'][0]->price->id;
      $priceIndex = array_search($price, array_column(option('kreativ-anders.memberkit.tiers'), 'price'), false);

      $tier = option('kreativ-anders.memberkit.tiers')[$priceIndex]['name'];
      
      // UPDATE KIRBY USER
      $this->update([
        'stripe_subscription' => $subscription->id,
        'stripe_status' => $subscription->status,
        'tier' => $tier
      ]);

      return true;

    } catch(Exception $e) {
        
      // LOG ERROR SOMEWHERE !!!
    }    

    return false;
  },
  // CHECK USER PRIVILEGES
  /*
    Due to usability isAllowed receives a string so you do not need to call it like:
    $kirby->user()->isAllowed(option('kreativ-anders.memberkit.tiers')[0]['name'])
    Now you can quickly call the function by writing:
    $kirby->user()->isAllowed('Basic') 
  */
  'isAllowed' => function ($tier) {

    $userTier = $this->tier()->toString();

    $userIndex = array_search($userTier, array_column(option('kreativ-anders.memberkit.tiers'), 'name'), false);
    $tierIndex = array_search($tier, array_column(option('kreativ-anders.memberkit.tiers'), 'name'), false);
 
    if ($this->tier()->isEmpty() || $this->stripe_subscription()->isEmpty() || $this->stripe_status()->isEmpty() || $this->stripe_status()->toString() != 'active') {

      return false;
    }

    if ($userTier === $tier) {

      return true;
    }

    if ($userIndex >= $tierIndex) {

      return true;
    }

    return false;
  }, 


];