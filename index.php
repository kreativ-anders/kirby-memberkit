<?php

// INCLUDE EXTERNAL LIBRARIES
include_once __DIR__ . '/lib/stripe/init.php';

Kirby::plugin('kreativ-anders/memberkit', [

  'options'     => include_once __DIR__ . '/options.php',
  'snippets'    => include_once __DIR__ . '/snippets.php',
  'hooks'       => include_once __DIR__ . '/hooks.php',
  'routes'      => include_once __DIR__ . '/routes.php',
  'userMethods' => include_once __DIR__ . '/userMethods.php',
  'siteMethods' => include_once __DIR__ . '/siteMethods.php',

]);


?>