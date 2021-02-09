<?php

/* 
  OPTIONS 
  ----
  https://getkirby.com/docs/reference/plugins/extensions/options

*/

return [

  'secretKey'     => 'sk_test_xxx',
  'publicKey'     => 'pk_test_xxx',
  'checkoutSlag'  => 'checkout',
  'successURL'    => '../success',
  'cancelURL'     => '../cancel',

  /*
    TIERS
    ----
    The tiers config is a 2D-array that needs to be ordererd hierarchical.
    The first index is always the entry/default tier after registration. 
    Due to consistency the tier on index 0 holds a price, but it is never ever checked, so keep it null.
    All the following tiers need to be greater than the one before, e.g., FREE --> BASIC --> PREMIUM --> SUPER DELUXE etc.
  */

  'tiers'         => [
    // INDEX 0
    [ 'name'  => 'FREE'
     ,'price' => null],
    // INDEX 1
    [ 'name'  => 'BASIC'
     ,'price' => 'price_xxxx'],
    // INDEX 2
    [ 'name'  => 'PREMIUM'
     ,'price' => 'price_xxxx'],
  ]

];