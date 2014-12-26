<?php

require ROOT.'/app/dbloader.php';

// Instantiate application
$app = new \Slim\Slim(require_once ROOT.'/app/config/app.php');
$app->setName('RedSlim');

// For native PHP session
session_cache_limiter(false);
session_start();

// For encrypted cookie session 
/*
$app->add(new \Slim\Middleware\SessionCookie(array(
            'expires' => '20 minutes',
            'path' => '/',
            'domain' => null,
            'secure' => false,
            'httponly' => false,
            'name' => 'app_session_name',
            'secret' => md5('appsecretkey'),
            'cipher' => MCRYPT_RIJNDAEL_256,
            'cipher_mode' => MCRYPT_MODE_CBC
        )));
*/

foreach(glob(ROOT.'/app/hooks/*.php') as $hook) {
  include $hook;
}

foreach(glob(ROOT.'/app/controllers/*.php') as $router) {
  include $router;
}

// Disable fluid mode in production environment
$app->configureMode(SLIM_MODE_PRO, function () use ($app) {
    // note, transactions will be auto-committed in fluid mode
    R::freeze(true);  
});

return $app;
