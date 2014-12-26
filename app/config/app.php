<?php
/*
 * Slim Application Config
 */

define('SLIM_MODE_DEV', 'development');
define('SLIM_MODE_PRO', 'production');
define('SLIM_MODE', SLIM_MODE_DEV);

date_default_timezone_set('Europe/Berlin');

return array(
    'mode' => SLIM_MODE,
    'cookies.secret_key' => md5('appsecretkey'),
    
    'debug' => SLIM_MODE === SLIM_MODE_DEV,
    'log.enabled' => SLIM_MODE === SLIM_MODE_DEV,
    'log.writer' => new \Slim\Extras\Log\DateTimeFileWriter(array(
        'path' => ROOT . '/data/logs',
        'name_format' => 'Y-m-d',
        'message_format' => '%label% - %date% - %message%'
            ))
);
