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
  // RETURN STRIPE SUBSCRIPTION CHECKOUT URL FOR TIER X
  'getStripeCheckoutURL' => function ($tier) {

    if (!isset($tier['name'])   || empty($tier['name'])   || $tier['name'] === '' || 
        !isset($tier['price'])  || empty($tier['price'])  || $tier['price'] === '') {

      throw new Exception('tier price or name is empty!'); 
    }

    $url  = Str::lower(option('kreativ-anders.stripekit.checkoutSlag'));  // CHECKOUT SLAG
    $url .= '/' . Str::lower($tier['name']);                              // TIER NAME
    $url .= '/' . base64_encode($this->stripe_customer());                // STRIPE CUSTOMER
    $url .= '/' . base64_encode($tier['price']);                          // STRIPE TIER PRICE

    return $url;
  },
  // RETRIEVE STRIPE CUSTOMER (WITH SUBSCRIPTIONS)
  'retrieveStripeCustomer' => function () {

    $customer = null;

    try {

      $stripe = new \Stripe\StripeClient(option('kreativ-anders.stripekit.secretKey'));
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
  // CHECK USER PRIVILEGES
  /*
    Due to usability isAllowed receives a string so you do not need to call it like:
    $kirby->user()->isAllowed(option('kreativ-anders.stripekit.tiers')[0]['name'])
    Now you can quickly call the function by writing:
    $kirby->user()->isAllowed('Basic') 
  */
  'isAllowed' => function ($tier) {

    $userTier = $this->tier()->toString();

    $userIndex = array_search($userTier, array_column(option('kreativ-anders.stripekit.tiers'), 'name'), false);
    $tierIndex = array_search($tier, array_column(option('kreativ-anders.stripekit.tiers'), 'name'), false);
 
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