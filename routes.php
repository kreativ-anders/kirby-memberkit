<?php

/*
  ROUTES
  ----
  https://getkirby.com/docs/reference/plugins/extensions/routes
*/
   
return function ($kirby) {
  return [
    
    // CREATE STRIPE CHECKOUT SESSION --------------------------------------------------------------------------------------------------
    [
      // PATTERN => STRIPE SLUG / ACTION NAME (SUBSCRIBE) / STRIPE TIER NAME (RAWURLENCODED)
      'pattern' => Str::lower(option('kreativ-anders.memberkit.stripeURLSlug')) . '/subscribe/(:all)',
      'action' => function ($tier) {

        // DETERMINE PRICE BY TIER NAME
        $tier = rawurldecode($tier);
        $tierIndex = array_search($tier, array_map("Str::lower", array_column(option('kreativ-anders.memberkit.tiers'), 'name')), false);
        $price = option('kreativ-anders.memberkit.tiers')[$tierIndex]['price'];

        // BUILD MAN-IN-THE-MIDDLE/SUCCESS URL => SITE URL / STRIPE SLUG / ACTION NAME (SUCCESS)
        $successURL  = kirby()->site()->url() . '/';
        $successURL .= Str::lower(option('kreativ-anders.memberkit.stripeURLSlug'));
        $successURL .= '/success';

        $customer = kirby()->user()->stripe_customer();
        $stripe = new \Stripe\StripeClient(option('kreativ-anders.memberkit.secretKey'));

        try {

          // CREATE STRIPE CHECKOUT SESSION
          $checkout = $stripe->checkout->sessions->create([
            'success_url' => $successURL,
            'cancel_url' => option('kreativ-anders.memberkit.cancelURL'),
            'payment_method_types' => ['card'],
            'allow_promotion_codes' => true,
            'line_items' => [
              [
                'price' => $price,
                'quantity' => 1,
              ],
            ],
            'mode' => 'subscription',
            'customer' => kirby()->user()->stripe_customer(),
          ]);
      
        } catch(Exception $e) {
        
          // LOG ERROR SOMEWHERE !!!
          throw new Exception('Could not create stripe checkout session!');
        }     

        return [
          'id' => $checkout->id
        ];
      }
    ],
    // CREATE STRIPE CUSTOMER PORTAL SESSION ----------------------------------------------------------------------------------------
    [
      // PATTERN => STRIPE SLUG / STRIPE PORTAL
      'pattern' => Str::lower(option('kreativ-anders.memberkit.stripeURLSlug')) . '/portal',
      'action' => function () {

        // BUILD MAN-IN-THE-MIDDLE/RETURN URL => SITE URL
        $returnURL  = kirby()->site()->url() . '/';
        
        $customer = kirby()->user()->stripe_customer();
        $stripe = new \Stripe\StripeClient(option('kreativ-anders.memberkit.secretKey'));

        try {

          // CREATE STRIPE PORTAL SESSION
          $session = $stripe->billingPortal->sessions->create([
            'customer' => $customer,
            'return_url' => $returnURL,
          ]);

          $url = $session->url; 
      
        } catch(Exception $e) {
        
          // LOG ERROR SOMEWHERE !!!
          throw new Exception('Could not create stripe portal session!');
        }     

        // GO TO STRIPE PORTAL
        return go($url);
      }
    ],
    // CANCEL STRIPE SUBSCRIPTION -------------------------------------------------------------------------------------------------
    [
      // PATTERN => STRIPE SLUG / ACTION NAME (CANCEL) / TYPE NAME (SUBSCRIPTION)
      'pattern' => Str::lower(option('kreativ-anders.memberkit.stripeURLSlug')) . '/cancel/subscription',
      'action' => function () {

        $subscription = kirby()->user()->stripe_subscription();
        $email = kirby()->user()->email();
        
        $stripe = new \Stripe\StripeClient(option('kreativ-anders.memberkit.secretKey'));

        try {

          // CANCEL STRIPE SUBSCRIPTION
          $stripe->subscriptions->cancel(
            $subscription,
            []
          );
        } catch(Exception $e) {
        
          // LOG ERROR SOMEWHERE !!!
          throw new Exception('Could not cancel stripe subscription!');
        } 

        try {

          // RESET KIRBY USER SUBSCRIPTION - ROOT TIER (INDEX=0)
          kirby()->user($email)->update([
            'stripe_subscription' => null,
            'stripe_status' => null,
            'tier' => option('kreativ-anders.memberkit.tiers')[0]['name']
          ]);
                
        } catch(Exception $e) {
        
          // LOG ERROR SOMEWHERE !!!
          throw new Exception('Could not reset kirby user subscriptions!');
        }     

        return go();
      }
    ],
    // UPDATE/MERGE KIRBY USER AFTER (SUCCESSFUL) CHECKOUT --------------------------------------------------------------------------
    [
      // PATTERN => STRIPE SLUG / ACTION NAME (SUCCESS)
      'pattern' => Str::lower(option('kreativ-anders.memberkit.stripeURLSlug')) . '/success',
      'action' => function () {

        try {

          // MERGE STRIPE USER WITH KIRBY USER
          kirby()->user()->mergeStripeCustomer();

        } catch(Exception $e) {
        
          // LOG ERROR SOMEWHERE !!!
          throw new Exception('Could not merge stripe customer into kirby user!');
        }       

        // REDIRECT TO CUSTOM SUCCESS PAGE
        return go(option('kreativ-anders.memberkit.successURL'));
      }
    ],
    // LISTEN TO STRIPE NOTIFICATIONS AKA STRIPE WEBHOOK ----------------------------------------------------------------------------
    // https://stripe.com/docs/webhooks/integration-builder
    // --> NOT SECURED!!!
    [
      // PATTERN => STRIPE SLUG / ACTION NAME (WEBHOOK)
      'pattern' => Str::lower(option('kreativ-anders.memberkit.stripeURLSlug')) . '/webhook',
      'action' => function () {

        \Stripe\Stripe::setApiKey(option('kreativ-anders.memberkit.secretKey'));

        $endpoint_secret = 'whsec_VsnjOx8yRSMs7cjwFxHfw0kaj3NASwKU';

        $payload = @file_get_contents('php://input');
        $event = null;
        
        try {

          $event = \Stripe\Event::constructFrom(
            json_decode($payload, true)
          );

        } catch(\UnexpectedValueException $e) {

          http_response_code(400);
          exit();
        }  
        
        if ($endpoint_secret) {
          // Only verify the event if there is an endpoint secret defined
          // Otherwise use the basic decoded event
          $sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'];
          try {
            $event = \Stripe\Webhook::constructEvent(
              $payload, $sig_header, $endpoint_secret
            );
          } catch(\Stripe\Exception\SignatureVerificationException $e) {
            // Invalid signature
            echo '⚠️  Webhook error while validating signature.';
            http_response_code(400);
            exit();
          }
        }

        // HANDLE THE EVENT
        // https://stripe.com/docs/api/events/types
        switch ($event->type) {

          case 'customer.subscription.updated':
            $subscription = $event->data->object;

            // UPDATE STRIPE SUBSCRIPTION FOR USER X 
            kirby()->site()->updateStripeSubscriptionWebhook($subscription);  

            break;

          case 'customer.subscription.deleted':
            $subscription = $event->data->object;

            // RESET KIRBY USER SUBSCRIPTION INFO
            kirby()->site()->cancelStripeSubscriptionWebhook($subscription); 

            break;

          case 'customer.updated':
            $customer = $event->data->object;

            // UPDATE KIRBY USER EMAIL
            kirby()->site()->updateStripeEmailWebhook($customer); 

            break;

          default:

            throw new Exception('Received unknown stripe event type!');
        }

        http_response_code(200);
        return '<html><body>✔️ Success!</body></html>';
      },
      // ENSURE ONLY POST REQUESTS ARE CAPTURED
      'method' => 'POST'
    ],
  ];
};

