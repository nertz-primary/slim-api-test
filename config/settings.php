<?php
$settings = [
	'root'   => dirname(__DIR__),
	'temp'   => dirname(__DIR__) . '/tmp',
	'public' => dirname(__DIR__) . '/public',
	'determineRouteBeforeAppMiddleware' => false,
	'displayErrorDetails' => true,
	'error' => [
		'display_error_details' => true,
		'log_errors'            => true,
		'log_error_details'     => true,
	],
	'db' => [
		'driver'    => 'mysql',
		'host'      => 'localhost',
		'database'  => 'test_slim',
		'username'  => 'test_slim',
		'password'  => 'jlTnQT9_Wu0DzaSB',
		'charset'   => 'utf8',
		'collation' => 'utf8_unicode_ci',
		'prefix'    => '',
		'flags'     => array(),
	],
];

return $settings;