<?php
/*
 * Example local configuration override file
 */

$config = [
	'debug' => 1,
	'App' => [
		'fullBaseUrl' => 'https://infohub-dev.byu.edu'
	],
	'Security' => [
		'salt' => 'SECURE SALT HERE',
		'cipherSeed' => 'ALL-DIGITS CIPHER SEED HERE'
	],
	'Datasources' => [
		'default' => [
			'host' => 'SOME HOST HERE',
			'login' => 'USERNAME',
			'password' => 'PASSWORD',
		],
		'byuApi' => [
			'key' => 'KEY',
			'secret' => 'SECRET'
		],
		'collibra' => [
			'url'       =>  'https://byu-dev.collibra.com/rest/latest/',
			'username'  => 'SOME USERNAME',
			'password'  => 'REAL PASSWORD'
		]
	]
];
