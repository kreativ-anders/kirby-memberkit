<?php

// INCLUDE EXTERNAL LIBRARIES
include_once __DIR__ . '/lib/stripe/init.php';

Kirby::plugin('kreativ-anders/stripekit', [

  'options'     => include __DIR__ . '/options.php',
  'snippets'    => include __DIR__ . '/snippets.php',
  'hooks'       => include __DIR__ . '/hooks.php',
  'routes'      => include __DIR__ . '/routes.php',
  'userMethods' => include __DIR__ . '/userMethods.php',

]);


?>