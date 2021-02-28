<?php

/*
  USER-METHODS
  ----
  https://getkirby.com/docs/reference/plugins/extensions/user-methods

*/

return [

  'subscripe' => function ($tier) {

    /*
      This might be great for user experience, but it is painful regarding process variants.
      More variants yield more complexity. Keep it simple and stupid.

      => Cancel subscription and subscripe to new one (Upgrade & Downgrade)

      Furthermore, it requires you to know the payment information. Sensetive data like this should be handled via Stripe´s checkout.
      In case the user is already subscribed to a tier you might have the payment information, but as mentioned earlier - 
      This increase the process variants that need to be considered.

      https://stripe.com/docs/billing/subscriptions/upgrade-downgrade
    */
    return false;
  },
  // RETURN STRIPE SUBSCRIPTION CANCEL URL
  'getStripeCancelURL' => function () {

    $subscription = null;

    if ($this->stripe_subscription()->isEmpty()) {

      throw new Exception('No subscription to cancel!');
    }

    // CHECKOUT SLAG / ACTION NAME (CANCEL) / STRIPE TIER NAME
    $url =  Str::lower(option('kreativ-anders.memberkit.checkoutSlag'));
    $url .= '/cancel';
    $url .= '/' . Str::lower(Str::trim($this->tier()));   

    return $url;
  },
  // RETURN STRIPE SUBSCRIPTION CHECKOUT URL FOR TIER X
  'getStripeCheckoutURL' => function ($tier) {

    if (!isset($tier['name'])   || empty($tier['name'])   || $tier['name'] === '' || 
        !isset($tier['price'])  || empty($tier['price'])  || $tier['price'] === '') {

      throw new Exception('tier price or name is empty!');
    }

    // CHECKOUT SLAG / ACTION NAME (SUBSCRIBE) / STRIPE TIER NAME
    $url  = Str::lower(option('kreativ-anders.memberkit.checkoutSlag'));
    $url .= '/subscribe';
    $url .= '/' . Str::lower(Str::trim($tier['name']));

    return $url;
  },
  // RETURN STRIPE CUSTOMER PORTAL URL
  'getStripePortalURL' => function () {

    // CHECKOUT SLAG / STRIPE PORTAL
    $url  = Str::lower(option('kreativ-anders.memberkit.checkoutSlag'));
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
  // RETRIEVE STRIPE CUSTOMER SUBSCRIPTION
  'retrieveStripeSubscription' => function () {

    $subscription = $this->retrieveStripeCustomer()->subscriptions['data'][0];

    return $subscription;
  },
  // MERGE STRIPE CUSTOMER WITH KIRBY USER
  'mergeStripeCustomer' => function () {

    $subscription = $this->retrieveStripeSubscription();

    try {

      // FIND TIER NAME
      $price = $subscription['items']['data'][0]['price']['id'];
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