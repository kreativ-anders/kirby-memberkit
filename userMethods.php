<?php

/*
  USER-METHODS
  ----
  https://getkirby.com/docs/reference/plugins/extensions/user-methods
*/

return [

  // RETURN STRIPE SUBSCRIPTION CANCEL URL ---------------------------------------------------------------------------------
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
  // RETURN STRIPE SUBSCRIPTION CHECKOUT URL FOR TIER X (NAME AS PARAMETER) -----------------------------------------------------
  'getStripeCheckoutURL' => function ($tier) {

    // SEARCH TIER NAME AND CHECK FOR EXISTENCE
    $tierIndex = array_search($tier, array_column(option('kreativ-anders.memberkit.tiers'), 'name'), false);
    if (!$tierIndex || $tierIndex < 1) {

      throw new Exception('Tier does not exist!');
    }

    // BUILD URL => STRIPE SLUG / ACTION NAME (SUBSCRIBE) / STRIPE TIER NAME (RAWURLENCODED)
    $url  = Str::lower(option('kreativ-anders.memberkit.stripeURLSlug'));
    $url .= '/subscribe';
    $url .= '/' . rawurlencode(Str::lower(Str::trim(option('kreativ-anders.memberkit.tiers')[$tierIndex]['name'])));

    return $url;
  },
  // RETURN STRIPE CUSTOMER PORTAL URL -------------------------------------------------------------------------------------------
  'getStripePortalURL' => function () {

    // BUILD URL => STRIPE SLUG / ACTION NAME (PORTAL)
    $url  = Str::lower(option('kreativ-anders.memberkit.stripeURLSlug'));
    $url .= '/portal';

    return $url;
  },
  // RETRIEVE STRIPE CUSTOMER (WITH SUBSCRIPTIONS) -------------------------------------------------------------------------------
  'retrieveStripeCustomer' => function () {

    if (!option('debug')) {

      throw new Exception('Retrieve stripe customer is only available in debug mode!');
    }

    $stripe = new \Stripe\StripeClient(option('kreativ-anders.memberkit.secretKey'));
    $customer = null;

    try {

      // RETRIEVE STRIPE CUSTOMER
      $customer = $stripe->customers->retrieve(
        $this->stripe_customer(),
        ['expand' => ['subscription']]
      );

    } catch(Exception $e) {
        
      // LOG ERROR SOMEWHERE !!!
      throw new Exception('Retrieve stripe customer failed!');
    }

    return $customer;
  },
  // MERGE STRIPE CUSTOMER WITH KIRBY USER ----------------------------------------------------------------------------------------
  'mergeStripeCustomer' => function () {

    $stripe = new \Stripe\StripeClient(option('kreativ-anders.memberkit.secretKey'));
    $customer = null;

    try {

      // RETRIEVE STRIPE CUSTOMER
      $customer = $stripe->customers->retrieve(
        $this->stripe_customer(),
        ['expand' => ['subscription']]
      );

    } catch(Exception $e) {
        
      // LOG ERROR SOMEWHERE !!!
      throw new Exception('Retrieve stripe customer failed!');
    }   

    $subscription = $customer->subscriptions['data'][0];

    // DETERMINE TIER NAME BY STRIPE PRICE ID
    $price = $subscription->items['data'][0]->price->id;
    $priceIndex = array_search($price, array_column(option('kreativ-anders.memberkit.tiers'), 'price'), false);
    $tier = option('kreativ-anders.memberkit.tiers')[$priceIndex]['name'];
    
    try {

      // UPDATE KIRBY USER
      $this->update([
        'stripe_subscription' => $subscription->id,
        'stripe_status' => $subscription->status,
        'tier' => $tier
      ]);

      return true;

    } catch (Exception $e) {

      // LOG ERROR SOMEWHERE !!!
      throw new Exception('Update kirby user failed!');
    }

    return false;
  },
  // CHECK USER PRIVILEGES BASED ON TIER (INDEX) -----------------------------------------------------------------------------
  'isAllowed' => function ($tier) {

    $userTier = $this->tier()->toString();

    // GET INDEX FROM USER AND TIER NAME
    $userIndex = array_search($userTier, array_column(option('kreativ-anders.memberkit.tiers'), 'name'), false);
    $tierIndex = array_search($tier, array_column(option('kreativ-anders.memberkit.tiers'), 'name'), false);
 
    // NO SUBSCRIPTION OR NON-ACTIVE SUBSCRIPTION
    if ($this->tier()->isEmpty() || $this->stripe_subscription()->isEmpty() || $this->stripe_status()->isEmpty() || $this->stripe_status()->toString() != 'active') {

      return false;
    }

    // REQUESTED TIER MATCHES USER TIER
    if ($userTier === $tier) {

      return true;
    }

    // USER TIER IS HIGHER (PRIO) THAN REQUESTED TIER
    if ($userIndex >= $tierIndex) {

      return true;
    }

    // DEFAULT
    return false;
  }, 

];