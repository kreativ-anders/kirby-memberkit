<?php

/* 
  OPTIONS 
  ----
  https://getkirby.com/docs/reference/plugins/extensions/options

*/

return [

  'secretKey'     => 'sk_test_xxx',
  'publicKey'     => 'pk_test_xxx',
  'stripeURLSlug' => 'checkout',
  'successURL'    => '../success',
  'cancelURL'     => '../cancel',
  'tiers'         => [
    // INDEX 0
    [ 'name'  => 'Free'
     ,'price' => null],
    // INDEX 1
    [ 'name'  => 'Basic'
     ,'price' => 'price_xxxx'],
    // INDEX 2
    [ 'name'  => 'Premium'
     ,'price' => 'price_xxxx'],
    // INDEX X
  ]

];