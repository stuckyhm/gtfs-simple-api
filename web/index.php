<?php

error_reporting(E_ALL | E_STRICT);
define('ROOT', dirname(__DIR__));

require ROOT.'/app/autoload.php';

$app = require_once ROOT.'/app/start.php';

$app->run();
