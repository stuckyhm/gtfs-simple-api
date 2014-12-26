<?php
/**
 * Database Config
 */

return array(
	'default' => 'mysql',

	'connections' => array(
		'mysql' => array(
			'driver'    => 'mysql',
			'host'      => isset($_ENV['DB_HOST']) ? $_ENV['DB_HOST'] : 'mysql',
			'database'  => isset($_ENV['DB_NAME']) ? $_ENV['DB_NAME'] : 'gtfs',
			'username'  => isset($_ENV['DB_USER']) ? $_ENV['DB_USER'] : 'root',
			'password'  => isset($_ENV['DB_PASS']) ? $_ENV['DB_PASS'] : '',
			'charset'   => 'utf8',
			'collation' => 'utf8_unicode_ci',
			'prefix'    => '',
		),
	),
);
