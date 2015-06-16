<?php

require __DIR__."/../vendor/autoload.php";


/* Load services into dependency injection container */

$app = new ServicesContainer();

if (isset($_SERVER['HTTP_DEBUG']) and $_SERVER['HTTP_DEBUG'] === $app->config->debug->debug_key) {
	ini_set('display_errors', 1);
	ini_set('html_errors', 1);
	error_reporting(E_ALL | E_STRICT);
	header('Debug: Enabled');
} else {
	$error_handler = new Raven_ErrorHandler($app->sentry);
	set_error_handler(array($error_handler, 'handleError'));
	set_exception_handler(array($error_handler, 'handleException'));
}

/* Define routing and dispatch controllers to build response */

$router = new Routing\Router($app);

// Set URL slug patterns
$router->setPattern('eventslug', '\d{4}\-\w+');
$router->setPattern('id', '\d+');

// Cope with Andrew's extreme muppetry
$router->route('/2014-sf/register', '/2015-london/register');


// Authentication routes
$router->route('/auth/callback', 'AuthCallback');
$router->route('/auth/logout', 'AuthLogout');
$router->route('/auth/email/start-verify', 'PublicSite\AuthEmailSendCode');
$router->route('/auth/email/verify', 'PublicSite\AuthEmailVerify');

// Public content routes
$router->route('/:eventslug', 'PublicSite\Info');
$router->route('/:eventslug/(?<page>schedule|faq|hub)', 'PublicSite\Info');
$router->route('/:eventslug/register', 'PublicSite\Register');
$router->route('/:eventslug/video', 'PublicSite\VideoAPI');
$router->route('/:eventslug/video/(?<video_id>[\w\d\-\_]+)', 'PublicSite\VideoAPI');
$router->route('/:eventslug/pay/charge', 'PublicSite\BillingCharge');
$router->route('/:eventslug/pay/cancel', 'PublicSite\BillingCancel');
$router->route('/:eventslug/share', 'PublicSite\ShareByEmail');

$router->route('/bot', 'PublicSite\Bot');

// Public tools
$router->route('/sign', 'Signage');

// Admin routes
$router->route('/admin', '/admin/people');
$router->route('/admin/people', 'Admin\People');
$router->route('/admin/people/:id', 'Admin\Person');
$router->route('/admin/people/new', 'Admin\Person');
$router->route('/admin/panels', 'Admin\Panels');
$router->route('/admin/invite', 'Admin\Invite');
$router->route('/admin/rate', 'Admin\Rate');
$router->route('/admin/badges', 'Admin\Badges');
$router->route('/admin/exports/(?<export>panels|attendees)(?:\.(?<format>csv|json))?', 'Admin\Export');

// Shortcuts
$promotedevent = '/2015-london';
$router->route('/', $promotedevent);
$router->route('/register', $promotedevent.'/register');
$router->route('/faq(?:\.html)', $promotedevent.'/faq');
$router->route('/hub', $promotedevent.'/hub');
$router->route('/feedback', 'https://docs.google.com/forms/d/1exJhCC0YjwPBh3qXf8BRNKSZ2kiY7XRGk_LLg_lhl3M/viewform');



$router->route('/errortest', 'ErrorTest');

$router->errorUnsupportedMethod('Errors\Error405');
$router->errorNoRoute('Errors\Error404');

$req = Routing\Request::createFromGlobals();
$resp = new Routing\Response();

$router->dispatch($req, $resp);


/* Serve the response */

$resp->serve();
