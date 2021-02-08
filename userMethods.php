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
  'getStripeCheckoutURL' => function ($tier, $price) {

    /*
      Combination of $tier + $price is not validated at all!
    */

    $url  = Str::lower(option('kreativ-anders.stripekit.checkoutSlag'));  // CHECKOUT SLAG
    $url .= '/' . Str::lower($tier);                                      // TIER NAME
    $url .= '/' . base64_encode($this->stripe_customer());                // STRIPE CUSTOMER
    $url .= '/' . base64_encode($price);                                  // STRIPE TIER PRICE

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
  }

];