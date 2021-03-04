<?php

/*
  ROUTES
  ----
  https://getkirby.com/docs/reference/plugins/extensions/routes

*/
   
return function ($kirby) {
  return [
    
    // CREATE STRIPE CHECKOUT SESSION
    [
      // PATTERN --> CHECKOUT SLAG / ACTION NAME (SUBSCRIBE) / STRIPE TIER NAME
      'pattern' => Str::lower(option('kreativ-anders.memberkit.checkoutSlag')) . '/subscribe/(:all)',
      'action' => function ($tier) {

        $tier = rawurldecode($tier);
        $tierIndex = array_search($tier, array_map("Str::lower", array_column(option('kreativ-anders.memberkit.tiers'), 'name')), false);
        $price = option('kreativ-anders.memberkit.tiers')[$tierIndex]['price'];

        $successURL  = kirby()->site()->url() . '/';
        $successURL .= Str::lower(option('kreativ-anders.memberkit.checkoutSlag'));
        $successURL .= '/success';

        $customer = kirby()->user()->stripe_customer();

        try {

          // STRIPE CHECKOUT SESSION
          $stripe = new \Stripe\StripeClient(option('kreativ-anders.memberkit.secretKey'));
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
        }     

        return [
          'id' => $checkout->id
        ];
      }
    ],
    // CREATE STRIPE CUSTOMER PORTAL SESSION
    [
      // PATTERN --> CHECKOUT SLAG / STRIPE PORTAL
      'pattern' => Str::lower(option('kreativ-anders.memberkit.checkoutSlag')) . '/portal',
      'action' => function () {

        $customer = kirby()->user()->stripe_customer();

        $returnURL  = kirby()->site()->url() . '/';
        // $returnURL .= Str::lower(option('kreativ-anders.memberkit.checkoutSlag'));
        // $returnURL .= '/portal/update';

        try {

          // STRIPE PORTAL SESSION
          $stripe = new \Stripe\StripeClient(option('kreativ-anders.memberkit.secretKey'));
          $session = $stripe->billingPortal->sessions->create([
            'customer' => $customer,
            'return_url' => $returnURL,
          ]);

          $url = $session->url;
          
      
        } catch(Exception $e) {
        
          // LOG ERROR SOMEWHERE !!!
        }     

        return go($url);
      }
    ],
    // CANCEL STRIPE SUBSCRIPTION
    [
      // PATTERN --> CHECKOUT SLAG / ACTION NAME (CANCEL) / STRIPE TIER NAME
      'pattern' => Str::lower(option('kreativ-anders.memberkit.checkoutSlag')) . '/cancel/(:all)',
      'action' => function ($tier) {

        $subscription = kirby()->user()->stripe_subscription();
        $email = kirby()->user()->email();

        try {

          // CANCEL STRIPE SUBSCRIPTION
          $stripe = new \Stripe\StripeClient(option('kreativ-anders.memberkit.secretKey'));
          $stripe->subscriptions->cancel(
            $subscription,
            []
          );

          // SUBSCRIPTION STATUS WILL BE "CANCELED"
          kirby()->user($email)->update([
            'stripe_subscription' => null,
            'stripe_status' => null,
            'tier' => option('kreativ-anders.memberkit.tiers')[0]['name']
          ]);
                
        } catch(Exception $e) {
        
          // LOG ERROR SOMEWHERE !!!
        }     

        return go();
      }
    ],
    // UPDATE/MERGE USER AFTER (SUCCESSFUL) CHECKOUT
    [
      // PATTERN --> CHECKOUT SLAG / success
      'pattern' => Str::lower(option('kreativ-anders.memberkit.checkoutSlag')) . '/success',
      'action' => function () {

        try {

          kirby()->user()->mergeStripeCustomer();

        } catch(Exception $e) {
        
          // LOG ERROR SOMEWHERE !!!
        }       

        return go(option('kreativ-anders.memberkit.successURL'));
      }
    ],
    // STRIPE WEBHOOK - OUH YEAH BABY!
    // https://stripe.com/docs/webhooks/integration-builder
    // --> NOT SECURED!!!
    [
      // PATTERN --> CHECKOUT SLAG / ACTION NAME (update)
      'pattern' => Str::lower(option('kreativ-anders.memberkit.checkoutSlag')) . '/webhook',
      'action' => function () {

        \Stripe\Stripe::setApiKey('sk_test_66EkZ3GZowFWgxrXszrRnSd200EmvYltOK');

        $payload = @file_get_contents('php://input');
        $event = null;
        
        try {
          $event = \Stripe\Event::constructFrom(
            json_decode($payload, true)
          );
        } catch(\UnexpectedValueException $e) {
          // Invalid payload
          echo '⚠️  Webhook error while parsing basic request.';
          http_response_code(400);
          exit();
        }   

        // Handle the event
        switch ($event->type) {
          case 'payment_intent.succeeded':
            $paymentIntent = $event->data->object; // contains a \Stripe\PaymentIntent
            // Then define and call a method to handle the successful payment intent.
            // handlePaymentIntentSucceeded($paymentIntent);
            break;
          case 'payment_method.attached':
            $paymentMethod = $event->data->object; // contains a \Stripe\PaymentMethod
            // Then define and call a method to handle the successful attachment of a PaymentMethod.
            // handlePaymentMethodAttached($paymentMethod);
            break;
          case 'customer.subscription.updated':
              $subscription = $event->data->object;

              break;
          default:
            // Unexpected event type
            echo 'Received unknown event type';
        }
        http_response_code(200);

        return [
          'id' => 'succer'
        ];
      }
    ],
  ];
};

