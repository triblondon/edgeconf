<?php
/**
 * Auth callback
 *
 * Callback script for Google login
 *
 *
 * @codingstandard ftlabs-phpcs
 * @copyright The Financial Times Limited [All Rights Reserved]
 */

require_once '../app/global';

session_start();
$auth = new GoogleAuth($_SESSION, '/oauth2callback.php', array(
  'canceldest' => 'http://edgeconf.com/2014-london/register.php'
));

try {
	$auth->receiveCallback($_GET);
} catch (Exception $e) {
	$_SESSION = array();
	session_destroy();
	echo "<p>Sorry, login with Google account failed with the following reason:</p><p><strong>".$e->getMessage()."</strong></p><p>Login process has been cancelled.  Please <a href='http://edgeconf.com'>try again</a>.</p>";
}

