<?php
	require_once("lib/class.db.php");
	require_once("lib/goibibo-php-client/GoIbibo.php");
	require_once("lib/linkedin-php-sdk/LinkedIn/LinkedIn.php");
	require_once("lib/facebook-php-sdk/src/facebook.php");

	define("APP_BASE_DOMAIN", "http://tnaa.localtunnel.me/qwerty");

	$db_host = "localhost";
	$db_port = 3301;
	$db_name = "qwerty";

	$db_user = "root";
	$db_pass = "root";

	$db = new db("mysql:host=$db_host;port=$db_port;dbname=$db_name", "$db_user", "$db_pass");
	$db->setErrorCallbackFunction("showError", "text");

	$api = new GoIbibo("8bef9fe5", "705a79d71ea767c939acac7890ff3503");

	$li = new LinkedIn(array(
		'api_key' => '75nupia224q9n1',
		'api_secret' => 'xMfrKTVO03lYMz24',
		'callback_url' => APP_BASE_DOMAIN . '/user/join/linkedIn'
	));

	$facebook = new Facebook(array(
	  'appId'  => '557900567598972',
	  'secret' => 'a4ab3c2c304d8040c1d83c40b54056c1',
	));


	$GLOBALS['db'] = $db;
	$GLOBALS['bus'] = $api;
	$GLOBALS['li'] = $li;
	$GLOBALS['fb'] = $facebook;
